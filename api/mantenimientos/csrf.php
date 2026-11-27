<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';

while (ob_get_level() > 0) ob_end_clean();
if (!$session->isUserLoggedIn(false)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'La sesion ha vencido.']);
    exit;
}

echo json_encode([
    'success' => true,
    'token' => csrf_token(),
], JSON_UNESCAPED_UNICODE);
