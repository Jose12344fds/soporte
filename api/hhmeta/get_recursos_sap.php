<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * API: Obtener procesos SAP por ItemCode
 * Params GET: itemCode (requerido)
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json; charset=utf-8');

$itemCode = isset($_GET['itemCode']) ? trim($_GET['itemCode']) : null;

try {
    if (empty($itemCode)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro itemCode requerido']);
        exit;
    }

    require_once __DIR__ . '/ConsultasHana.php';

    $consultas = new Consultas();
    $recursos = $consultas->obtenerRecursosSAP($itemCode);

    ob_end_clean();
    echo json_encode(is_array($recursos) ? $recursos : [], JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file'  => basename($e->getFile()),
        'line'  => $e->getLine()
    ]);
}
restore_error_handler();
