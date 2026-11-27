<?php
/**
 * api/CarpetComUser/get_admin_archivos.php
 * GET — Solo admins. Retorna TODOS los archivos con info de compartidos.
 * Responde JSON: { ok, archivos: [...] }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/load.php';

require_permiso('CarpetComUser', 'eliminar');

// Obtener todos los archivos con nombre de usuario
$archivos = [];
$res = $db->query(
    "SELECT a.*, u.name AS nombre_usuario
     FROM carpeta_archivos a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC"
);
while ($row = $db->fetch_assoc($res)) {
    $archivos[] = $row;
}

// Para cada archivo, obtener con quién está compartido
foreach ($archivos as &$a) {
    $archId = (int)$a['id'];
    $compRes = $db->query(
        "SELECT u.name FROM carpeta_compartidos c
         JOIN users u ON u.id = c.compartido_con
         WHERE c.archivo_id = {$archId}"
    );
    $a['compartidos'] = [];
    while ($c = $db->fetch_assoc($compRes)) {
        $a['compartidos'][] = $c['name'];
    }
}
unset($a);

echo json_encode(['ok' => true, 'archivos' => $archivos]);
