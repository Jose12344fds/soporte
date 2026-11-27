<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once __DIR__ . '/conexion_hana.php';

function disp_out(array $data, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function disp_date(string $value): string {
    return substr(str_replace("\0", '', trim($value)), 0, 10);
}

function disp_sap_time($value, int $default): int {
    $digits = preg_replace('/\D+/', '', str_replace("\0", '', trim((string)$value)));
    if ($digits === '') return $default;
    $number = (int)$digits;
    $hour = intdiv($number, 100);
    $minute = $number % 100;
    if ($hour > 23 || $minute > 59) return $default;
    return ($hour * 60) + $minute;
}

function disp_day_capacity(array $day): int {
    if ((int)($day['feriado'] ?? 0) === 1) return 0;
    $dow = (int)(new DateTimeImmutable((string)$day['fecha']))->format('N');
    if ($dow >= 1 && $dow <= 5) return 511; // 8.6 h menos charla de 5 min.
    if ($dow === 6) return 295;              // 5 h menos charla de 5 min.
    return 0;
}

function disp_order_minutes_for_day(array $order, array $day): int {
    if ((int)($day['feriado'] ?? 0) === 1) return 0;
    $date = (string)$day['fecha'];
    if ($date < $order['start_date'] || $date > $order['end_date']) return 0;

    $dow = (int)(new DateTimeImmutable($date))->format('N');
    if ($dow < 1 || $dow > 6) return 0;
    $workStart = 7 * 60;
    $workEnd = $dow === 6 ? 12 * 60 : (16 * 60) + 20;
    $start = $date === $order['start_date'] ? $order['start_time'] : $workStart;
    $end = $date === $order['end_date'] ? $order['end_time'] : $workEnd;
    $start = max($workStart, min($start, $workEnd));
    $end = max($workStart, min($end, $workEnd));
    if ($end <= $start) return 0;

    $minutes = $end - $start;
    if ($dow <= 5) {
        // Refrigerio operativo: 12:45 a 13:30 (45 min), descontado solo si se cruza.
        $lunchOverlap = max(0, min($end, 13 * 60 + 30) - max($start, 12 * 60 + 45));
        $minutes -= $lunchOverlap;
    }
    return max(0, min($minutes, disp_day_capacity($day)));
}

function disp_metrics(int $available, int $totalDowntime, int $failures, int $maintenanceEvents = 0): array {
    // Todas las formulas usan la parada total: correctiva + preventiva.
    $totalDowntime = max(0, min($totalDowntime, $available));
    $uptime = max(0, $available - $totalDowntime);
    // Sin fallas no existe divisor; se muestra el tiempo operativo real del periodo.
    $mtbf = $failures > 0 ? $uptime / $failures : (float)$uptime;
    // La mantenibilidad considera toda intervención que produjo parada,
    // incluso un preventivo sin falla correctiva.
    $effectiveEvents = max($failures, $maintenanceEvents);
    $mttr = $effectiveEvents > 0 ? $totalDowntime / $effectiveEvents : 0.0;
    $availability = $available > 0 ? $uptime / $available : 1.0;
    $reliability = $totalDowntime <= 0 ? 1.0 : ($mtbf > 0 ? exp(-$totalDowntime / $mtbf) : 0.0);
    // Excel: SI(ESERROR(1-2.7182^(-tiempo muerto total/MTTR));100%;formula)
    $maintainability = 1.0;
    if ($mttr > 0) {
        $calculatedMaintainability = 1 - pow(2.7182, -$totalDowntime / $mttr);
        if (is_finite($calculatedMaintainability)) {
            $maintainability = $calculatedMaintainability;
        }
    }
    return [
        'available' => $available,
        'downtime' => $totalDowntime,
        'uptime' => $uptime,
        'failures' => $failures,
        'mtbf' => round($mtbf, 2),
        'mttr' => round($mttr, 2),
        'check' => round(($mtbf * $failures) + $totalDowntime, 2),
        'availability' => round($availability * 100, 2),
        'reliability' => round($reliability * 100, 2),
        'maintainability' => round($maintainability * 100, 2),
    ];
}

if (!$session->isUserLoggedIn(false)) disp_out(['error' => 'No autorizado.'], 401);
if (!tiene_permiso('mantenimientos', 'ver')) disp_out(['error' => 'Sin permiso.'], 403);

try {
    $currentYear = (int)date('Y');
    $year = (int)($_GET['anio'] ?? $currentYear);
    $month = (int)($_GET['mes'] ?? (int)date('n'));
    $itemCode = strtoupper(trim((string)($_GET['equipo'] ?? '')));

    $yearRows = find_by_sql('SELECT DISTINCT anio FROM dias_del_anio ORDER BY anio DESC');
    $years = array_values(array_filter(array_map(static fn(array $row): int => (int)$row['anio'], $yearRows)));
    if (!$years) disp_out(['error' => 'La tabla dias_del_anio no contiene años configurados.'], 422);
    if (!in_array($year, $years, true)) $year = $years[0];

    $monthRows = find_by_sql('SELECT DISTINCT Mes FROM dias_del_anio WHERE anio=' . $year . ' ORDER BY Mes');
    $months = array_values(array_filter(array_map(static fn(array $row): int => (int)$row['Mes'], $monthRows), static fn(int $m): bool => $m >= 1 && $m <= 12));
    if (!$months) disp_out(['error' => 'No existen meses configurados para el año seleccionado.'], 422);
    if (!in_array($month, $months, true)) $month = $months[count($months) - 1];

    $pdo = getHanaPdo();
    $equipmentSql = <<<'SQL'
SELECT
    E."Name" AS "Code",
    COALESCE(NULLIF(E."U_ItemNameEq", ''), E."Name") AS "ItemName",
    COALESCE(E."U_Nivel_Criterio", '') AS "Criticality",
    COALESCE(E."U_RIESGO_EVA", 0) AS "Risk"
FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E
WHERE UPPER(COALESCE(E."U_Estado", 'ACTIVO')) <> 'INACTIVO'
ORDER BY
    CASE UPPER(COALESCE(E."U_Nivel_Criterio", '')) WHEN 'ALTO' THEN 1 WHEN 'MEDIO' THEN 2 WHEN 'BAJO' THEN 3 ELSE 4 END,
    E."Name"
SQL;
    $equipment = array_map(static function (array $row): array {
        return [
            'code' => trim((string)$row['Code']),
            'name' => trim((string)$row['ItemName']),
            'criticality' => strtoupper(trim((string)$row['Criticality'])),
            'risk' => round((float)$row['Risk'], 2),
        ];
    }, $pdo->query($equipmentSql)->fetchAll(PDO::FETCH_ASSOC));

    $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $last = $first->modify('last day of this month');
    $calendarStart = $first->modify('-7 days')->format('Y-m-d');
    $calendarEnd = $last->modify('+7 days')->format('Y-m-d');
    $calendarRows = find_by_sql(
        "SELECT fecha,semana,dia_semana,Mes,anio,feriado FROM dias_del_anio"
        . " WHERE fecha BETWEEN '{$calendarStart}' AND '{$calendarEnd}' ORDER BY fecha"
    );

    $targetWeeks = [];
    foreach ($calendarRows as $day) {
        if ((int)$day['anio'] === $year && (int)$day['Mes'] === $month) {
            $targetWeeks[(int)$day['semana']] = true;
        }
    }
    if (!$targetWeeks) disp_out(['error' => 'El mes no tiene semanas configuradas en dias_del_anio.'], 422);

    $weekDays = [];
    foreach ($calendarRows as $day) {
        $week = (int)$day['semana'];
        if (!isset($targetWeeks[$week])) continue;
        $day['fecha'] = substr((string)$day['fecha'], 0, 10);
        $weekDays[$week][] = $day;
    }
    ksort($weekDays, SORT_NUMERIC);
    $reportStart = min(array_column(reset($weekDays), 'fecha'));
    $lastWeekDays = end($weekDays);
    $reportEnd = max(array_column($lastWeekDays, 'fecha'));
    reset($weekDays);

    $quickSql = <<<'SQL'
SELECT
    OT."U_ItemCode" AS "Code",
    COALESCE(NULLIF(E."U_ItemNameEq", ''), OT."U_ItemCode") AS "ItemName",
    COALESCE(E."U_Nivel_Criterio", '') AS "Criticality",
    COALESCE(E."U_RIESGO_EVA", 0) AS "Risk",
    COUNT(DISTINCT OT."DocEntry") AS "OrderCount"
FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" OT
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" TM ON OT."U_TipoMant"=TM."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E ON OT."U_ItemCode"=E."Name"
WHERE COALESCE(OT."Canceled", 'N')<>'Y'
  AND UPPER(COALESCE(OT."Status", 'O'))='C'
  AND UPPER(COALESCE(E."U_Estado", 'ACTIVO'))<>'INACTIVO'
  AND (UPPER(TRIM(COALESCE(OT."U_TipoMant", '')))='CORR'
       OR UPPER(TRIM(COALESCE(TM."Name", ''))) LIKE 'CORRECT%')
  AND OT."U_FechaInicio"<=TO_DATE(?)
  AND COALESCE(OT."U_FechaFin", OT."U_FechaInicio")>=TO_DATE(?)
GROUP BY OT."U_ItemCode", E."U_ItemNameEq", E."U_Nivel_Criterio", E."U_RIESGO_EVA"
ORDER BY "OrderCount" DESC, OT."U_ItemCode"
SQL;
    $quickStmt = $pdo->prepare($quickSql);
    $quickStmt->execute([$reportEnd, $reportStart]);
    $correctiveEquipment = array_map(static function (array $row): array {
        return [
            'code' => trim((string)$row['Code']),
            'name' => trim((string)$row['ItemName']),
            'criticality' => strtoupper(trim((string)$row['Criticality'])),
            'risk' => round((float)$row['Risk'], 2),
            'orders' => (int)$row['OrderCount'],
        ];
    }, $quickStmt->fetchAll(PDO::FETCH_ASSOC));

    $meta = [
        'years' => $years, 'months' => $months, 'equipment' => $equipment,
        'correctiveEquipment' => $correctiveEquipment,
    ];
    if ($itemCode === '') disp_out(['success' => true, 'anio' => $year, 'mes' => $month, 'meta' => $meta]);

    $selected = null;
    foreach ($equipment as $candidate) {
        if (strtoupper($candidate['code']) === $itemCode) {
            $selected = $candidate;
            break;
        }
    }
    if (!$selected) disp_out(['error' => 'El equipo seleccionado no existe o está inactivo.'], 404);

    $ordersSql = <<<'SQL'
SELECT
    OT."DocEntry" AS "DocEntry",
    OT."DocNum" AS "DocNum",
    SUBSTRING(TO_VARCHAR(OT."U_FechaInicio"),1,10) AS "StartDate",
    SUBSTRING(TO_VARCHAR(COALESCE(OT."U_FechaFin", OT."U_FechaInicio")),1,10) AS "EndDate",
    OT."U_Hora_Ini" AS "StartTime",
    OT."U_Hora_Fin" AS "EndTime",
    COALESCE(OT."U_TituFor", '') AS "Title",
    COALESCE(T."Name", OT."U_Tecnico", '') AS "Technician",
    CASE
      WHEN UPPER(TRIM(COALESCE(OT."U_TipoMant", '')))='CORR'
        OR UPPER(TRIM(COALESCE(TM."Name", ''))) LIKE 'CORRECT%'
      THEN 'CORRECTIVO' ELSE 'PREVENTIVO'
    END AS "MaintenanceKind"
FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" OT
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" TM ON OT."U_TipoMant"=TM."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS" T ON OT."U_Tecnico"=T."Code"
WHERE OT."U_ItemCode"=?
  AND COALESCE(OT."Canceled", 'N')<>'Y'
  AND UPPER(COALESCE(OT."Status", 'O'))='C'
  AND (
       UPPER(TRIM(COALESCE(OT."U_TipoMant", ''))) IN ('CORR','PREV')
       OR UPPER(TRIM(COALESCE(TM."Name", ''))) LIKE 'CORRECT%'
       OR UPPER(TRIM(COALESCE(TM."Name", ''))) LIKE 'PREVENT%'
  )
  AND OT."U_FechaInicio"<=TO_DATE(?)
  AND COALESCE(OT."U_FechaFin", OT."U_FechaInicio")>=TO_DATE(?)
ORDER BY OT."U_FechaInicio", OT."DocEntry"
SQL;
    $ordersStmt = $pdo->prepare($ordersSql);
    $ordersStmt->execute([$itemCode, $reportEnd, $reportStart]);
    $orders = [];
    foreach ($ordersStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $startDate = disp_date((string)($row['StartDate'] ?? ''));
        $endDate = disp_date((string)($row['EndDate'] ?? $startDate));
        if ($startDate === '' || $endDate === '') continue;
        $orders[] = [
            'docEntry' => (int)$row['DocEntry'],
            'docNum' => (int)($row['DocNum'] ?? $row['DocEntry']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => disp_sap_time($row['StartTime'] ?? null, 7 * 60),
            'end_time' => disp_sap_time($row['EndTime'] ?? null, 16 * 60 + 20),
            'start_time_text' => sprintf('%02d:%02d', intdiv(disp_sap_time($row['StartTime'] ?? null, 7 * 60), 60), disp_sap_time($row['StartTime'] ?? null, 7 * 60) % 60),
            'end_time_text' => sprintf('%02d:%02d', intdiv(disp_sap_time($row['EndTime'] ?? null, 16 * 60 + 20), 60), disp_sap_time($row['EndTime'] ?? null, 16 * 60 + 20) % 60),
            'title' => trim((string)($row['Title'] ?? '')),
            'technician' => trim((string)($row['Technician'] ?? '')),
            'type' => strtoupper(trim((string)($row['MaintenanceKind'] ?? 'PREVENTIVO'))),
        ];
    }

    // El tiempo preventivo real proviene del mismo cruce usado por el modal
    // "Tareo relacionado": equipo + fecha comprendida en el periodo de la OT.
    $tareoSql = <<<'SQL'
SELECT
    SUBSTRING(TO_VARCHAR(T."U_FECHA"),1,10) AS "WorkDate",
    SUM(COALESCE(T."U_HORAS", 0)) AS "Hours"
FROM "SBO_TAMAING_PRODUC"."@TAMA_TAREO_OT_CIF" T
WHERE TRIM(COALESCE(T."U_EQUIPO_TM", '')) = ?
  AND SUBSTRING(TO_VARCHAR(T."U_FECHA"),1,10) BETWEEN ? AND ?
GROUP BY SUBSTRING(TO_VARCHAR(T."U_FECHA"),1,10)
SQL;
    $tareoStmt = $pdo->prepare($tareoSql);
    $tareoStmt->execute([$itemCode, $reportStart, $reportEnd]);
    $tareoMinutesByDate = [];
    foreach ($tareoStmt->fetchAll(PDO::FETCH_ASSOC) as $tareoRow) {
        $workDate = disp_date((string)($tareoRow['WorkDate'] ?? ''));
        if ($workDate !== '') $tareoMinutesByDate[$workDate] = max(0, (int)round((float)($tareoRow['Hours'] ?? 0) * 60));
    }

    $weeks = [];
    $monthlyOrderIds = [];
    $monthlyCorrectiveIds = [];
    $orderMinutes = [];
    $preventiveTareoAssigned = [];
    foreach ($weekDays as $weekNumber => $days) {
        $available = array_sum(array_map('disp_day_capacity', $days));
        $correctiveDowntime = 0;
        $preventiveDowntime = 0;
        $weekOrderIds = [];
        $weekCorrectiveIds = [];
        $holidays = [];
        foreach ($days as $day) {
            if ((int)$day['feriado'] === 1 && disp_day_capacity(array_merge($day, ['feriado' => 0])) > 0) {
                $holidays[] = $day['fecha'];
            }
            foreach ($orders as $order) {
                if ($day['fecha'] < $order['start_date'] || $day['fecha'] > $order['end_date']) continue;
                if ($order['type'] === 'CORRECTIVO') {
                    $minutes = disp_order_minutes_for_day($order, $day);
                } else {
                    // Un registro de tareo del equipo y fecha se contabiliza una sola vez,
                    // incluso si dos OT preventivas se superponen accidentalmente.
                    $minutes = isset($preventiveTareoAssigned[$day['fecha']])
                        ? 0
                        : (int)($tareoMinutesByDate[$day['fecha']] ?? 0);
                    $preventiveTareoAssigned[$day['fecha']] = true;
                }
                if ($minutes > 0) {
                    if ($order['type'] === 'CORRECTIVO') $correctiveDowntime += $minutes;
                    else $preventiveDowntime += $minutes;
                    $orderMinutes[$order['docEntry']] = ($orderMinutes[$order['docEntry']] ?? 0) + $minutes;
                }
                $weekOrderIds[$order['docEntry']] = true;
                $monthlyOrderIds[$order['docEntry']] = true;
                if ($order['type'] === 'CORRECTIVO') {
                    $weekCorrectiveIds[$order['docEntry']] = true;
                    $monthlyCorrectiveIds[$order['docEntry']] = true;
                }
            }
        }
        $correctiveDowntime = min($correctiveDowntime, $available);
        $preventiveDowntime = min($preventiveDowntime, max(0, $available - $correctiveDowntime));
        $downtime = $correctiveDowntime + $preventiveDowntime;
        $metrics = disp_metrics($available, $downtime, count($weekCorrectiveIds), count($weekOrderIds));
        $metrics['corrective_downtime'] = $correctiveDowntime;
        $metrics['preventive_downtime'] = $preventiveDowntime;
        $metrics['week'] = (int)$weekNumber;
        $metrics['start'] = min(array_column($days, 'fecha'));
        $metrics['end'] = max(array_column($days, 'fecha'));
        $metrics['holidays'] = $holidays;
        $metrics['orders'] = array_keys($weekOrderIds);
        $weeks[] = $metrics;
    }

    $totalAvailable = array_sum(array_column($weeks, 'available'));
    $totalDowntime = array_sum(array_column($weeks, 'downtime'));
    $totals = disp_metrics($totalAvailable, $totalDowntime, count($monthlyCorrectiveIds), count($monthlyOrderIds));
    $totals['corrective_downtime'] = array_sum(array_column($weeks, 'corrective_downtime'));
    $totals['preventive_downtime'] = array_sum(array_column($weeks, 'preventive_downtime'));
    // La mantenibilidad mensual representa la probabilidad observada en todas
    // las semanas del periodo, no solo la semana que tuvo una falla.
    $weeklyMaintainability = array_column($weeks, 'maintainability');
    $totals['maintainability'] = $weeklyMaintainability
        ? round(array_sum($weeklyMaintainability) / count($weeklyMaintainability), 2)
        : 100.0;

    $orderDetail = [];
    foreach ($orders as $order) {
        if (!isset($monthlyOrderIds[$order['docEntry']])) continue;
        $affectedWeeks = [];
        foreach ($weeks as $week) {
            if (in_array($order['docEntry'], $week['orders'], true)) $affectedWeeks[] = $week['week'];
        }
        $orderDetail[] = [
            'docEntry' => $order['docEntry'], 'docNum' => $order['docNum'],
            'startDate' => $order['start_date'], 'endDate' => $order['end_date'],
            'startTime' => $order['start_time_text'], 'endTime' => $order['end_time_text'],
            'minutes' => (int)($orderMinutes[$order['docEntry']] ?? 0),
            'weeks' => $affectedWeeks, 'title' => $order['title'], 'technician' => $order['technician'],
            'type' => $order['type'],
        ];
    }
    foreach ($weeks as &$week) unset($week['orders']);
    unset($week);

    disp_out([
        'success' => true, 'anio' => $year, 'mes' => $month, 'meta' => $meta,
        'equipment' => $selected, 'weeks' => $weeks, 'totals' => $totals,
        'orders' => $orderDetail,
        'rules' => [
            'weekday_minutes' => 511, 'saturday_minutes' => 295,
            'weekday_downtime_minutes' => 515, 'lunch_minutes' => 45, 'talk_minutes' => 5,
        ],
    ]);
} catch (Throwable $e) {
    error_log('Disponibilidad equipos criticos: ' . $e->getMessage());
    disp_out(['error' => 'No se pudo calcular la disponibilidad. Verifique el calendario y las OT correctivas/preventivas.'], 500);
}
