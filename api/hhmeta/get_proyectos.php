<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * API: Obtener todos los proyectos SAP HANA
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Capturar warnings/notices como excepciones
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/ConsultasHana.php';
    
    $consultas = new Consultas();
    $proyectos = $consultas->obtenerProyectos4();
    
    ob_end_clean();
    echo json_encode(is_array($proyectos) ? $proyectos : [], JSON_PARTIAL_OUTPUT_ON_ERROR);
    
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    error_log('HH_META get_proyectos: ' . $e->getMessage());
    echo json_encode(['error' => 'No se pudieron cargar los proyectos.']);
}
restore_error_handler();
