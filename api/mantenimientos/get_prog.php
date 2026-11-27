<?php
/**
 * API: Programacion de Mantenimiento
 * - lista ocurrencias desde @TAMA_PROGDET
 * - edita plan maestro desde @TAMA_PROGMANT con ?plan_code=
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';

function gp_q(string $v): string {
    return "'" . str_replace("'", "''", trim($v)) . "'";
}

function gp_clean_control_chars(array &$rows): void {
    array_walk_recursive($rows, static function (&$value): void {
        if (!is_string($value)) return;
        $clean = preg_replace('/[\x{0080}-\x{009F}]/u', '', $value);
        if ($clean !== null) $value = $clean;
    });
}

function gp_read_plan_observation(PDO $pdo, string $planCode, int $length): string {
    if ($length <= 0) return '';

    $chunkSize = 25;
    $length = min($length, 50000);
    $partCount = (int)ceil($length / $chunkSize);
    $value = '';
    $planCodeQ = gp_q($planCode);

    for ($offset = 0; $offset < $partCount; $offset += 100) {
        $selects = [];
        $batchEnd = min($offset + 100, $partCount);
        for ($i = $offset; $i < $batchEnd; $i++) {
            $start = ($i * $chunkSize) + 1;
            $hexExpr = 'CAST(BINTOHEX(CAST(SUBSTRING("U_Observacion",' . $start . ',' . $chunkSize
                . ') AS VARBINARY(200))) AS VARCHAR(250))';
            $selects[] = 'SELECT ' . $i . ' AS "SEQ", ' . $hexExpr . ' AS "HEX"'
                . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" WHERE "Code" = ' . $planCodeQ;
        }
        $stmt = $pdo->query(implode(' UNION ALL ', $selects) . ' ORDER BY "SEQ"');
        while ($part = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hex = preg_replace('/[^0-9A-Fa-f]/', '', (string)($part['HEX'] ?? ''));
            if ($hex === '') continue;
            if (strlen($hex) % 2 !== 0) $hex = substr($hex, 0, -1);
            $decoded = hex2bin($hex);
            if ($decoded !== false) $value .= $decoded;
        }
    }
    return $value;
}

function gp_hydrate_plan_observations(PDO $pdo, array &$rows, bool $planDetail = false): void {
    $cache = [];
    foreach ($rows as &$row) {
        $planCode = (string)($planDetail ? ($row['Code'] ?? '') : ($row['PlanCode'] ?? $row['Code'] ?? ''));
        $length = (int)($row['__PlanObsLen'] ?? 0);
        if (!array_key_exists($planCode, $cache)) {
            $cache[$planCode] = gp_read_plan_observation($pdo, $planCode, $length);
        }
        if ($planDetail) $row['U_Observacion'] = $cache[$planCode];
        $row['PlanObservation'] = $cache[$planCode];
        unset($row['__PlanObsLen']);
    }
    unset($row);
}

try {
    $pdo = getHanaPdo();

    if (!empty($_GET['plan_code'])) {
        $planCode = trim((string)$_GET['plan_code']);
        $planCodeQ = gp_q($planCode);
        $sql = <<<SQL
SELECT
    T0."Code",
    T0."Name",
    T0."U_ItemCode",
    COALESCE(NULLIF(T3."U_ItemNameEq", ''), T0."U_ItemCode") AS "ItemName",
    T3."U_InventryNo" AS "InventryNo",
    T3."U_AssetSerNo" AS "AssetSerNo",
    T3."U_Marca" AS "Marca",
    T3."U_Modelo" AS "Modelo",
    T0."U_TipoMant",
    NULLIF(T0."U_Modalidad", '') AS "U_Modalidad",
    T0."U_Frecuencia",
    T0."U_TipoPeriodo",
    SUBSTRING(TO_VARCHAR(T0."U_FechaInicioPlan"), 1, 10) AS "U_FechaInicioPlan",
    SUBSTRING(TO_VARCHAR(T0."U_FechaFinPlan"), 1, 10) AS "U_FechaFinPlan",
    SUBSTRING(TO_VARCHAR(T0."U_FechaProg"), 1, 10) AS "U_FechaProg",
    T0."U_Estado",
    NULL AS "U_Observacion",
    CAST(LENGTH(T0."U_Observacion") AS VARCHAR(20)) AS "__PlanObsLen",
    T0."U_GeneraAuto",
    T0."U_AnioBase",
    T0."U_UltPeriodoGen",
    T0."U_Critico",
    T0."U_Activo",
    SUBSTRING(TO_VARCHAR(T0."CreateDate"), 1, 10) AS "CreateDate"
FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" T0
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T3 ON T0."U_ItemCode" = T3."Name"
WHERE T0."Code" = {$planCodeQ}
SQL;
        $rows = $pdo->query($sql)->fetchAll();
        gp_clean_control_chars($rows);
        gp_hydrate_plan_observations($pdo, $rows, true);
        ob_end_clean();
        echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    $filters = [];
    if (!empty($_GET['code'])) {
        $filters[] = 'T1."Code" = ' . gp_q((string)$_GET['code']);
    }
    if (!empty($_GET['estado'])) {
        $filters[] = 'COALESCE(T1."U_Estado", \'Pendiente\') = ' . gp_q((string)$_GET['estado']);
    }
    if (!empty($_GET['itemcode'])) {
        $filters[] = 'T0."U_ItemCode" = ' . gp_q((string)$_GET['itemcode']);
    }

    $where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

    $sql = <<<SQL
SELECT
    T1."Code",
    T0."Code" AS "PlanCode",
    T0."Name",
    T0."U_ItemCode",
    COALESCE(NULLIF(T3."U_ItemNameEq", ''), T0."U_ItemCode") AS "ItemName",
    T3."U_InventryNo" AS "InventryNo",
    T3."U_AssetSerNo" AS "AssetSerNo",
    T3."U_Marca" AS "Marca",
    T3."U_Modelo" AS "Modelo",
    T3."U_UBICACION" AS "Ubicacion",
    T0."U_TipoMant",
    NULLIF(T0."U_Modalidad", '') AS "U_Modalidad",
    COALESCE(T2."Name", T0."U_TipoMant") AS "TipoMantName",
    SUBSTRING(TO_VARCHAR(T1."U_FechaProg"), 1, 10) AS "U_FechaProg",
    T0."U_AnioBase" AS "U_AnioBase",
    T1."U_Anio" AS "U_Anio",
    T0."U_Frecuencia",
    T1."U_TipoPeriodo",
    T1."U_PeriodoNum",
    T1."U_Semana",
    T1."U_Mes",
    CASE
        WHEN T1."U_Estado" = 'Cancelado' THEN 'Cancelado'
        WHEN OT."Status" = 'C'
             AND COALESCE(SUBSTRING(TO_VARCHAR(OT."U_FechaFin"), 1, 10), SUBSTRING(TO_VARCHAR(OT."UpdateDate"), 1, 10)) > SUBSTRING(TO_VARCHAR(T1."U_FechaProg"), 1, 10)
            THEN 'Ejecutado fuera de fecha'
        WHEN OT."Status" = 'C' THEN 'Ejecutado'
        WHEN SUBSTRING(TO_VARCHAR(T1."U_FechaFin"), 1, 10) < SUBSTRING(TO_VARCHAR(CURRENT_DATE), 1, 10)
            THEN 'Vencido'
        ELSE COALESCE(T1."U_Estado", 'Pendiente')
    END AS "U_Estado",
    OT."DocEntry" AS "U_OTDocEntry",
    OT."DocNum" AS "U_OTDocNum",
    OT."Status" AS "OTStatus",
    OT."U_Tecnico" AS "OTTecnico",
    COALESCE(TEC."Name", OT."U_Tecnico") AS "OTTecnicoName",
    T1."U_Observacion" AS "U_Observacion",
    T1."U_Observacion" AS "OccurrenceObservation",
    NULL AS "PlanObservation",
    CAST(LENGTH(T0."U_Observacion") AS VARCHAR(20)) AS "__PlanObsLen",
    'Y' AS "IsOccurrence",
    SUBSTRING(TO_VARCHAR(T1."CreateDate"), 1, 10) AS "CreateDate"
FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" T1
INNER JOIN "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" T0 ON T1."U_ProgMant" = T0."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" T2 ON T0."U_TipoMant" = T2."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T3 ON T0."U_ItemCode" = T3."Name"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" OT
    ON T1."Code" = OT."U_ProgDet" AND COALESCE(OT."Canceled", 'N') <> 'Y'
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS" TEC ON OT."U_Tecnico" = TEC."Code"
{$where}
ORDER BY T1."U_Anio" DESC, T1."U_PeriodoNum" DESC, T1."U_FechaProg" DESC
SQL;

    $rows = $pdo->query($sql)->fetchAll();
    if (!$rows) {
        $fallbackSql = <<<SQL
SELECT
    T0."Code" AS "Code",
    T0."Code" AS "PlanCode",
    T0."Name",
    T0."U_ItemCode",
    COALESCE(NULLIF(T3."U_ItemNameEq", ''), T0."U_ItemCode") AS "ItemName",
    T3."U_InventryNo" AS "InventryNo",
    T3."U_AssetSerNo" AS "AssetSerNo",
    T3."U_Marca" AS "Marca",
    T3."U_Modelo" AS "Modelo",
    T3."U_UBICACION" AS "Ubicacion",
    T0."U_TipoMant",
    NULLIF(T0."U_Modalidad", '') AS "U_Modalidad",
    COALESCE(T2."Name", T0."U_TipoMant") AS "TipoMantName",
    SUBSTRING(TO_VARCHAR(T0."U_FechaProg"), 1, 10) AS "U_FechaProg",
    T0."U_AnioBase" AS "U_AnioBase",
    T0."U_AnioBase" AS "U_Anio",
    T0."U_Frecuencia",
    T0."U_TipoPeriodo",
    NULL AS "U_PeriodoNum",
    NULL AS "U_Semana",
    NULL AS "U_Mes",
    COALESCE(T0."U_Estado", 'Pendiente') AS "U_Estado",
    NULL AS "U_OTDocEntry",
    NULL AS "U_OTDocNum",
    NULL AS "OTStatus",
    NULL AS "OTTecnico",
    NULL AS "OTTecnicoName",
    NULL AS "U_Observacion",
    NULL AS "OccurrenceObservation",
    NULL AS "PlanObservation",
    CAST(LENGTH(T0."U_Observacion") AS VARCHAR(20)) AS "__PlanObsLen",
    'N' AS "IsOccurrence",
    SUBSTRING(TO_VARCHAR(T0."CreateDate"), 1, 10) AS "CreateDate"
FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" T0
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" T2 ON T0."U_TipoMant" = T2."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T3 ON T0."U_ItemCode" = T3."Name"
{$where}
ORDER BY T0."U_FechaProg" DESC, T0."Code" DESC
SQL;
        $rows = $pdo->query($fallbackSql)->fetchAll();
    }
    gp_clean_control_chars($rows);
    gp_hydrate_plan_observations($pdo, $rows);
    ob_end_clean();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
