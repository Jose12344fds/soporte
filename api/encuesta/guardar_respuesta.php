<?php
/**
 * api/encuesta/guardar_respuesta.php
 * POST — Guarda las respuestas de un usuario a una encuesta.
 * Recibe JSON: { id_encuesta, id_usuario, respuestas: [{id_pregunta, id_opcion?, texto_respuesta?},...] }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        http_response_code(400);
        echo json_encode(['error' => 'Cuerpo JSON inválido']);
        exit;
    }

    $idEncuesta = (int)($body['id_encuesta'] ?? 0);
    $idUsuario  = (int)($body['id_usuario']  ?? 0);
    $respuestas = $body['respuestas']         ?? [];

    if (!$idEncuesta || !$idUsuario) {
        http_response_code(422);
        echo json_encode(['error' => 'id_encuesta e id_usuario son obligatorios']);
        exit;
    }

    if (ConsultasEncuesta::yaRespondio($idEncuesta, $idUsuario)) {
        echo json_encode(['ok' => false, 'msg' => 'Ya respondiste esta encuesta']);
        exit;
    }

    $pdo = ConexionEncuesta::conectar();
    $pdo->beginTransaction();

    $idRespuesta = ConsultasEncuesta::insertRespuesta($idEncuesta, $idUsuario);

    foreach ($respuestas as $r) {
        $idPregunta  = (int)($r['id_pregunta']      ?? 0);
        $idOpcion    = isset($r['id_opcion'])  && $r['id_opcion']  ? (int)$r['id_opcion']  : null;
        $textoResp   = isset($r['texto_respuesta'])  && $r['texto_respuesta'] ? trim($r['texto_respuesta']) : null;
        if (!$idPregunta) continue;
        ConsultasEncuesta::insertDetalle($idRespuesta, $idPregunta, $idOpcion, $textoResp);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'id_respuesta' => $idRespuesta]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
