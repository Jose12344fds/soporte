<?php
/**
 * api/encuesta/toggle_activa.php — Activa / desactiva una encuesta
 * POST { id_encuesta }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $id = (int)($_POST['id_encuesta'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    ConsultasEncuesta::toggleActiva($id);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
