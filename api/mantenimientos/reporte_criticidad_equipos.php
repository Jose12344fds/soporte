<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once __DIR__ . '/conexion_hana.php';

function criticidad_report_out(array $data, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!$session->isUserLoggedIn(false)) criticidad_report_out(['error' => 'No autorizado.'], 401);
if (!tiene_permiso('mantenimientos', 'ver')) criticidad_report_out(['error' => 'Sin permiso.'], 403);

try {
    $pdo = getHanaPdo();
    $sql = <<<'SQL'
SELECT
    E."Code", COALESCE(NULLIF(TRIM(E."Name"), ''), E."Code") AS "ItemCode",
    COALESCE(NULLIF(TRIM(E."U_ItemNameEq"), ''), E."Name") AS "ItemName",
    COALESCE(NULLIF(TRIM(E."U_Cod_Tipo"), ''), 'SIN-CODIGO') AS "FamilyCode",
    COALESCE(NULLIF(TRIM(E."U_TIP_EMH"), ''), 'SIN FAMILIA') AS "FamilyName",
    E."U_Nivel_Criterio" AS "Nivel", E."U_RIESGO_EVA" AS "Riesgo",
    E."U_F1_EVA" AS "F1", E."U_R_EVA" AS "R",
    E."U_SHA_EVA" AS "SHA", E."U_IC_EVA" AS "IC", E."U_IP_EVA" AS "IP",
    E."U_BM_EVA" AS "BM", E."U_CM_EVA" AS "CM"
FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E
ORDER BY COALESCE(E."U_Cod_Tipo", ''), E."Name"
SQL;
    $equipment = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $planRows = $pdo->query(
        'SELECT "U_ItemCode" AS "ItemCode", "U_Frecuencia" AS "Frecuencia"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
        . ' WHERE COALESCE("U_Activo", \'Y\') <> \'N\' AND COALESCE("U_ItemCode", \'\') <> \'\''
    )->fetchAll(PDO::FETCH_ASSOC);
    $frequencies = [];
    foreach ($planRows as $plan) {
        $item = trim((string)($plan['ItemCode'] ?? ''));
        $frequency = trim((string)($plan['Frecuencia'] ?? ''));
        if ($item !== '' && $frequency !== '') $frequencies[$item][$frequency] = true;
    }

    $tareoRows = $pdo->query(
        'SELECT TRIM("U_EQUIPO_TM") AS "ItemCode", COUNT(*) AS "Registros",'
        . ' SUM(COALESCE("U_HORAS", 0)) AS "Horas"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_TAREO_OT_CIF"'
        . ' WHERE COALESCE(TRIM("U_EQUIPO_TM"), \'\') <> \'\''
        . ' GROUP BY TRIM("U_EQUIPO_TM")'
    )->fetchAll(PDO::FETCH_ASSOC);
    $tareo = [];
    foreach ($tareoRows as $row) {
        $tareo[trim((string)$row['ItemCode'])] = [
            'registros' => (int)($row['Registros'] ?? 0),
            'horas' => round((float)($row['Horas'] ?? 0), 2),
        ];
    }

    $weekMap = [
        'SEMANAL' => 1, 'QUINCENAL' => 2, 'MENSUAL' => 4,
        'BIMESTRAL' => 8, 'TRIMESTRAL' => 13, 'CUATRIMESTRAL' => 17,
        'SEMESTRAL' => 26, 'ANUAL' => 52,
    ];
    $rows = [];
    foreach ($equipment as $row) {
        $scores = [];
        foreach (['F1', 'R', 'SHA', 'IC', 'IP', 'BM', 'CM'] as $factor) {
            $value = (float)($row[$factor] ?? 0);
            $scores[$factor] = $value >= 1 && $value <= 5 ? $value : 0;
        }
        $evaluated = count(array_filter($scores, static fn(float $v): bool => $v > 0)) === 7;
        $failureRelevance = $evaluated ? ($scores['F1'] + $scores['R']) / 2 : 0;
        $consequence = $evaluated
            ? 1 + ((5 - 1) / (5 - 1)) * (0.2 * ($scores['SHA'] + $scores['IC'] + $scores['IP'] + $scores['BM'] + $scores['CM']) - 1)
            : 0;
        $risk = $evaluated ? ($failureRelevance * $consequence / 25) * 100 : 0;
        $level = !$evaluated ? 'SIN EVALUAR' : ($risk > 36.99 ? 'ALTO' : ($risk >= 16.99 ? 'MEDIO' : 'BAJO'));
        $item = trim((string)($row['ItemCode'] ?? ''));
        $itemFrequencies = array_keys($frequencies[$item] ?? []);
        natcasesort($itemFrequencies);
        $frequencyWeeks = [];
        foreach ($itemFrequencies as $frequency) {
            $weeks = $weekMap[strtoupper(trim($frequency))] ?? null;
            $frequencyWeeks[] = $weeks === null ? $frequency : $frequency . ' (' . $weeks . ')';
        }
        $time = $tareo[$item] ?? ['registros' => 0, 'horas' => 0];
        $rows[] = [
            'familia_codigo' => trim((string)($row['FamilyCode'] ?? '')) ?: 'SIN-CODIGO',
            'familia_nombre' => trim((string)($row['FamilyName'] ?? '')) ?: 'SIN FAMILIA',
            'codigo' => $item,
            'nombre' => trim((string)($row['ItemName'] ?? '')),
            'criticidad' => $level,
            'riesgo' => round($risk, 2),
            'falla_relevancia' => round($failureRelevance, 2),
            'consecuencia' => round($consequence, 2),
            'frecuencia' => implode(', ', $itemFrequencies),
            'frecuencia_semanas' => implode(', ', $frequencyWeeks),
            'tareo_registros' => $time['registros'],
            'tareo_horas' => $time['horas'],
            'F1' => $scores['F1'] ?: null, 'R' => $scores['R'] ?: null,
            'SHA' => $scores['SHA'] ?: null, 'IC' => $scores['IC'] ?: null,
            'IP' => $scores['IP'] ?: null, 'BM' => $scores['BM'] ?: null,
            'CM' => $scores['CM'] ?: null,
        ];
    }

    criticidad_report_out(['success' => true, 'rows' => $rows]);
} catch (Throwable $e) {
    error_log('Reporte criticidad equipos: ' . $e->getMessage());
    criticidad_report_out(['error' => 'No se pudo generar el reporte de criticidad.'], 500);
}
