<?php
/** Proteccion comun para las APIs del modulo HH_META. */
require_once dirname(__DIR__) . '/includes/load.php';

header('Content-Type: application/json; charset=utf-8');

if (!$session->isUserLoggedIn(true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion no valida.']);
    exit;
}

if (!tiene_permiso('hh-meta', 'ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permiso para usar HH META.']);
    exit;
}
