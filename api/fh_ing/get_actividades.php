<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    $rows = find_by_sql("SELECT id, nombre, orden FROM fh_ing_actividades WHERE activo = 1 ORDER BY orden ASC");
    ob_end_clean();
    echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
