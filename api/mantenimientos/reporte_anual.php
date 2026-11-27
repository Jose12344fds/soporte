<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once __DIR__ . '/conexion_hana.php';

function reporte_out(array $data, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!$session->isUserLoggedIn(false)) reporte_out(['error' => 'No autorizado.'], 401);
if (!tiene_permiso('mantenimientos', 'ver')) reporte_out(['error' => 'Sin permiso.'], 403);

try {
    $pdo = getHanaPdo();
    $currentYear = (int)date('Y');
    $year = (int)($_GET['anio'] ?? $currentYear);
    if ($year < 2020 || $year > $currentYear + 1) $year = $currentYear;

    // El mes oficial lo define dias_del_anio.Mes para conservar semanas completas.
    $calendarRows = find_by_sql(
        "SELECT DATE_FORMAT(fecha, '%Y-%m-%d') fecha, semana, Mes mes
         FROM dias_del_anio WHERE anio=" . $year . " ORDER BY fecha"
    );
    $calendarMonthByDate = [];
    $calendarMonths = [];
    foreach ($calendarRows as $calendarRow) {
        $calendarDate = substr((string)($calendarRow['fecha'] ?? ''), 0, 10);
        $calendarMonth = (int)($calendarRow['mes'] ?? 0);
        $calendarWeek = (int)($calendarRow['semana'] ?? 0);
        if ($calendarDate === '' || $calendarMonth < 1 || $calendarMonth > 12) continue;
        $calendarMonthByDate[$calendarDate] = $calendarMonth;
        if (!isset($calendarMonths[$calendarMonth])) {
            $calendarMonths[$calendarMonth] = [
                'mes' => $calendarMonth, 'fecha_inicio' => $calendarDate, 'fecha_fin' => $calendarDate,
                'semana_inicio' => $calendarWeek, 'semana_fin' => $calendarWeek,
            ];
        } else {
            $calendarMonths[$calendarMonth]['fecha_fin'] = $calendarDate;
            $calendarMonths[$calendarMonth]['semana_fin'] = $calendarWeek;
        }
    }
    ksort($calendarMonths);
    $calendarMonths = array_values($calendarMonths);

    $yearsSql = <<<'SQL'
SELECT DISTINCT YEAR(COALESCE(T0."U_FechaInicio", T0."CreateDate")) AS "Anio"
FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" T0
WHERE COALESCE(T0."U_FechaInicio", T0."CreateDate") IS NOT NULL
ORDER BY "Anio" DESC
SQL;
    $years = array_values(array_filter(array_map(
        static fn(array $row): int => (int)($row['Anio'] ?? 0),
        $pdo->query($yearsSql)->fetchAll(PDO::FETCH_ASSOC)
    )));
    if (!in_array($currentYear, $years, true)) array_unshift($years, $currentYear);

    $sql = <<<'SQL'
SELECT
    T0."DocEntry",
    T0."DocNum",
    T0."Status",
    T0."Canceled",
    T0."CreateDate",
    T0."U_ProgMant",
    T0."U_ProgDet",
    T0."U_ItemCode",
    COALESCE(T5."U_ItemNameEq", T0."U_ItemCode") AS "ItemName",
    T5."U_InventryNo" AS "InventryNo",
    T5."U_Marca" AS "Marca",
    T5."U_Modelo" AS "Modelo",
    T0."U_TipoMant",
    COALESCE(T3."Name", T0."U_TipoMant") AS "TipoMantName",
    T0."U_Tecnico",
    COALESCE(T2."Name", T0."U_Tecnico") AS "TecnicoName",
    T0."U_FechaInicio",
    T0."U_FechaFin",
    T0."U_Hora_Ini",
    T0."U_Hora_Fin",
    T0."U_TituFor"
FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" T0
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS" T2 ON T0."U_Tecnico"=T2."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" T3 ON T0."U_TipoMant"=T3."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T5 ON T0."U_ItemCode"=T5."Name"
WHERE YEAR(COALESCE(T0."U_FechaInicio", T0."CreateDate")) = ?
ORDER BY COALESCE(T0."U_FechaInicio", T0."CreateDate") DESC, T0."DocEntry" DESC
SQL;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = ['total' => count($rows), 'abiertas' => 0, 'cerradas' => 0, 'anuladas' => 0, 'sin_tecnico' => 0];
    foreach ($rows as &$row) {
        $canceled = strtoupper(trim((string)($row['Canceled'] ?? 'N'))) === 'Y';
        $closed = strtoupper(trim((string)($row['Status'] ?? 'O'))) === 'C';
        $row['EstadoNombre'] = $canceled ? 'Anulada' : ($closed ? 'Cerrada' : 'Abierta');
        if ($canceled) $summary['anuladas']++;
        elseif ($closed) $summary['cerradas']++;
        else $summary['abiertas']++;
        if (trim((string)($row['U_Tecnico'] ?? '')) === '') $summary['sin_tecnico']++;
    }
    unset($row);

    $matrixSql = <<<'SQL'
SELECT
    D."Code" AS "OccurrenceCode",
    SUBSTRING(TO_VARCHAR(D."U_FechaProg"),1,10) AS "ScheduledDate",
    D."U_Anio" AS "ScheduledYear",
    D."U_Semana" AS "ScheduledWeek",
    D."U_Mes" AS "StoredMonth",
    COALESCE(D."U_Estado", 'Pendiente') AS "OccurrenceStatus",
    P."U_ItemCode" AS "ItemCode",
    COALESCE(NULLIF(E."U_ItemNameEq", ''), P."U_ItemCode") AS "ItemName",
    COALESCE(NULLIF(E."U_Cod_Tipo", ''), 'SIN-CODIGO') AS "FamilyCode",
    COALESCE(NULLIF(E."U_TIP_EMH", ''), 'SIN FAMILIA') AS "FamilyName",
    UPPER(TRIM(COALESCE(P."U_Modalidad", ''))) AS "Modalidad",
    OT."DocEntry" AS "OTDocEntry",
    OT."DocNum" AS "OTDocNum",
    OT."Status" AS "OTStatus",
    OT."Canceled" AS "OTCanceled",
    SUBSTRING(TO_VARCHAR(COALESCE(OT."U_FechaFin", OT."UpdateDate")),1,10) AS "ExecutedDate"
FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" D
INNER JOIN "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" P ON D."U_ProgMant"=P."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E ON P."U_ItemCode"=E."Name"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" OT
    ON D."Code"=OT."U_ProgDet" AND COALESCE(OT."Canceled", 'N')<>'Y'
WHERE YEAR(D."U_FechaProg")=? AND COALESCE(D."U_Estado", 'Pendiente')<>'Cancelado'
ORDER BY "FamilyCode","FamilyName","ItemCode","ScheduledDate","OccurrenceCode"
SQL;
    $matrixStmt = $pdo->prepare($matrixSql);
    $matrixStmt->execute([$year]);
    $matrixSource = $matrixStmt->fetchAll(PDO::FETCH_ASSOC);

    $correctiveSql = <<<'SQL'
SELECT
    OT."DocEntry" AS "OTDocEntry",
    OT."DocNum" AS "OTDocNum",
    OT."U_ItemCode" AS "ItemCode",
    COALESCE(NULLIF(E."U_ItemNameEq", ''), OT."U_ItemCode") AS "ItemName",
    COALESCE(NULLIF(E."U_Cod_Tipo", ''), 'SIN-CODIGO') AS "FamilyCode",
    COALESCE(NULLIF(E."U_TIP_EMH", ''), 'SIN FAMILIA') AS "FamilyName",
    SUBSTRING(TO_VARCHAR(COALESCE(OT."U_FechaFin", OT."U_FechaInicio", OT."UpdateDate", OT."CreateDate")),1,10) AS "CorrectiveDate"
FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" OT
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" TM ON OT."U_TipoMant"=TM."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E ON OT."U_ItemCode"=E."Name"
WHERE COALESCE(OT."Canceled", 'N')<>'Y'
  AND UPPER(COALESCE(OT."Status", 'O'))='C'
  AND (UPPER(TRIM(COALESCE(OT."U_TipoMant", '')))='CORR'
       OR UPPER(TRIM(COALESCE(TM."Name", ''))) LIKE 'CORRECT%')
  AND YEAR(COALESCE(OT."U_FechaFin", OT."U_FechaInicio", OT."UpdateDate", OT."CreateDate"))=?
ORDER BY "FamilyCode","ItemCode","CorrectiveDate","OTDocEntry"
SQL;
    $correctiveStmt = $pdo->prepare($correctiveSql);
    $correctiveStmt->execute([$year]);
    $correctiveOrders = $correctiveStmt->fetchAll(PDO::FETCH_ASSOC);

    // Una ocurrencia cuenta una sola vez aunque existan cruces historicos repetidos con OT.
    $occurrences = [];
    foreach ($matrixSource as $source) {
        $occurrenceCode = trim((string)($source['OccurrenceCode'] ?? ''));
        if ($occurrenceCode === '') continue;
        if (!isset($occurrences[$occurrenceCode])) {
            $occurrences[$occurrenceCode] = [
                'family_code' => trim((string)($source['FamilyCode'] ?? 'SIN-CODIGO')),
                'family_name' => trim((string)($source['FamilyName'] ?? 'SIN FAMILIA')),
                'item_code' => trim((string)($source['ItemCode'] ?? '')),
                'item_name' => trim((string)($source['ItemName'] ?? '')),
                'scheduled_date' => substr((string)($source['ScheduledDate'] ?? ''), 0, 10),
                'scheduled_year' => (int)($source['ScheduledYear'] ?? 0),
                'scheduled_week' => (int)($source['ScheduledWeek'] ?? 0),
                'stored_month' => (int)($source['StoredMonth'] ?? 0),
                'executed_date' => '',
                'ot_docentry' => 0,
                'ot_docnum' => 0,
                'occurrence_code' => $occurrenceCode,
                'modalidad' => strtoupper(trim((string)($source['Modalidad'] ?? ''))),
            ];
        }
        if (strtoupper(trim((string)($source['OTStatus'] ?? ''))) === 'C'
            && strtoupper(trim((string)($source['OTCanceled'] ?? 'N'))) !== 'Y') {
            $executedDate = substr((string)($source['ExecutedDate'] ?? ''), 0, 10);
            $docEntry = (int)($source['OTDocEntry'] ?? 0);
            if ($executedDate !== '' && $docEntry >= (int)$occurrences[$occurrenceCode]['ot_docentry']) {
                $occurrences[$occurrenceCode]['executed_date'] = $executedDate;
                $occurrences[$occurrenceCode]['ot_docentry'] = $docEntry;
                $occurrences[$occurrenceCode]['ot_docnum'] = (int)($source['OTDocNum'] ?? $docEntry);
            }
        }
    }

    $matrix = [];
    $matrixSummary = ['programadas' => 0, 'ejecutadas' => 0, 'correctivas' => 0, 'equipos' => 0, 'familias' => 0];
    foreach ($occurrences as $occurrence) {
        $familyCode = $occurrence['family_code'] !== '' ? $occurrence['family_code'] : 'SIN-CODIGO';
        $familyName = $occurrence['family_name'] !== '' ? $occurrence['family_name'] : 'SIN FAMILIA';
        $familyKey = $familyCode . '|' . $familyName;
        $itemCode = $occurrence['item_code'] !== '' ? $occurrence['item_code'] : 'SIN-EQUIPO';
        if (!isset($matrix[$familyKey])) {
            $matrix[$familyKey] = ['codigo' => $familyCode, 'nombre' => $familyName, 'equipos' => []];
        }
        if (!isset($matrix[$familyKey]['equipos'][$itemCode])) {
            $matrix[$familyKey]['equipos'][$itemCode] = [
                'codigo' => $itemCode,
                'nombre' => $occurrence['item_name'] ?: $itemCode,
                'programado' => array_fill(0, 12, 0),
                'ejecutado' => array_fill(0, 12, 0),
                'correctivo' => array_fill(0, 12, 0),
            ];
        }
        $scheduledMonth = (int)($calendarMonthByDate[$occurrence['scheduled_date']] ?? $occurrence['stored_month'] ?? 0);
        if ($scheduledMonth < 1 || $scheduledMonth > 12) $scheduledMonth = (int)substr($occurrence['scheduled_date'], 5, 2);
        if ($scheduledMonth >= 1 && $scheduledMonth <= 12) {
            $matrix[$familyKey]['equipos'][$itemCode]['programado'][$scheduledMonth - 1]++;
            $matrixSummary['programadas']++;
        }
        if ($occurrence['executed_date'] !== '') {
            // El cumplimiento se atribuye al mes planificado, aunque la OT se cierre despues.
            $executedMonth = $scheduledMonth;
            if ($executedMonth >= 1 && $executedMonth <= 12) {
                $matrix[$familyKey]['equipos'][$itemCode]['ejecutado'][$executedMonth - 1]++;
                $matrixSummary['ejecutadas']++;
            }
        }
    }
    foreach ($correctiveOrders as $corrective) {
        $familyCode = trim((string)($corrective['FamilyCode'] ?? '')) ?: 'SIN-CODIGO';
        $familyName = trim((string)($corrective['FamilyName'] ?? '')) ?: 'SIN FAMILIA';
        $familyKey = $familyCode . '|' . $familyName;
        $itemCode = trim((string)($corrective['ItemCode'] ?? '')) ?: 'SIN-EQUIPO';
        if (!isset($matrix[$familyKey])) {
            $matrix[$familyKey] = ['codigo' => $familyCode, 'nombre' => $familyName, 'equipos' => []];
        }
        if (!isset($matrix[$familyKey]['equipos'][$itemCode])) {
            $matrix[$familyKey]['equipos'][$itemCode] = [
                'codigo' => $itemCode,
                'nombre' => trim((string)($corrective['ItemName'] ?? '')) ?: $itemCode,
                'programado' => array_fill(0, 12, 0),
                'ejecutado' => array_fill(0, 12, 0),
                'correctivo' => array_fill(0, 12, 0),
            ];
        }
        $correctiveDate = substr((string)($corrective['CorrectiveDate'] ?? ''), 0, 10);
        $correctiveMonth = (int)($calendarMonthByDate[$correctiveDate] ?? 0);
        if ($correctiveMonth < 1 || $correctiveMonth > 12) $correctiveMonth = (int)substr($correctiveDate, 5, 2);
        if ($correctiveMonth >= 1 && $correctiveMonth <= 12) {
            $matrix[$familyKey]['equipos'][$itemCode]['correctivo'][$correctiveMonth - 1]++;
            $matrixSummary['correctivas']++;
        }
    }
    uasort($matrix, static fn(array $a, array $b): int => strnatcasecmp($a['codigo'] . $a['nombre'], $b['codigo'] . $b['nombre']));
    foreach ($matrix as &$family) {
        uasort($family['equipos'], static fn(array $a, array $b): int => strnatcasecmp($a['codigo'], $b['codigo']));
        $family['equipos'] = array_values($family['equipos']);
        $matrixSummary['equipos'] += count($family['equipos']);
    }
    unset($family);
    $matrix = array_values($matrix);
    $matrixSummary['familias'] = count($matrix);

    // Matriz independiente por modalidad. La matriz P/E/C anterior permanece intacta.
    $modalityMatrix = [];
    $modalitySummary = [
        'programadas_mr' => 0, 'programadas_m' => 0,
        'ejecutadas_mr' => 0, 'ejecutadas_m' => 0,
        'correctivas' => 0, 'sin_modalidad' => 0, 'equipos' => 0, 'familias' => 0,
    ];
    $monthlyDetails = [
        'p_mr' => array_fill(0, 12, []), 'p_m' => array_fill(0, 12, []),
        'e_mr' => array_fill(0, 12, []), 'e_m' => array_fill(0, 12, []),
        'c' => array_fill(0, 12, []),
    ];
    foreach ($occurrences as $occurrence) {
        $modality = $occurrence['modalidad'] ?? '';
        if ($modality !== 'M' && $modality !== 'MR') {
            $modalitySummary['sin_modalidad']++;
            continue;
        }
        $familyCode = $occurrence['family_code'] !== '' ? $occurrence['family_code'] : 'SIN-CODIGO';
        $familyName = $occurrence['family_name'] !== '' ? $occurrence['family_name'] : 'SIN FAMILIA';
        $familyKey = $familyCode . '|' . $familyName;
        $itemCode = $occurrence['item_code'] !== '' ? $occurrence['item_code'] : 'SIN-EQUIPO';
        if (!isset($modalityMatrix[$familyKey])) {
            $modalityMatrix[$familyKey] = ['codigo' => $familyCode, 'nombre' => $familyName, 'equipos' => []];
        }
        if (!isset($modalityMatrix[$familyKey]['equipos'][$itemCode])) {
            $modalityMatrix[$familyKey]['equipos'][$itemCode] = [
                'codigo' => $itemCode,
                'nombre' => $occurrence['item_name'] ?: $itemCode,
                'programado_mr' => array_fill(0, 12, 0),
                'programado_m' => array_fill(0, 12, 0),
                'ejecutado_mr' => array_fill(0, 12, 0),
                'ejecutado_m' => array_fill(0, 12, 0),
                'correctivo' => array_fill(0, 12, 0),
                'detalle_p_mr' => array_fill(0, 12, []),
                'detalle_p_m' => array_fill(0, 12, []),
                'detalle_e_mr' => array_fill(0, 12, []),
                'detalle_e_m' => array_fill(0, 12, []),
                'detalle_c' => array_fill(0, 12, []),
            ];
        }
        $suffix = strtolower($modality);
        $scheduledMonth = (int)($calendarMonthByDate[$occurrence['scheduled_date']] ?? $occurrence['stored_month'] ?? 0);
        if ($scheduledMonth < 1 || $scheduledMonth > 12) $scheduledMonth = (int)substr($occurrence['scheduled_date'], 5, 2);
        if ($scheduledMonth >= 1 && $scheduledMonth <= 12) {
            $modalityMatrix[$familyKey]['equipos'][$itemCode]['programado_' . $suffix][$scheduledMonth - 1]++;
            $modalitySummary['programadas_' . $suffix]++;
            $monthlyDetails['p_' . $suffix][$scheduledMonth - 1][] = [
                'familyCode' => $familyCode, 'familyName' => $familyName,
                'code' => $itemCode, 'name' => $occurrence['item_name'] ?: $itemCode,
                'occurrence' => $occurrence['occurrence_code'],
                'date' => $occurrence['scheduled_date'], 'otDocEntry' => null, 'otDocNum' => null,
            ];
            $modalityMatrix[$familyKey]['equipos'][$itemCode]['detalle_p_' . $suffix][$scheduledMonth - 1][]
                = $monthlyDetails['p_' . $suffix][$scheduledMonth - 1][array_key_last($monthlyDetails['p_' . $suffix][$scheduledMonth - 1])];
        }
        if ($occurrence['executed_date'] !== '') {
            // P y E deben compartir el mes de la ocurrencia para medir cumplimiento mensual.
            $executedMonth = $scheduledMonth;
            if ($executedMonth >= 1 && $executedMonth <= 12) {
                $modalityMatrix[$familyKey]['equipos'][$itemCode]['ejecutado_' . $suffix][$executedMonth - 1]++;
                $modalitySummary['ejecutadas_' . $suffix]++;
                $monthlyDetails['e_' . $suffix][$executedMonth - 1][] = [
                    'familyCode' => $familyCode, 'familyName' => $familyName,
                    'code' => $itemCode, 'name' => $occurrence['item_name'] ?: $itemCode,
                    'occurrence' => $occurrence['occurrence_code'],
                    'date' => $occurrence['scheduled_date'],
                    'scheduledDate' => $occurrence['scheduled_date'],
                    'executedDate' => $occurrence['executed_date'],
                    'otDocEntry' => $occurrence['ot_docentry'] ?: null,
                    'otDocNum' => $occurrence['ot_docnum'] ?: null,
                ];
                $modalityMatrix[$familyKey]['equipos'][$itemCode]['detalle_e_' . $suffix][$executedMonth - 1][]
                    = $monthlyDetails['e_' . $suffix][$executedMonth - 1][array_key_last($monthlyDetails['e_' . $suffix][$executedMonth - 1])];
            }
        }
    }
    foreach ($correctiveOrders as $corrective) {
        $familyCode = trim((string)($corrective['FamilyCode'] ?? '')) ?: 'SIN-CODIGO';
        $familyName = trim((string)($corrective['FamilyName'] ?? '')) ?: 'SIN FAMILIA';
        $familyKey = $familyCode . '|' . $familyName;
        $itemCode = trim((string)($corrective['ItemCode'] ?? '')) ?: 'SIN-EQUIPO';
        if (!isset($modalityMatrix[$familyKey])) {
            $modalityMatrix[$familyKey] = ['codigo' => $familyCode, 'nombre' => $familyName, 'equipos' => []];
        }
        if (!isset($modalityMatrix[$familyKey]['equipos'][$itemCode])) {
            $modalityMatrix[$familyKey]['equipos'][$itemCode] = [
                'codigo' => $itemCode,
                'nombre' => trim((string)($corrective['ItemName'] ?? '')) ?: $itemCode,
                'programado_mr' => array_fill(0, 12, 0),
                'programado_m' => array_fill(0, 12, 0),
                'ejecutado_mr' => array_fill(0, 12, 0),
                'ejecutado_m' => array_fill(0, 12, 0),
                'correctivo' => array_fill(0, 12, 0),
                'detalle_p_mr' => array_fill(0, 12, []),
                'detalle_p_m' => array_fill(0, 12, []),
                'detalle_e_mr' => array_fill(0, 12, []),
                'detalle_e_m' => array_fill(0, 12, []),
                'detalle_c' => array_fill(0, 12, []),
            ];
        }
        $correctiveDate = substr((string)($corrective['CorrectiveDate'] ?? ''), 0, 10);
        $correctiveMonth = (int)($calendarMonthByDate[$correctiveDate] ?? 0);
        if ($correctiveMonth < 1 || $correctiveMonth > 12) $correctiveMonth = (int)substr($correctiveDate, 5, 2);
        if ($correctiveMonth >= 1 && $correctiveMonth <= 12) {
            $modalityMatrix[$familyKey]['equipos'][$itemCode]['correctivo'][$correctiveMonth - 1]++;
            $modalitySummary['correctivas']++;
            $monthlyDetails['c'][$correctiveMonth - 1][] = [
                'familyCode' => $familyCode, 'familyName' => $familyName,
                'code' => $itemCode,
                'name' => trim((string)($corrective['ItemName'] ?? '')) ?: $itemCode,
                'occurrence' => null, 'date' => $correctiveDate,
                'scheduledDate' => null, 'executedDate' => $correctiveDate,
                'otDocEntry' => (int)($corrective['OTDocEntry'] ?? 0) ?: null,
                'otDocNum' => (int)($corrective['OTDocNum'] ?? $corrective['OTDocEntry'] ?? 0) ?: null,
            ];
            $modalityMatrix[$familyKey]['equipos'][$itemCode]['detalle_c'][$correctiveMonth - 1][]
                = $monthlyDetails['c'][$correctiveMonth - 1][array_key_last($monthlyDetails['c'][$correctiveMonth - 1])];
        }
    }
    uasort($modalityMatrix, static fn(array $a, array $b): int => strnatcasecmp($a['codigo'] . $a['nombre'], $b['codigo'] . $b['nombre']));
    foreach ($modalityMatrix as &$modalityFamily) {
        uasort($modalityFamily['equipos'], static fn(array $a, array $b): int => strnatcasecmp($a['codigo'], $b['codigo']));
        $modalityFamily['equipos'] = array_values($modalityFamily['equipos']);
        $modalitySummary['equipos'] += count($modalityFamily['equipos']);
    }
    unset($modalityFamily);
    $modalityMatrix = array_values($modalityMatrix);
    $modalitySummary['familias'] = count($modalityMatrix);

    reporte_out([
        'success' => true, 'anio' => $year, 'anios' => $years, 'resumen' => $summary,
        'ordenes' => $rows, 'matriz' => $matrix, 'resumen_matriz' => $matrixSummary,
        'matriz_modalidad' => $modalityMatrix, 'resumen_modalidad' => $modalitySummary,
        'detalle_mensual' => $monthlyDetails, 'calendario_meses' => $calendarMonths,
    ]);
} catch (Throwable $e) {
    error_log('Reporte anual mantenimiento MT: ' . $e->getMessage());
    reporte_out(['error' => 'No se pudo consultar el reporte anual en SAP.'], 500);
}
