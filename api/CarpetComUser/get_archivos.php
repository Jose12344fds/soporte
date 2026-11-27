<?php
/**
 * api/CarpetComUser/get_archivos.php
 * GET — Retorna los archivos del usuario y los compartidos con él.
 * Responde JSON: { ok, mis_archivos: [...], compartidos: [...] }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/load.php';

require_permiso('CarpetComUser', 'ver');

$user   = current_user();
$userId = (int)$user['id'];

// Mis archivos subidos
$misArchivos = [];
$res = $db->query(
    "SELECT a.*, u.name AS nombre_usuario, u.area AS area_usuario
     FROM carpeta_archivos a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.user_id = {$userId}
     ORDER BY a.created_at DESC"
);
while ($row = $db->fetch_assoc($res)) {
    $misArchivos[] = $row;
}

// Archivos compartidos conmigo
$compartidos = [];
$res2 = $db->query(
    "SELECT a.*, u.name AS nombre_usuario, u.area AS area_usuario,
            p.name AS compartido_por_nombre
     FROM carpeta_compartidos c
     JOIN carpeta_archivos a ON a.id = c.archivo_id
     JOIN users u ON u.id = a.user_id
     JOIN users p ON p.id = c.compartido_por
     WHERE c.compartido_con = {$userId}
     ORDER BY c.created_at DESC"
);
while ($row = $db->fetch_assoc($res2)) {
    $compartidos[] = $row;
}

echo json_encode([
    'ok'           => true,
    'mis_archivos' => $misArchivos,
    'compartidos'  => $compartidos,
]);
