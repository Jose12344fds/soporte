<?php
/**
 * Módulo Usuarios - Eliminar Usuario
 * Sistema de Soporte TI - TAMA
 */
require_once(__DIR__ . '/../../includes/load.php');

require_permiso('usuarios', 'eliminar');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current = current_user();

// No permitir eliminarse a sí mismo
if ($user_id == $current['id']) {
    $session->msg('d', 'No puede eliminarse a sí mismo.');
    redirect('index.php', false);
}

if ($user_id > 0) {
    $sql = "DELETE FROM users WHERE id = '{$user_id}' LIMIT 1";
    if ($db->query($sql) && $db->affected_rows() === 1) {
        $session->msg('s', 'Usuario eliminado exitosamente.');
    } else {
        $session->msg('d', 'Error al eliminar usuario.');
    }
} else {
    $session->msg('d', 'ID de usuario inválido.');
}

redirect('index.php', false);
