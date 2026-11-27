<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

function normalizar_ot($value) {
    $value = strtoupper(preg_replace('/\s+/', '', trim((string)$value)));
    if (ctype_digit($value)) return 'OTC-' . str_pad($value, 6, '0', STR_PAD_LEFT);
    if (preg_match('/^OTC-?(\d+)$/', $value, $m)) return 'OTC-' . str_pad($m[1], 6, '0', STR_PAD_LEFT);
    return $value;
}

function sql_value($value) {
    return "'" . str_replace("'", "''", trim((string)$value)) . "'";
}

try {
    $ot = normalizar_ot($_GET['ot'] ?? '');
    $item = trim((string)($_GET['item'] ?? ''));
    if ($ot === '' || $item === '') {
        throw new InvalidArgumentException('OT e item son obligatorios');
    }

    $dsn = 'odbc:Driver={HDBODBC};ServerNode=192.168.4.33:30015;Database=SBO_TAMAING_PRODUC;CHAR_AS_UTF8=1';
    $pdo = new PDO($dsn, 'B1ADMIN', 'Au$tr4l14', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    $otSql = sql_value($ot);
    $itemSql = sql_value($item);
    $digits = preg_replace('/\D+/', '', $ot);
    $short = $digits !== '' ? (string)((int)$digits) : '';
    $candidates = array_values(array_unique(array_filter([$ot, str_replace('-', '', $ot), $digits, $short])));
    $candidateSql = implode(',', array_map('sql_value', $candidates));

    $hist = $pdo->query(
        'SELECT "OT", "CodItem", "Anio", "DescItemOC", "EstadoL", "CantComp" '
        . 'FROM "SBO_TAMAING_PRODUC"."TAMA_REPHISTCOMP_WEB" '
        . "WHERE \"OT\" = $otSql AND TRIM(\"CodItem\") = $itemSql"
    )->fetchAll();

    $ofitem = $pdo->query(
        'SELECT "CodProy", "CodItemHijo", "NomHijo", "CantPlanTot", "CantPendTot" '
        . 'FROM "SBO_TAMAING_PRODUC"."TAMA_OFDetalleXItem" '
        . "WHERE \"CodProy\" = $otSql AND TRIM(\"CodItemHijo\") = $itemSql"
    )->fetchAll();

    $solicitud = $pdo->query(
        'SELECT "PROYECTO", "DOCNUM", "NUM ARTICULO", "DESC ARTICULO", "UNI", "CANTIDAD", "FECHA" '
        . 'FROM "SBO_TAMAING_PRODUC"."TAMA_COMPRA_SOLICITU_ABIER" '
        . "WHERE UPPER(TRIM(\"PROYECTO\")) IN ($candidateSql) AND TRIM(\"NUM ARTICULO\") = $itemSql"
    )->fetchAll();

    $consumo = $pdo->query(
        'SELECT "CodOT", "Cod. Elemento", "Desc. Elemento", "Fec. Contab", "Semana", "Tipo", "Nro.OF", "Cantidad" '
        . 'FROM "SBO_TAMAING_PRODUC"."TAMA_CONSUMOS" '
        . "WHERE UPPER(TRIM(\"CodOT\")) IN ($candidateSql) AND TRIM(\"Cod. Elemento\") = $itemSql"
    )->fetchAll();

    // Diagnóstico: mismo artículo en otros proyectos, útil cuando PROYECTO no coincide con la OT indicada.
    $otrosProyectos = $pdo->query(
        'SELECT "PROYECTO", "NUM ARTICULO", MAX("UNI") AS "UNI", SUM("CANTIDAD") AS "CANTIDAD" '
        . 'FROM "SBO_TAMAING_PRODUC"."TAMA_COMPRA_SOLICITU_ABIER" '
        . "WHERE TRIM(\"NUM ARTICULO\") = $itemSql AND UPPER(TRIM(\"PROYECTO\")) NOT IN ($candidateSql) "
        . 'GROUP BY "PROYECTO", "NUM ARTICULO" ORDER BY "PROYECTO"'
    )->fetchAll();

    $sum = static function($rows, $field) {
        return array_reduce($rows, static fn($total, $row) => $total + (float)($row[$field] ?? 0), 0.0);
    };
    $first = static fn($rows, $field) => $rows ? trim((string)($rows[0][$field] ?? '')) : '';

    $consolidado = [
        'OT' => $ot,
        'CodItem' => $item,
        'DescItemOC' => $first($hist, 'DescItemOC') ?: ($first($ofitem, 'NomHijo') ?: ($first($solicitud, 'DESC ARTICULO') ?: $first($consumo, 'Desc. Elemento'))),
        'CantComp' => $sum($hist, 'CantComp'),
        'CantPlanTot' => $sum($ofitem, 'CantPlanTot'),
        'CantPendTot' => $sum($ofitem, 'CantPendTot'),
        'Unidad' => $first($solicitud, 'UNI'),
        'CantSolicitudAbierta' => $sum($solicitud, 'CANTIDAD'),
        'CantConsumida' => $sum($consumo, 'Cantidad'),
    ];

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'ot' => $ot,
        'item' => $item,
        'formatos_proyecto_evaluados' => $candidates,
        'historial_compras' => $hist,
        'detalle_of' => $ofitem,
        'solicitud_abierta' => $solicitud,
        'consumos' => $consumo,
        'mismo_articulo_otros_proyectos' => $otrosProyectos,
        'consolidado' => $consolidado,
    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
