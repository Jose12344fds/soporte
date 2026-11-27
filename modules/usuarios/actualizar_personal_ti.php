<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

header('Content-Type: application/json; charset=utf-8');
require_permiso('usuarios', 'editar');
ensure_users_tipo_registro();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userId = (int)($input['user_id'] ?? 0);
$isItStaff = !empty($input['es_personal_ti']) ? 1 : 0;
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Usuario invalido.']);
    exit;
}

$result = $db->query("SELECT id,tipo_registro FROM users WHERE id={$userId} LIMIT 1");
$user = $db->fetch_assoc($result);
if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'El usuario no existe.']);
    exit;
}
if ($isItStaff && ($user['tipo_registro'] ?? 'USUARIO') !== 'USUARIO') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Un registro de correo no puede ser responsable de mantenimiento.']);
    exit;
}

$db->query("UPDATE users SET es_personal_ti={$isItStaff} WHERE id={$userId} LIMIT 1");
echo json_encode(['success' => true, 'es_personal_ti' => $isItStaff]);
?>
