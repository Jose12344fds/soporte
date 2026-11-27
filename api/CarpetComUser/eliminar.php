<?php
/**
 * api/CarpetComUser/eliminar.php
 * POST — Elimina un archivo del disco y de la BD.
 *        Solo el propietario o un admin puede eliminar.
 * Body JSON: { id: int }
 * Responde JSON: { ok }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';

require_permiso('CarpetComUser', 'ver');

$user    = current_user();
$userId  = (int)$user['id'];
$isAdmin = (int)($user['user_level'] ?? 99) <= 1;

$body = request_json_body();

// Soporte bulk: { ids: [1,2,3] }  o single: { id: 1 }
$ids = [];
if (!empty($body['ids']) && is_array($body['ids'])) {
    $ids = array_map('intval', $body['ids']);
} elseif (!empty($body['id'])) {
    $ids = [(int)$body['id']];
}

$ids = array_filter($ids); // elimina ceros
if (empty($ids)) {
    echo json_encode(['ok' => false, 'error' => 'ID no válido.']);
    exit;
}

$deletedCount = 0;
foreach ($ids as $archId) {
    $cond = $isAdmin ? '' : "AND user_id = {$userId}";
    $res  = $db->query("SELECT * FROM carpeta_archivos WHERE id = {$archId} {$cond}");

    if ($db->num_rows($res) === 0) {
        continue; // sin permiso o no existe, saltar
    }

    $file    = $db->fetch_assoc($res);
    $ownerId = (int)$file['user_id'];

    $filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
              . DIRECTORY_SEPARATOR . 'CarpetComUser'
              . DIRECTORY_SEPARATOR . $ownerId
              . DIRECTORY_SEPARATOR . $file['nombre_guardado'];

    // Eliminar de la BD (FK ON DELETE CASCADE elimina carpeta_compartidos)
    $db->query("DELETE FROM carpeta_archivos WHERE id = {$archId}");

    // Eliminar del disco
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
    $deletedCount++;
}

echo json_encode(['ok' => true, 'deleted' => $deletedCount]);
