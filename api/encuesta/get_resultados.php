<?php
/**
 * api/encuesta/get_resultados.php — Resultados de una encuesta para el reporte
 * GET ?id=<id_encuesta>
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }

    $encuesta    = ConsultasEncuesta::getEncuesta($id);
    $opciones    = ConsultasEncuesta::getResultadosOpciones($id);
    $abiertos    = ConsultasEncuesta::getResultadosAbiertos($id);
    $participantes = ConsultasEncuesta::getParticipantes($id);

    echo json_encode([
        'encuesta'      => $encuesta,
        'opciones'      => $opciones,
        'abiertos'      => $abiertos,
        'participantes' => $participantes,
        'total'         => count($participantes),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
