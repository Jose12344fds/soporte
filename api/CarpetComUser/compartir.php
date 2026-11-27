<?php
/**
 * api/CarpetComUser/compartir.php
 * POST — Comparte un archivo con uno o varios usuarios.
 *        Body JSON: { archivo_id: int, usuarios: [int,...] }
 * POST ?accion=quitar — Quita un compartido.
 *        Body JSON: { archivo_id: int, usuario_id: int }
 * Responde JSON: { ok, ... }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/load.php';

require_permiso('CarpetComUser', 'ver');

$user    = current_user();
$userId  = (int)$user['id'];
$isAdmin = (int)($user['user_level'] ?? 99) <= 1;
$accion  = $_GET['accion'] ?? '';

$body = request_json_body();

if ($accion === 'quitar') {
    // Quitar compartido
    $archId = (int)($body['archivo_id'] ?? 0);
    $destId = (int)($body['usuario_id'] ?? 0);

    if (!$archId || !$destId) {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos.']);
        exit;
    }

    // Solo el que compartió puede quitar (o admin)
    $cond = $isAdmin ? '' : "AND compartido_por = {$userId}";
    $db->query(
        "DELETE FROM carpeta_compartidos
         WHERE archivo_id = {$archId} AND compartido_con = {$destId} {$cond}"
    );
    echo json_encode(['ok' => $db->affected_rows() > 0]);
    exit;
}

// Agregar compartido
$archId  = (int)($body['archivo_id'] ?? 0);
$destIds = array_map('intval', $body['usuarios'] ?? []);

if (!$archId || empty($destIds)) {
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos.']);
    exit;
}

// Verificar que el archivo pertenece al usuario actual (o admin)
$cond = $isAdmin ? '' : "AND user_id = {$userId}";
$chk  = $db->query("SELECT id FROM carpeta_archivos WHERE id = {$archId} {$cond}");

if ($db->num_rows($chk) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Archivo no encontrado o sin permiso.']);
    exit;
}

$nuevos = 0;
foreach ($destIds as $destId) {
    if ($destId === $userId) continue; // No compartir consigo mismo
    $db->query(
        "INSERT IGNORE INTO carpeta_compartidos (archivo_id, compartido_por, compartido_con)
         VALUES ({$archId}, {$userId}, {$destId})"
    );
    $nuevos += $db->affected_rows();
}

echo json_encode(['ok' => true, 'compartidos' => $nuevos]);
