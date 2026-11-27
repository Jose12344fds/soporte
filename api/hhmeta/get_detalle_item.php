<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * API: Obtener detalle de procesos por Código de Producto
 * Params GET: codProducto (requerido)
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json; charset=utf-8');

$codProducto = isset($_GET['codProducto']) ? trim($_GET['codProducto']) : null;

try {
    if (empty($codProducto)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro codProducto requerido']);
        exit;
    }

    require_once __DIR__ . '/ConsultasHana.php';

    $consultas = new Consultas();
    $detalle = $consultas->obtenerDetalleProducto($codProducto);

    ob_end_clean();
    echo json_encode(is_array($detalle) ? $detalle : [], JSON_PARTIAL_OUTPUT_ON_ERROR);

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
