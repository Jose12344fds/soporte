<?php
/**
 * api/CarpetComUser/get_usuarios.php
 * GET — Lista usuarios activos para el selector de compartir.
 * ?area=1  → Filtra solo usuarios de la misma área que el usuario actual.
 * Responde JSON: { ok, usuarios: [...] }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/load.php';

require_permiso('CarpetComUser', 'ver');

$user     = current_user();
$userId   = (int)$user['id'];
$soloArea = ($_GET['area'] ?? '') === '1';

$areaFilter = '';
if ($soloArea && !empty($user['area'])) {
    $area       = $db->escape($user['area']);
    $areaFilter = "AND u.area = '{$area}'";
}

$res      = $db->query(
    "SELECT u.id, u.name, u.username, u.area, u.subarea
     FROM users u
     WHERE u.id != {$userId} AND u.estado = 1 {$areaFilter}
     ORDER BY u.area, u.name"
);
$usuarios = [];
while ($row = $db->fetch_assoc($res)) {
    $usuarios[] = $row;
}

echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
