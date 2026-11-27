<?php
/**
 * api/encuesta/eliminar_respuesta.php
 * Elimina la respuesta de un usuario en una encuesta para que pueda volver a responder.
 * POST { id_encuesta, id_usuario }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $idEncuesta = (int)($_POST['id_encuesta'] ?? 0);
    $idUsuario  = (int)($_POST['id_usuario']  ?? 0);

    if (!$idEncuesta || !$idUsuario) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros requeridos']);
        exit;
    }

    ConsultasEncuesta::deleteRespuestaUsuario($idEncuesta, $idUsuario);
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
