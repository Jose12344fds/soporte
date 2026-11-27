<?php
/**
 * API: Guardar Orden de Trabajo - @TAMA_ORDTRABAJO
 * POST JSON body:
 *   docentry    -> int  (0 = nueva, >0 = editar)
 *   accion      -> 'guardar' | 'cerrar' | 'reabrir'
 *   itemcode    -> string (obligatorio en guardar)
 *   tipomant    -> string
 *   tecnico     -> string
 *   progmant    -> string (opcional)
 *   progdet     -> string (opcional)
 *   fechainicio -> 'YYYY-MM-DD'
 *   fechafin    -> 'YYYY-MM-DD' (opcional)
 *   horainicio  -> 'HH:MM' (opcional)
 *   horafin     -> 'HH:MM' (opcional)
 *   trabajo     -> string
 *   observacion -> string
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if (!$session->isUserLoggedIn(false)) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

function ot_out(array $data, int $status = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function ot_v($v): string {
    return trim((string)($v ?? ''));
}

function ot_nullable(string $value): ?string {
    return $value !== '' ? $value : null;
}

function ot_hora_to_sap(?string $value): ?int {
    $value = ot_v($value ?? '');
    if ($value === '') {
        return null;
    }
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
        $hh = (int)$m[1];
        $mm = (int)$m[2];
        if ($hh >= 0 && $hh <= 23 && $mm >= 0 && $mm <= 59) {
            return ($hh * 100) + $mm;
        }
    }
    if (preg_match('/^\d{3,4}$/', $value)) {
        return (int)$value;
    }
    throw new InvalidArgumentException('La hora debe tener formato HH:MM.');
}

function ot_fetch_prog(PDO $pdo, string $code): ?array {
    $stmt = $pdo->prepare(
        'SELECT "Code","Name","U_ItemCode","U_TipoMant","U_FechaProg","U_Frecuencia","U_Estado","U_Observacion"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" WHERE "Code" = ?'
    );
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function ot_fetch_progdet(PDO $pdo, string $code): ?array {
    $stmt = $pdo->prepare(
        'SELECT * FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" WHERE "Code" = ?'
    );
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function ot_fetch_ot(PDO $pdo, int $docentry): ?array {
    $stmt = $pdo->prepare(
        'SELECT "DocEntry","DocNum","Status","Canceled","U_ProgMant","U_ProgDet","U_FechaInicio","U_FechaFin",'
        . ' "U_Observacion","UpdateDate"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" WHERE "DocEntry" = ?'
    );
    $stmt->execute([$docentry]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function ot_set_prog_estado(PDO $pdo, string $code, string $estado, string $date, int $time): void {
    $stmt = $pdo->prepare(
        'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
        . ' SET "U_Estado" = ?, "UpdateDate" = ?, "UpdateTime" = ?'
        . ' WHERE "Code" = ?'
    );
    $stmt->execute([$estado, $date, $time, $code]);
}

function ot_set_progdet_estado(PDO $pdo, string $code, string $estado, string $date, int $time, ?int $docentry = null, ?int $docnum = null, ?string $fechaEjec = null, ?int $horaIni = null, ?int $horaFin = null): void {
    $stmt = $pdo->prepare(
        'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_PROGDET"'
        . ' SET "U_Estado" = ?, "U_OTDocEntry" = ?, "U_OTDocNum" = ?, "U_FechaEjec" = ?, "U_HoraIni" = ?, "U_HoraFin" = ?,'
        . ' "U_FueraFecha" = ?, "UpdateDate" = ?, "UpdateTime" = ?'
        . ' WHERE "Code" = ?'
    );
    $fueraFecha = ($estado === 'Ejecutado fuera de fecha') ? 'Y' : 'N';
    $stmt->execute([
        $estado,
        $docentry,
        $docnum,
        $fechaEjec,
        $horaIni,
        $horaFin,
        $fueraFecha,
        $date,
        $time,
        $code,
    ]);
}

function ot_freq_interval(string $frecuencia): ?DateInterval {
    $map = [
        'Diario' => 'P1D',
        'Semanal' => 'P1W',
        'Quincenal' => 'P14D',
        'Mensual' => 'P1M',
        'Trimestral' => 'P3M',
        'Semestral' => 'P6M',
        'Anual' => 'P1Y',
        'Bienal' => 'P2Y',
    ];
    return isset($map[$frecuencia]) ? new DateInterval($map[$frecuencia]) : null;
}

function ot_next_prog_date(string $fechaBase, string $frecuencia): ?string {
    if ($fechaBase === '') {
        return null;
    }
    $interval = ot_freq_interval($frecuencia);
    if (!$interval) {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr($fechaBase, 0, 10));
    if (!$dt) {
        return null;
    }
    $dt->add($interval);
    return $dt->format('Y-m-d');
}

function ot_align_next_prog_date(string $fechaBase, string $frecuencia, string $referenceDate): ?string {
    $fechaBase = substr(ot_v($fechaBase), 0, 10);
    $referenceDate = substr(ot_v($referenceDate), 0, 10);
    if ($fechaBase === '' || $referenceDate === '') {
        return null;
    }
    $interval = ot_freq_interval($frecuencia);
    if (!$interval) {
        return $fechaBase;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $fechaBase);
    if (!$dt) {
        return null;
    }
    $guard = 0;
    while ($dt->format('Y-m-d') <= $referenceDate && $guard < 1000) {
        $dt->add($interval);
        $guard++;
    }
    return $dt->format('Y-m-d');
}

function ot_prog_exists_for_cycle(PDO $pdo, array $prog, string $nextDate, string $exceptCode): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS "CNT"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
        . ' WHERE "U_ItemCode" = ? AND COALESCE("U_TipoMant", \'\') = COALESCE(?, \'\')'
        . ' AND COALESCE("U_Frecuencia", \'\') = COALESCE(?, \'\')'
        . ' AND "U_FechaProg" = ? AND "Code" <> ?'
    );
    $stmt->execute([
        ot_v($prog['U_ItemCode'] ?? ''),
        ot_nullable(ot_v($prog['U_TipoMant'] ?? '')),
        ot_nullable(ot_v($prog['U_Frecuencia'] ?? '')),
        $nextDate,
        $exceptCode,
    ]);
    $row = $stmt->fetch();
    return ((int)($row['CNT'] ?? array_values($row)[0] ?? 0)) > 0;
}

function ot_next_pm(PDO $pdo): int {
    $row = $pdo->query(
        'SELECT COALESCE(MAX(CAST(SUBSTRING("Code", 4) AS INTEGER)), 0) + 1 AS "NXT"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
        . " WHERE \"Code\" LIKE_REGEXPR '^PM-[0-9]+$'"
    )->fetch();
    return (int)($row['NXT'] ?? array_values($row)[0] ?? 1);
}

function ot_next_prog_docentry(PDO $pdo): int {
    $row = $pdo->query(
        'SELECT COALESCE(MAX("DocEntry"), 0) + 1 AS "NXT"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
    )->fetch();
    return (int)($row['NXT'] ?? array_values($row)[0] ?? 1);
}

function ot_prog_code_exists(PDO $pdo, string $code): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS "CNT" FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" WHERE "Code" = ?'
    );
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    return ((int)($row['CNT'] ?? array_values($row)[0] ?? 0)) > 0;
}

function ot_prog_name_exists(PDO $pdo, string $name): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS "CNT" FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" WHERE "Name" = ?'
    );
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    return ((int)($row['CNT'] ?? array_values($row)[0] ?? 0)) > 0;
}

function ot_prog_unique_name(PDO $pdo, string $name, string $code): string {
    $base = ot_v($name);
    if ($base === '') {
        $base = $code;
    }
    if (!ot_prog_name_exists($pdo, $base)) {
        return $base;
    }
    for ($i = 1; $i <= 50; $i++) {
        $suffix = $i === 1 ? " [{$code}]" : " [{$code}-{$i}]";
        $limit = max(1, 100 - strlen($suffix));
        $candidate = substr($base, 0, $limit) . $suffix;
        if (!ot_prog_name_exists($pdo, $candidate)) {
            return $candidate;
        }
    }
    return substr($code . ' ' . $base, 0, 100);
}

function ot_create_next_programacion(PDO $pdo, array $prog, string $nowDate, int $nowTime): ?string {
    $fechaProg = substr(ot_v($prog['U_FechaProg'] ?? ''), 0, 10);
    $frecuencia = ot_v($prog['U_Frecuencia'] ?? '');
    $nextDate = ot_align_next_prog_date($fechaProg, $frecuencia, $nowDate);
    if (!$nextDate) {
        return null;
    }
    if (ot_prog_exists_for_cycle($pdo, $prog, $nextDate, ot_v($prog['Code'] ?? ''))) {
        return null;
    }

    $codigo = '';
    $docEntry = 0;
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $codigo = 'PM-' . str_pad((string)ot_next_pm($pdo), 4, '0', STR_PAD_LEFT);
        if (!ot_prog_code_exists($pdo, $codigo)) {
            $docEntry = ot_next_prog_docentry($pdo);
            break;
        }
        usleep(100000 * $attempt);
    }
    if ($codigo === '' || $docEntry <= 0) {
        throw new RuntimeException('No se pudo generar la siguiente programacion.');
    }

    $name = ot_prog_unique_name($pdo, ot_v($prog['Name'] ?? ''), $codigo);
    $stmt = $pdo->prepare(
        'INSERT INTO "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
        . ' ("Code","Name","DocEntry","Canceled","Object","LogInst","UserSign","Transfered",'
        . ' "CreateDate","CreateTime","UpdateDate","UpdateTime","DataSource",'
        . ' "U_ItemCode","U_TipoMant","U_FechaProg","U_Frecuencia","U_Estado","U_Observacion")'
        . ' VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $codigo,
        $name,
        $docEntry,
        'N',
        'TAMA_PROGMANT',
        1,
        1,
        'N',
        $nowDate,
        $nowTime,
        $nowDate,
        $nowTime,
        'I',
        ot_v($prog['U_ItemCode'] ?? ''),
        ot_nullable(ot_v($prog['U_TipoMant'] ?? '')),
        $nextDate,
        ot_nullable($frecuencia),
        'Pendiente',
        ot_nullable(ot_v($prog['U_Observacion'] ?? '')),
    ]);

    return $codigo;
}

function ot_estado_por_ejecucion(string $fechaProg, string $fechaCierre): string {
    $prog = substr($fechaProg, 0, 10);
    $cierre = substr($fechaCierre, 0, 10);
    if ($prog !== '' && $cierre !== '' && $cierre > $prog) {
        return 'Ejecutado fuera de fecha';
    }
    return 'Ejecutado';
}

function ot_estado_abierto_para_prog(string $fechaProg, string $today): string {
    $prog = substr($fechaProg, 0, 10);
    if ($prog !== '' && $prog < $today) {
        return 'Vencido';
    }
    return 'Pendiente';
}

try {
    $input = function_exists('request_json_body') ? request_json_body() : [];
    if (!$input) {
        $raw = file_get_contents('php://input');
        $input = !empty($raw) ? (json_decode($raw, true) ?? $_POST) : $_POST;
    }

    $docentry = isset($input['docentry']) ? (int)$input['docentry'] : 0;
    $accion = ot_v($input['accion'] ?? 'guardar');
    $itemcode = strtoupper(ot_v($input['itemcode'] ?? ''));
    $tipomant = strtoupper(ot_v($input['tipomant'] ?? ''));
    $tecnico = strtoupper(ot_v($input['tecnico'] ?? ''));
    $detalleSvt = ot_v($input['detalle_svt'] ?? '');
    $progmant = strtoupper(ot_v($input['progmant'] ?? ''));
    $progdet = strtoupper(ot_v($input['progdet'] ?? ''));
    $finicio = ot_v($input['fechainicio'] ?? '');
    $ffin = ot_v($input['fechafin'] ?? '');
    $horaInicio = ot_v($input['horainicio'] ?? '');
    $horaFin = ot_v($input['horafin'] ?? '');
    $titulo = ot_v($input['titulo'] ?? '');
    $trabajo = ot_v($input['trabajo'] ?? '');
    $observ = ot_v($input['observacion'] ?? '');
    $motivo = ot_v($input['motivo'] ?? '');
    if (mb_strlen($trabajo) > 20000 || mb_strlen($observ) > 20000) {
        ot_out(['error' => 'Trabajo realizado y observaciones admiten hasta 20,000 caracteres cada uno.'], 400);
    }
    if (mb_strlen($titulo) > 150) {
        ot_out(['error' => 'El titulo de la OT admite hasta 150 caracteres.'], 400);
    }
    if (mb_strlen($detalleSvt) > 254) {
        ot_out(['error' => 'La informacion de SVT admite hasta 254 caracteres.'], 400);
    }
    $horaInicioSap = ot_hora_to_sap($horaInicio);
    $horaFinSap = ot_hora_to_sap($horaFin);

    $pdo = getHanaPdo();
    $nowDate = date('Y-m-d');
    $nowTime = (int)date('Hi');

    $esTecnicoSvt = ($tecnico === 'SVT');
    if ($tecnico !== '') {
        $techStmt = $pdo->prepare(
            'SELECT "Name" FROM "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS" WHERE "Code" = ? LIMIT 1'
        );
        $techStmt->execute([$tecnico]);
        $techRow = $techStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $esTecnicoSvt = $esTecnicoSvt || strtoupper(ot_v($techRow['Name'] ?? '')) === 'SVT';
    }
    if ($esTecnicoSvt && $detalleSvt === '') {
        ot_out(['error' => 'Ingrese el nombre o la informacion correspondiente al tecnico SVT.'], 400);
    }
    if (!$esTecnicoSvt) {
        $detalleSvt = '';
    }

    if ($accion === 'anular' && $docentry > 0) {
        if (!tiene_permiso('mantenimientos', 'editar')) {
            ot_out(['error' => 'No tiene permiso para anular ordenes de trabajo.'], 403);
        }
        if (mb_strlen($motivo) < 4 || mb_strlen($motivo) > 180) {
            ot_out(['error' => 'Ingrese un motivo de 4 a 180 caracteres.'], 400);
        }
        $ot = ot_fetch_ot($pdo, $docentry);
        if (!$ot) {
            ot_out(['error' => 'La OT no existe.'], 404);
        }
        if (($ot['Canceled'] ?? 'N') === 'Y') {
            ot_out(['error' => 'La OT ya se encuentra anulada.'], 409);
        }
        if (($ot['Status'] ?? '') === 'C') {
            ot_out(['error' => 'Una OT cerrada no puede anularse.'], 409);
        }

        $user = current_user();
        $userName = substr(ot_v($user['name'] ?? $user['username'] ?? 'Sistema'), 0, 50);
        $audit = '[ANULADA ' . $nowDate . ' - ' . $userName . '] ' . $motivo;
        $previous = ot_v($ot['U_Observacion'] ?? '');
        $cancelObservation = mb_substr($audit . ($previous !== '' ? ' | Anterior: ' . $previous : ''), 0, 254);
        $progDetCode = strtoupper(ot_v($ot['U_ProgDet'] ?? ''));
        $progCode = strtoupper(ot_v($ot['U_ProgMant'] ?? ''));

        $pdo->beginTransaction();
        $cancelOt = $pdo->prepare(
            'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" SET'
            . ' "Canceled" = \'Y\', "Status" = \'C\', "U_Observacion" = ?,'
            . ' "UpdateDate" = ?, "UpdateTime" = ? WHERE "DocEntry" = ? AND "Status" = \'O\''
        );
        $cancelOt->execute([$cancelObservation, $nowDate, $nowTime, $docentry]);

        $linkedDetails = $pdo->prepare(
            'SELECT * FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" WHERE'
            . ' "Code" = ? OR "U_OTDocEntry" = ? OR "U_OTDocNum" = ?'
        );
        $linkedDetails->execute([$progDetCode, $docentry, (int)($ot['DocNum'] ?? 0)]);
        $releasedCodes = [];
        foreach ($linkedDetails->fetchAll(PDO::FETCH_ASSOC) as $det) {
            $detCode = strtoupper(ot_v($det['Code'] ?? ''));
            if ($detCode === '' || isset($releasedCodes[$detCode])) {
                continue;
            }
            $estadoDet = ot_estado_abierto_para_prog(ot_v($det['U_FechaProg'] ?? ''), $nowDate);
            ot_set_progdet_estado($pdo, $detCode, $estadoDet, $nowDate, $nowTime);
            $releasedCodes[$detCode] = true;
        }
        if ($progCode !== '') {
            $prog = ot_fetch_prog($pdo, $progCode);
            if ($prog) {
                ot_set_prog_estado(
                    $pdo,
                    $progCode,
                    ot_estado_abierto_para_prog(ot_v($prog['U_FechaProg'] ?? ''), $nowDate),
                    $nowDate,
                    $nowTime
                );
            }
        }
        $pdo->commit();
        ot_out([
            'success' => true,
            'docentry' => $docentry,
            'docnum' => $ot['DocNum'] ?? null,
            'action' => 'anulada',
            'occurrences_released' => count($releasedCodes),
        ]);
    }

    $selectedProgDet = null;
    if ($progdet !== '') {
        $det = ot_fetch_progdet($pdo, $progdet);
        if (!$det) {
            ot_out(['error' => 'La ocurrencia seleccionada no existe.'], 404);
        }
        $selectedProgDet = $det;
        // La ocurrencia es la fuente autoritativa de la relacion con el plan.
        $progmant = strtoupper(ot_v($det['U_ProgMant'] ?? ''));
        if ($docentry <= 0 && ot_v($det['U_Estado'] ?? '') === 'Cancelado') {
            ot_out(['error' => 'No se puede generar una OT para una ocurrencia cancelada.'], 409);
        }
    }

    if (in_array($accion, ['cerrar', 'reabrir'], true) && $docentry > 0) {
        $ot = ot_fetch_ot($pdo, $docentry);
        if (!$ot) {
            ot_out(['error' => 'La OT no existe.'], 404);
        }
        if (($ot['Canceled'] ?? 'N') === 'Y') {
            ot_out(['error' => 'La OT esta anulada y no puede cambiar de estado.'], 409);
        }

        $estadoOt = ($accion === 'cerrar') ? 'C' : 'O';
        $fechaFin = ($accion === 'cerrar')
            ? substr(ot_v($ot['U_FechaFin'] ?? ''), 0, 10)
            : '';
        if ($accion === 'cerrar' && $fechaFin === '') {
            $fechaFin = $nowDate;
        }

        $stmt = $pdo->prepare(
            'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" SET'
            . ' "Status" = ?, "U_FechaFin" = ?, "UpdateDate" = ?, "UpdateTime" = ?'
            . ' WHERE "DocEntry" = ?'
        );
        $stmt->execute([
            $estadoOt,
            $accion === 'cerrar' ? $fechaFin : ot_nullable($fechaFin),
            $nowDate,
            $nowTime,
            $docentry,
        ]);

        $nextProgCode = null;
        $progDetCode = strtoupper(ot_v($ot['U_ProgDet'] ?? ''));
        $progCode = strtoupper(ot_v($ot['U_ProgMant'] ?? ''));
        if ($progDetCode !== '') {
            $det = ot_fetch_progdet($pdo, $progDetCode);
            if ($det) {
                $estadoDet = $accion === 'cerrar'
                    ? ot_estado_por_ejecucion(ot_v($det['U_FechaProg'] ?? ''), $fechaFin !== '' ? $fechaFin : $nowDate)
                    : ot_estado_abierto_para_prog(ot_v($det['U_FechaProg'] ?? ''), $nowDate);
                ot_set_progdet_estado(
                    $pdo,
                    $progDetCode,
                    $estadoDet,
                    $nowDate,
                    $nowTime,
                    $docentry,
                    (int)($ot['DocNum'] ?? 0),
                    $accion === 'cerrar' ? ($fechaFin !== '' ? $fechaFin : $nowDate) : null,
                    $accion === 'cerrar' ? $horaInicioSap : null,
                    $accion === 'cerrar' ? $horaFinSap : null
                );
            }
        } elseif ($progCode !== '') {
            $prog = ot_fetch_prog($pdo, $progCode);
            if ($prog) {
                if ($accion === 'cerrar') {
                    $estadoProg = ot_estado_por_ejecucion(
                        ot_v($prog['U_FechaProg'] ?? ''),
                        $fechaFin !== '' ? $fechaFin : $nowDate
                    );
                    ot_set_prog_estado($pdo, $progCode, $estadoProg, $nowDate, $nowTime);
                    $nextProgCode = ot_create_next_programacion($pdo, $prog, $nowDate, $nowTime);
                } else {
                    $estadoProg = ot_estado_abierto_para_prog(ot_v($prog['U_FechaProg'] ?? ''), $nowDate);
                    ot_set_prog_estado($pdo, $progCode, $estadoProg, $nowDate, $nowTime);
                }
            }
        }

        ot_out([
            'success' => true,
            'docentry' => $docentry,
            'action' => $accion,
            'next_programacion' => $nextProgCode,
        ]);
    }

    if ($itemcode === '') {
        ot_out(['error' => 'El equipo es obligatorio'], 400);
    }

    if ($docentry > 0) {
        $actualOt = ot_fetch_ot($pdo, $docentry);
        if (!$actualOt) {
            ot_out(['error' => 'La OT no existe.'], 404);
        }
        if (($actualOt['Canceled'] ?? 'N') === 'Y') {
            ot_out(['error' => 'La OT esta anulada y no puede editarse.'], 409);
        }

        $pdo->beginTransaction();
        if ($progdet !== '') {
            $lockOccurrence = $pdo->prepare(
                'SELECT "Code" FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET"'
                . ' WHERE "Code" = ? FOR UPDATE'
            );
            $lockOccurrence->execute([$progdet]);
            if (!$lockOccurrence->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                ot_out(['error' => 'La ocurrencia seleccionada ya no existe.'], 404);
            }

            $duplicateOt = $pdo->prepare(
                'SELECT TOP 1 "DocEntry","DocNum"'
                . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
                . ' WHERE "U_ProgDet" = ? AND "DocEntry" <> ?'
                . ' AND COALESCE("Canceled", \'N\') <> \'Y\''
                . ' ORDER BY "DocEntry" DESC'
            );
            $duplicateOt->execute([$progdet, $docentry]);
            $ownerOt = $duplicateOt->fetch(PDO::FETCH_ASSOC);
            if ($ownerOt) {
                $pdo->rollBack();
                ot_out([
                    'error' => 'La ocurrencia ya esta vinculada a la OT #' . ($ownerOt['DocNum'] ?? $ownerOt['DocEntry']) . '.',
                    'duplicate' => true,
                    'docentry' => (int)($ownerOt['DocEntry'] ?? 0),
                    'docnum' => (int)($ownerOt['DocNum'] ?? 0),
                ], 409);
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" SET'
            . ' "U_ItemCode" = ?,'
            . ' "U_TipoMant" = ?,'
            . ' "U_Tecnico" = ?,'
            . ' "U_DetalleSVT" = ?,'
            . ' "U_ProgMant" = ?,'
            . ' "U_ProgDet" = ?,'
            . ' "U_FechaInicio" = ?,'
            . ' "U_FechaFin" = ?,'
            . ' "U_Hora_Ini" = ?,'
            . ' "U_Hora_Fin" = ?,'
            . ' "U_TituFor" = ?,'
            . ' "U_TrabajoRealizado" = ?,'
            . ' "U_Observacion" = ?,'
            . ' "Remark" = ?,'
            . ' "UpdateDate" = ?,'
            . ' "UpdateTime" = ?'
            . ' WHERE "DocEntry" = ?'
        );
        $stmt->execute([
            $itemcode,
            ot_nullable($tipomant),
            ot_nullable($tecnico),
            ot_nullable($detalleSvt),
            ot_nullable($progmant),
            ot_nullable($progdet),
            $finicio,
            ot_nullable($ffin),
            $horaInicioSap,
            $horaFinSap,
            ot_nullable($titulo),
            $trabajo,
            ot_nullable($observ),
            $trabajo,
            $nowDate,
            $nowTime,
            $docentry,
        ]);

        // Libera cualquier ocurrencia anterior o residual de esta OT antes de fijar la relacion actual.
        $staleDetails = $pdo->prepare(
            'SELECT "Code","U_FechaProg" FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET"'
            . ' WHERE ("Code" = ? OR "U_OTDocEntry" = ? OR "U_OTDocNum" = ?)'
            . ' AND "Code" <> ?'
        );
        $staleDetails->execute([
            strtoupper(ot_v($actualOt['U_ProgDet'] ?? '')),
            $docentry,
            (int)($actualOt['DocNum'] ?? 0),
            $progdet,
        ]);
        foreach ($staleDetails->fetchAll(PDO::FETCH_ASSOC) as $stale) {
            $staleCode = strtoupper(ot_v($stale['Code'] ?? ''));
            if ($staleCode === '') continue;
            ot_set_progdet_estado(
                $pdo,
                $staleCode,
                ot_estado_abierto_para_prog(ot_v($stale['U_FechaProg'] ?? ''), $nowDate),
                $nowDate,
                $nowTime
            );
        }

        if ($progdet !== '') {
            $det = ot_fetch_progdet($pdo, $progdet);
            if ($det) {
                $otCerrada = ($actualOt['Status'] ?? 'O') === 'C';
                $fechaCierre = substr(ot_v($actualOt['U_FechaFin'] ?? ''), 0, 10);
                $estadoDet = $otCerrada
                    ? ot_estado_por_ejecucion(ot_v($det['U_FechaProg'] ?? ''), $fechaCierre !== '' ? $fechaCierre : $nowDate)
                    : ot_estado_abierto_para_prog(ot_v($det['U_FechaProg'] ?? ''), $nowDate);
                ot_set_progdet_estado(
                    $pdo,
                    $progdet,
                    $estadoDet,
                    $nowDate,
                    $nowTime,
                    $docentry,
                    (int)($actualOt['DocNum'] ?? 0),
                    $otCerrada ? ($fechaCierre !== '' ? $fechaCierre : $nowDate) : null,
                    $otCerrada ? $horaInicioSap : null,
                    $otCerrada ? $horaFinSap : null
                );
            }
        }
        $pdo->commit();
        ot_out(['success' => true, 'docentry' => $docentry, 'action' => 'updated']);
    }

    $period = date('Y');
    $user = current_user();
    $creator = substr(ot_v($user['name'] ?? 'SISTEMA'), 0, 8);

    $pdo->beginTransaction();
    if ($progdet !== '') {
        $lockOccurrence = $pdo->prepare(
            'SELECT "Code","U_OTDocEntry","U_OTDocNum"'
            . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET"'
            . ' WHERE "Code" = ? FOR UPDATE'
        );
        $lockOccurrence->execute([$progdet]);
        $lockedOccurrence = $lockOccurrence->fetch(PDO::FETCH_ASSOC);
        if (!$lockedOccurrence) {
            $pdo->rollBack();
            ot_out(['error' => 'La ocurrencia seleccionada ya no existe.'], 404);
        }

        $duplicateOt = $pdo->prepare(
            'SELECT TOP 1 "DocEntry","DocNum","Status"'
            . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
            . ' WHERE "U_ProgDet" = ? AND COALESCE("Canceled", \'N\') <> \'Y\''
            . ' ORDER BY "DocEntry" DESC'
        );
        $duplicateOt->execute([$progdet]);
        $existingOt = $duplicateOt->fetch(PDO::FETCH_ASSOC);
        if ($existingOt) {
            $pdo->rollBack();
            ot_out([
                'error' => 'La ocurrencia ya tiene una OT generada.',
                'duplicate' => true,
                'docentry' => (int)($existingOt['DocEntry'] ?? 0),
                'docnum' => (int)($existingOt['DocNum'] ?? 0),
            ], 409);
        }
    }

    $lastError = null;
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $maxRow = $pdo->query(
            'SELECT COALESCE(MAX("DocEntry"),0)+1 AS "nE", COALESCE(MAX("DocNum"),0)+1 AS "nN"'
            . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
        )->fetch();

        $nextEntry = (int)($maxRow['nE'] ?? array_values($maxRow)[0] ?? 1);
        $nextNum = (int)($maxRow['nN'] ?? array_values($maxRow)[1] ?? $nextEntry);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
                . ' ("DocEntry","DocNum","Period","Instance","Series","Handwrtten","Canceled","Object","LogInst","UserSign",'
                . ' "Transfered","Status","CreateDate","CreateTime","UpdateDate","UpdateTime","DataSource","RequestStatus",'
                . ' "Creator","Remark","U_ProgMant","U_ProgDet","U_ItemCode","U_TipoMant","U_Tecnico","U_DetalleSVT","U_FechaInicio","U_FechaFin","U_Hora_Ini","U_Hora_Fin","U_TituFor","U_TrabajoRealizado","U_Observacion")'
                . ' VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $nextEntry,
                $nextNum,
                $period,
                1,
                1,
                'N',
                'N',
                'TAMA_ORDTRABAJO',
                1,
                1,
                'N',
                'O',
                $nowDate,
                $nowTime,
                $nowDate,
                $nowTime,
                'I',
                'A',
                $creator,
                $trabajo,
                ot_nullable($progmant),
                ot_nullable($progdet),
                $itemcode,
                ot_nullable($tipomant),
                ot_nullable($tecnico),
                ot_nullable($detalleSvt),
                $finicio,
                ot_nullable($ffin),
                $horaInicioSap,
                $horaFinSap,
                ot_nullable($titulo),
                $trabajo,
                ot_nullable($observ),
            ]);
            if ($progdet !== '') {
                $det = ot_fetch_progdet($pdo, $progdet);
                if ($det) {
                    $estadoDet = ot_estado_abierto_para_prog(ot_v($det['U_FechaFin'] ?? $det['U_FechaProg'] ?? ''), $nowDate);
                    ot_set_progdet_estado(
                        $pdo,
                        $progdet,
                        $estadoDet,
                        $nowDate,
                        $nowTime,
                        $nextEntry,
                        $nextNum,
                        null,
                        null,
                        null
                    );
                }
            }
            $pdo->commit();
            ot_out(['success' => true, 'docentry' => $nextEntry, 'docnum' => $nextNum, 'action' => 'inserted']);
        } catch (Throwable $insertEx) {
            $lastError = $insertEx;
            $msg = $insertEx->getMessage();
            if (strpos($msg, 'unique constraint') === false && strpos($msg, '301') === false) {
                throw $insertEx;
            }
            usleep(100000 * $attempt);
        }
    }

    throw ($lastError ?: new RuntimeException('No se pudo generar un DocEntry unico para la OT'));
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ot_out(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
}
