<?php
/**
 * API: Historial de Compras por OT - Modulo Estructuracion
 * Vista SAP HANA: SBO_TAMAING_PRODUC.TAMA_REPHISTCOMP_WEB
 * GET ?ot=4682         -> busca OTC-004682
 * GET ?ot=OTC-004682   -> codigo completo
 * GET ?ot=...&debug=1  -> diagnostico
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    $otRaw = isset($_GET['ot']) ? trim($_GET['ot']) : '';

    if ($otRaw === '') {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Parametro ot requerido']);
        exit;
    }

    $otNormalized = strtoupper(preg_replace('/\s+/', '', $otRaw));

    // Acepta 4682, OTC-004682, OTC004682 y variantes con espacios.
    if (ctype_digit($otNormalized)) {
        $otCod = 'OTC-' . str_pad($otNormalized, 6, '0', STR_PAD_LEFT);
    } elseif (preg_match('/^OTC-?(\d+)$/', $otNormalized, $matches)) {
        $otCod = 'OTC-' . str_pad($matches[1], 6, '0', STR_PAD_LEFT);
    } else {
        $otCod = $otNormalized;
    }

    // Conexion HANA via ODBC
    $dsn = 'odbc:Driver={HDBODBC};ServerNode=192.168.4.33:30015;Database=SBO_TAMAING_PRODUC;CHAR_AS_UTF8=1';
    $pdo = new PDO($dsn, 'B1ADMIN', 'Au$tr4l14', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);

    // Inline escaped value (no ? porque EMULATE_PREPARES no cotiza el valor)
    $otEsc = str_replace("'", "''", $otCod);
    $sql = 'SELECT   MAX(T0."Anio")  AS "Anio", T0."CodItem",   MAX(T0."DescItemOC") AS "DescItemOC",  MAX(T0."EstadoL")    AS "EstadoL", T0."OT",  SUM(T0."CantComp")   AS "CantComp"'
         . ' FROM "SBO_TAMAING_PRODUC"."TAMA_REPHISTCOMP_WEB" T0'
         . " WHERE T0.\"OT\" = '$otEsc'"
         . ' GROUP BY T0."CodItem", T0."OT"'
         . ' ORDER BY "Anio" DESC, T0."CodItem"';

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Modo debug
    if (isset($_GET['debug'])) {
        ob_end_clean();
        echo json_encode([
            'ot'     => $otCod,
            'sql'    => $sql,
            'total'  => count($rows),
            'keys'   => !empty($rows) ? array_keys($rows[0]) : [],
            'sample' => !empty($rows) ? $rows[0] : null,
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    ob_end_clean();
    echo json_encode([
        'ot'    => $otCod,
        'total' => count($rows),
        'data'  => $rows,
    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file'  => basename($e->getFile()),
        'line'  => $e->getLine(),
    ]);
}