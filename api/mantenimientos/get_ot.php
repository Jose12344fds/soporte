<?php
/**
 * API: Órdenes de Trabajo — @TAMA_ORDTRABAJO
 * GET ?docentry=X  → OT específica
 * GET ?estado=O|C  → filtro por estado (O=Abierto, C=Cerrado)
 * GET ?tipo=PREV   → filtro por tipo mantenimiento
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';

function ot_read_nclob(PDO $pdo, string $column, int $docentry, int $length): string {
    if (!in_array($column, ['U_TrabajoRealizado', 'U_Observacion'], true) || $length <= 0) {
        return '';
    }
    // El PDO_ODBC instalado corrompe NVARCHAR largos con Unicode. Se transporta
    // cada fragmento como hexadecimal ASCII y se reconstruye en PHP.
    $chunkSize = 25;
    $maxLength = 20000;
    $length = min($length, $maxLength);
    $partCount = (int)ceil($length / $chunkSize);
    $value = '';
    for ($offset = 0; $offset < $partCount; $offset += 100) {
        $selects = [];
        $batchEnd = min($offset + 100, $partCount);
        for ($i = $offset; $i < $batchEnd; $i++) {
            $start = ($i * $chunkSize) + 1;
            $hexExpr = 'CAST(BINTOHEX(CAST(SUBSTRING("' . $column . '",' . $start . ',' . $chunkSize
                . ') AS VARBINARY(200))) AS VARCHAR(250))';
            $selects[] = 'SELECT ' . $i . ' AS "SEQ", ' . $hexExpr . ' AS "HEX"'
                . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" WHERE "DocEntry" = ' . $docentry;
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

try {
    $pdo = getHanaPdo();

    $filters = [];
    if (!empty($_GET['docentry'])) {
        $v = (int) $_GET['docentry'];
        $filters[] = "T0.\"DocEntry\" = {$v}";
    }
    if (!empty($_GET['estado'])) {
        $v = str_replace("'", "''", $_GET['estado']);
        $filters[] = "T0.\"Status\" = '$v'";
    }
    if (!empty($_GET['tipo'])) {
        $v = str_replace("'", "''", $_GET['tipo']);
        $filters[] = "T0.\"U_TipoMant\" = '$v'";
    }

    $where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

    // La modalidad NO va en este SQL: se consulta aparte más abajo (ver $modalidades).
    $sql = <<<SQL
SELECT
    T0."DocEntry",
    T0."DocNum",
    T0."Status",
    T0."Canceled",
    T0."CreateDate",
    T0."U_ProgMant",
    T0."U_ProgDet",
    T0."U_ItemCode",
    COALESCE(T5."U_ItemNameEq", T0."U_ItemCode")                AS "ItemName",
    T5."U_InventryNo"                                           AS "InventryNo",
    T5."U_AssetSerNo"                                           AS "AssetSerNo",
    T5."U_Marca"                                                AS "Marca",
    T5."U_Modelo"                                               AS "Modelo",
    T0."U_TipoMant",
    COALESCE(T3."Name", T0."U_TipoMant")         AS "TipoMantName",
    T0."U_Tecnico",
    T0."U_DetalleSVT",
    COALESCE(T2."Name", T0."U_Tecnico")          AS "TecnicoName",
    T2."U_Especialidad"                          AS "TecnicoEsp",
    T0."U_FechaInicio",
    T0."U_FechaFin",
    T0."U_Hora_Ini",
    T0."U_Hora_Fin",
    T0."U_TituFor",
    CASE
        WHEN T0."U_ItemCode" IS NULL OR T0."U_FechaInicio" IS NULL THEN 0
        ELSE (
            SELECT COUNT(*)
            FROM "SBO_TAMAING_PRODUC"."@TAMA_TAREO_OT_CIF" TT
            WHERE TRIM(COALESCE(TT."U_EQUIPO_TM", '')) = TRIM(T0."U_ItemCode")
              AND SUBSTRING(TO_VARCHAR(TT."U_FECHA"), 1, 10)
                  BETWEEN SUBSTRING(TO_VARCHAR(T0."U_FechaInicio"), 1, 10)
                      AND SUBSTRING(TO_VARCHAR(COALESCE(T0."U_FechaFin", T0."U_FechaInicio")), 1, 10)
        )
    END AS "TareoCount",
    LENGTH(T0."U_TrabajoRealizado") AS "__TrabajoLen",
    LENGTH(T0."U_Observacion") AS "__ObservLen"
FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" T0
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS"  T2 ON T0."U_Tecnico" = T2."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT"  T3 ON T0."U_TipoMant" = T3."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T5 ON T0."U_ItemCode" = T5."Name"
{$where}
ORDER BY T0."DocEntry" DESC
SQL;

    $rows = $pdo->query($sql)->fetchAll();

    // Modalidad del plan (@TAMA_PROGMANT). Se consulta aparte y dentro de try/catch:
    // si falla, la lista de OT se sigue devolviendo con U_Modalidad vacía.
    // Se castea a VARCHAR(10) porque el PDO_ODBC corrompe NVARCHAR/NCLOB con Unicode
    // y la modalidad (M / MR) es solo ASCII.
    $modalidades = [];
    try {
        $codes = [];
        foreach ($rows as $r) {
            $c = trim((string)($r['U_ProgMant'] ?? ''));
            if ($c !== '') $codes[$c] = true;
        }
        $codes = array_keys($codes);
        for ($i = 0; $i < count($codes); $i += 500) {
            $in = [];
            foreach (array_slice($codes, $i, 500) as $c) {
                $in[] = "'" . str_replace("'", "''", $c) . "'";
            }
            $sqlMod = 'SELECT CAST(PM."Code" AS VARCHAR(50)) AS "CODE", '
                . 'CAST(SUBSTRING(TO_NVARCHAR(PM."U_Modalidad"), 1, 10) AS VARCHAR(10)) AS "MODAL" '
                . 'FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" PM '
                . 'WHERE CAST(PM."Code" AS VARCHAR(50)) IN (' . implode(',', $in) . ')';
            $stmtMod = $pdo->query($sqlMod);
            while ($m = $stmtMod->fetch(PDO::FETCH_ASSOC)) {
                $modalidades[trim((string)$m['CODE'])] = strtoupper(trim((string)$m['MODAL']));
            }
        }
    } catch (Throwable $eMod) {
        $modalidades = [];
    }

    foreach ($rows as &$row) {
        $progKey = trim((string)($row['U_ProgMant'] ?? ''));
        $modal = $modalidades[$progKey] ?? '';
        $row['U_Modalidad'] = $modal !== '' ? $modal : null;
        $trabajoLen = (int)($row['__TrabajoLen'] ?? 0);
        $observLen = (int)($row['__ObservLen'] ?? 0);
        if (!empty($_GET['docentry'])) {
            $entry = (int)($row['DocEntry'] ?? 0);
            $row['U_TrabajoRealizado'] = ot_read_nclob($pdo, 'U_TrabajoRealizado', $entry, $trabajoLen);
            $row['U_Observacion'] = ot_read_nclob($pdo, 'U_Observacion', $entry, $observLen);
        } else {
            $row['U_TrabajoRealizado'] = '';
            $row['U_Observacion'] = '';
        }
        unset($row['__TrabajoLen'], $row['__ObservLen']);
    }
    unset($row);

    ob_end_clean();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
