<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

function reprog_out(array $data, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!$session->isUserLoggedIn(true)) {
    reprog_out(['error' => 'Sesion no valida.'], 401);
}
if (!tiene_permiso('mantenimientos', 'editar')) {
    reprog_out(['error' => 'No tiene permiso para reprogramar ocurrencias.'], 403);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;

    $codes = array_values(array_unique(array_filter(array_map(
        static fn($value) => strtoupper(trim((string)$value)),
        is_array($input['codes'] ?? null) ? $input['codes'] : []
    ))));
    $fechaNueva = substr(trim((string)($input['fecha_nueva'] ?? '')), 0, 10);
    $motivo = trim((string)($input['motivo'] ?? ''));

    if (!$codes || count($codes) > 100) {
        reprog_out(['error' => 'Seleccione entre 1 y 100 ocurrencias.'], 400);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNueva)) {
        reprog_out(['error' => 'Seleccione una fecha de destino valida.'], 400);
    }
    if ($motivo === '' || mb_strlen($motivo) < 4 || mb_strlen($motivo) > 250) {
        reprog_out(['error' => 'Ingrese un motivo de 4 a 250 caracteres.'], 400);
    }

    $fechaSql = $db->escape($fechaNueva);
    $calendarRows = find_by_sql(
        "SELECT YEAR(D.fecha) AS anio, D.semana, D.Mes AS mes, MIN(W.fecha) AS fecha_inicio, MAX(W.fecha) AS fecha_fin
         FROM dias_del_anio D
         INNER JOIN dias_del_anio W ON YEAR(W.fecha) = YEAR(D.fecha) AND W.semana = D.semana
         WHERE D.fecha = '{$fechaSql}'
         GROUP BY YEAR(D.fecha), D.semana, D.Mes"
    );
    if (!$calendarRows) {
        reprog_out(['error' => 'La fecha no existe en el calendario dias_del_anio.'], 400);
    }
    $calendar = $calendarRows[0];
    $anio = (string)$calendar['anio'];
    $semana = (string)$calendar['semana'];
    $mes = (string)(int)$calendar['mes'];
    $fechaInicio = substr((string)$calendar['fecha_inicio'], 0, 10);
    $fechaFin = substr((string)$calendar['fecha_fin'], 0, 10);

    $pdo = getHanaPdo();
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare(
        'SELECT D."Code", D."U_ProgMant", SUBSTRING(TO_VARCHAR(D."U_FechaProg"), 1, 10) AS "FechaAnterior",'
        . ' COALESCE(D."U_Estado", \'Pendiente\') AS "Estado", D."U_OTDocEntry", D."U_OTDocNum",'
        . ' P."U_ItemCode", E."U_Estado" AS "EquipoEstado"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" D'
        . ' INNER JOIN "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" P ON P."Code" = D."U_ProgMant"'
        . ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E ON E."Name" = P."U_ItemCode"'
        . ' WHERE D."Code" IN (' . $placeholders . ')'
    );
    $stmt->execute($codes);
    $occurrences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($occurrences) !== count($codes)) {
        reprog_out(['error' => 'Una o mas ocurrencias seleccionadas ya no existen. Actualice el listado.'], 409);
    }

    foreach ($occurrences as $occurrence) {
        if (strtoupper(trim((string)($occurrence['EquipoEstado'] ?? ''))) === 'DE BAJA') {
            reprog_out([
                'error' => 'No se puede reprogramar la ocurrencia ' . $occurrence['Code']
                    . ': el equipo ' . ($occurrence['U_ItemCode'] ?? '') . ' esta DE BAJA.',
            ], 409);
        }
        if (!empty($occurrence['U_OTDocEntry']) || !empty($occurrence['U_OTDocNum'])) {
            reprog_out(['error' => 'La ocurrencia ' . $occurrence['Code'] . ' ya tiene una OT y no puede moverse.'], 409);
        }
        $estado = trim((string)$occurrence['Estado']);
        if (!in_array($estado, ['Pendiente', 'Vencido'], true)) {
            reprog_out(['error' => 'La ocurrencia ' . $occurrence['Code'] . ' esta ' . $estado . ' y no puede moverse.'], 409);
        }
    }

    $nowDate = date('Y-m-d');
    $nowTime = (int)date('Hi');
    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    $userName = substr(trim((string)($user['name'] ?? $user['username'] ?? 'Sistema')), 0, 100);
    $update = $pdo->prepare(
        'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" SET'
        . ' "U_Anio" = ?, "U_Semana" = ?, "U_Mes" = ?, "U_FechaInicio" = ?, "U_FechaFin" = ?,'
        . ' "U_FechaProg" = ?, "U_Estado" = \'Pendiente\', "UpdateDate" = ?, "UpdateTime" = ?'
        . ' WHERE "Code" = ? AND COALESCE("U_OTDocEntry", 0) = 0 AND COALESCE("U_OTDocNum", 0) = 0'
    );

    $pdo->beginTransaction();
    $plans = [];
    foreach ($occurrences as $occurrence) {
        $update->execute([$anio, $semana, $mes, $fechaInicio, $fechaFin, $fechaNueva, $nowDate, $nowTime, $occurrence['Code']]);
        $plans[(string)$occurrence['U_ProgMant']] = true;
    }

    $verify = $pdo->prepare(
        'SELECT COUNT(*) AS "CNT" FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET"'
        . ' WHERE "Code" IN (' . $placeholders . ')'
        . ' AND SUBSTRING(TO_VARCHAR("U_FechaProg"), 1, 10) = ?'
        . ' AND COALESCE("U_OTDocEntry", 0) = 0 AND COALESCE("U_OTDocNum", 0) = 0'
    );
    $verify->execute(array_merge($codes, [$fechaNueva]));
    $verified = $verify->fetch(PDO::FETCH_ASSOC) ?: [];
    if ((int)($verified['CNT'] ?? 0) !== count($codes)) {
        throw new RuntimeException('Una ocurrencia cambio durante el proceso. No se guardaron los cambios.');
    }

    $insertHistory = $pdo->prepare(
        'INSERT INTO "SBO_TAMAING_PRODUC"."@TAMA_REPROGHIST"'
        . ' ("Code", "Name", "U_ProgDet", "U_FechaAnt", "U_FechaNue", "U_Semana",'
        . ' "U_Motivo", "U_UserId", "U_Usuario", "U_FecReg", "U_HoraReg")'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($occurrences as $occurrence) {
        $historyCode = 'RH' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        $occurrenceCode = (string)$occurrence['Code'];
        $insertHistory->execute([
            $historyCode,
            substr('REPR-' . $occurrenceCode . '-' . substr($historyCode, -8), 0, 100),
            substr($occurrenceCode, 0, 30),
            substr((string)$occurrence['FechaAnterior'], 0, 10),
            $fechaNueva,
            (int)$semana,
            substr('[REPROGRAMACION] ' . $motivo, 0, 254),
            $userId,
            $userName,
            $nowDate,
            $nowTime,
        ]);
    }

    $syncSelect = $pdo->prepare(
        'SELECT MIN("U_FechaProg") AS "NEXT_DATE", MAX("U_PeriodoNum") AS "LAST_PERIOD"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET"'
        . ' WHERE "U_ProgMant" = ? AND COALESCE("U_Estado", \'Pendiente\') IN (\'Pendiente\', \'Vencido\')'
    );
    $syncUpdate = $pdo->prepare(
        'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" SET'
        . ' "U_FechaProg" = ?, "U_UltPeriodoGen" = ?, "UpdateDate" = ?, "UpdateTime" = ? WHERE "Code" = ?'
    );
    foreach (array_keys($plans) as $planCode) {
        if ($planCode === '') continue;
        $syncSelect->execute([$planCode]);
        $next = $syncSelect->fetch(PDO::FETCH_ASSOC) ?: [];
        $syncUpdate->execute([
            !empty($next['NEXT_DATE']) ? substr((string)$next['NEXT_DATE'], 0, 10) : null,
            isset($next['LAST_PERIOD']) ? (string)$next['LAST_PERIOD'] : null,
            $nowDate,
            $nowTime,
            $planCode,
        ]);
    }
    $pdo->commit();

    reprog_out([
        'success' => true,
        'updated' => count($occurrences),
        'fecha_nueva' => $fechaNueva,
        'semana_nueva' => (int)$semana,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Error al reprogramar mantenimiento: ' . $e->getMessage());
    reprog_out([
        'error' => 'No se pudo completar la reprogramacion en SAP. Los cambios fueron revertidos.',
    ], 500);
}
