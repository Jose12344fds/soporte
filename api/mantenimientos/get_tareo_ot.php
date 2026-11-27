<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';

function tareo_ot_out(array $payload, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function tareo_ot_date($value): string {
    $date = substr(trim((string)$value), 0, 10);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

try {
    $docentry = filter_input(INPUT_GET, 'docentry', FILTER_VALIDATE_INT);
    if (!$docentry || $docentry < 1) {
        tareo_ot_out(['success' => false, 'error' => 'La orden de trabajo no es valida.'], 400);
    }

    $pdo = getHanaPdo();
    $otStmt = $pdo->prepare(
        'SELECT TOP 1 T0."DocEntry", T0."DocNum", T0."U_ItemCode",'
        . ' COALESCE(E."U_ItemNameEq", T0."U_ItemCode") AS "ItemName",'
        . ' T0."U_FechaInicio", T0."U_FechaFin"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" T0'
        . ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E ON E."Name" = T0."U_ItemCode"'
        . ' WHERE T0."DocEntry" = ?'
    );
    $otStmt->execute([(int)$docentry]);
    $ot = $otStmt->fetch(PDO::FETCH_ASSOC);
    if (!$ot) tareo_ot_out(['success' => false, 'error' => 'La orden de trabajo ya no existe.'], 404);

    $equipo = trim((string)($ot['U_ItemCode'] ?? ''));
    $fechaInicio = tareo_ot_date($ot['U_FechaInicio'] ?? '');
    $fechaFin = tareo_ot_date($ot['U_FechaFin'] ?? '');
    if ($fechaFin === '') $fechaFin = $fechaInicio;

    if ($equipo === '' || $fechaInicio === '') {
        tareo_ot_out([
            'success' => true,
            'ot' => $ot,
            'rows' => [],
            'summary' => ['horas' => 0, 'trabajadores' => 0, 'registros' => 0],
            'notice' => $equipo === '' ? 'La OT no tiene equipo asignado.' : 'La OT no tiene fecha de inicio.'
        ]);
    }

    if ($fechaFin < $fechaInicio) $fechaFin = $fechaInicio;

    $sql = <<<'SQL'
SELECT
    T."Code",
    T."U_FECHA" AS "Fecha",
    T."U_ID_TRABAJADOR" AS "IdTrabajador",
    T."U_DNI" AS "Dni",
    T."U_NOMBRE_TRABA" AS "Trabajador",
    T."U_HORAS" AS "Horas",
    T."U_Columna" AS "Columna",
    T."U_OT" AS "OtProduccion",
    T."U_ITEM_NO" AS "ItemNo",
    T."U_DESCRIPTION" AS "Descripcion",
    T."U_PROCESO" AS "Proceso",
    T."U_PROCESO_TRAB" AS "ProcesoTrabajador",
    T."U_EQUIPO_TM" AS "Equipo"
FROM "SBO_TAMAING_PRODUC"."@TAMA_TAREO_OT_CIF" T
WHERE TRIM(COALESCE(T."U_EQUIPO_TM", '')) = ?
  AND SUBSTRING(TO_VARCHAR(T."U_FECHA"), 1, 10) BETWEEN ? AND ?
ORDER BY T."U_FECHA", T."U_NOMBRE_TRABA", T."U_Columna"
SQL;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$equipo, $fechaInicio, $fechaFin]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalHoras = 0.0;
    $trabajadores = [];
    foreach ($rows as &$row) {
        $row['Fecha'] = tareo_ot_date($row['Fecha'] ?? '');
        $row['Horas'] = round((float)($row['Horas'] ?? 0), 2);
        $totalHoras += $row['Horas'];
        $workerKey = trim((string)($row['Dni'] ?? ''));
        if ($workerKey === '') $workerKey = trim((string)($row['IdTrabajador'] ?? ''));
        if ($workerKey !== '') $trabajadores[$workerKey] = true;
    }
    unset($row);

    $ot['U_FechaInicio'] = $fechaInicio;
    $ot['U_FechaFin'] = $fechaFin;
    tareo_ot_out([
        'success' => true,
        'ot' => $ot,
        'rows' => $rows,
        'summary' => [
            'horas' => round($totalHoras, 2),
            'trabajadores' => count($trabajadores),
            'registros' => count($rows)
        ]
    ]);
} catch (Throwable $e) {
    tareo_ot_out(['success' => false, 'error' => 'No se pudo consultar el tareo: ' . $e->getMessage()], 500);
}
