<?php
/**
 * API para obtener permisos de un módulo
 */
require_once(__DIR__ . '/../includes/load.php');
require_once(__DIR__ . '/../config/app.php');

header('Content-Type: application/json');

try {
    $modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';

    if (empty($modulo)) {
        throw new Exception('Módulo no especificado');
    }

    global $db;
    
    if (!$db) {
        throw new Exception('Conexión a BD no disponible');
    }

    // Obtener permisos del módulo
    $sql = "SELECT group_level, puede_ver, puede_crear, puede_editar, puede_eliminar 
            FROM permisos_modulos 
            WHERE modulo = '{$db->escape($modulo)}'
            ORDER BY group_level ASC";

    $result = $db->query($sql);
    $permisos = [];

    if ($result && $db->num_rows($result) > 0) {
        while ($row = $db->fetch_assoc($result)) {
            $permisos[] = [
                'group_level' => (int)$row['group_level'],
                'puede_ver' => (bool)$row['puede_ver'],
                'puede_crear' => (bool)$row['puede_crear'],
                'puede_editar' => (bool)$row['puede_editar'],
                'puede_eliminar' => (bool)$row['puede_eliminar']
            ];
        }
    }

    echo json_encode(['success' => true, 'permisos' => $permisos]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
