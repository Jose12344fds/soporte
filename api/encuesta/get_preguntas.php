<?php
/** api/encuesta/get_preguntas.php — Preguntas + opciones de una encuesta */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de encuesta requerido']);
        exit;
    }
    echo json_encode(ConsultasEncuesta::getPreguntasConOpciones($id), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
