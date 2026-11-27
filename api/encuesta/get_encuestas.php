<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/** api/encuesta/get_encuestas.php — Lista todas las encuestas */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $solo_activas = isset($_GET['activas']) && $_GET['activas'] == '1';
    $data = $solo_activas
        ? ConsultasEncuesta::getEncuestasActivas()
        : ConsultasEncuesta::getEncuestas();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
