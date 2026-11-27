<?php
require_once __DIR__ . '/../../includes/load.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_permiso('capacidad_ti', 'ver');

echo json_encode([
    'success' => true,
    'token' => csrf_token(),
], JSON_UNESCAPED_UNICODE);
