<?php
/**
 * API para obtener grupos dinámicamente
 */

header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/load.php');

$user = current_user();
if (!$user || $user['user_level'] > 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

global $db;

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Obtener todos los grupos
if ($action === 'obtener_grupos') {
    $result = $db->query("SELECT id, group_name, group_level, group_status FROM user_groups WHERE group_status = 1 ORDER BY group_level");
    $grupos = [];
    
    if ($result && $db->num_rows($result) > 0) {
        while ($row = $db->fetch_assoc($result)) {
            $grupos[] = [
                'id' => $row['id'],
                'group_name' => $row['group_name'],
                'group_level' => $row['group_level']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'grupos' => $grupos]);
    exit;
}

// Obtener permisos de todos los grupos para un módulo
if ($action === 'obtener_todos' && isset($_POST['modulo'])) {
    $modulo = $_POST['modulo'];
    $result = [];
    
    // Obtener todos los grupos
    $grupos_query = $db->query("SELECT id, group_name, group_level FROM user_groups WHERE group_status = 1 ORDER BY group_level");
    
    if ($grupos_query && $db->num_rows($grupos_query) > 0) {
        while ($grupo = $db->fetch_assoc($grupos_query)) {
            $group_level = $grupo['group_level'];
            
            $sql = "SELECT puede_ver, puede_crear, puede_editar, puede_eliminar 
                    FROM permisos_modulos 
                    WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}
                    LIMIT 1";
            
            $perms = $db->query($sql);
            
            if ($perms && $db->num_rows($perms) > 0) {
                $row = $db->fetch_assoc($perms);
                $result[$group_level] = [
                    'group_name' => $grupo['group_name'],
                    'puede_ver' => (bool)$row['puede_ver'],
                    'puede_crear' => (bool)$row['puede_crear'],
                    'puede_editar' => (bool)$row['puede_editar'],
                    'puede_eliminar' => (bool)$row['puede_eliminar']
                ];
            } else {
                $result[$group_level] = [
                    'group_name' => $grupo['group_name'],
                    'puede_ver' => false,
                    'puede_crear' => false,
                    'puede_editar' => false,
                    'puede_eliminar' => false
                ];
            }
        }
    }
    
    echo json_encode($result);
    exit;
}

// Obtener permisos de un grupo específico para un módulo
$modulo = isset($_POST['modulo']) ? trim($_POST['modulo']) : '';
$group_level = isset($_POST['group_level']) ? (int)$_POST['group_level'] : 0;

if (!$modulo || $group_level <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

$sql = "SELECT puede_ver, puede_crear, puede_editar, puede_eliminar 
        FROM permisos_modulos 
        WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}
        LIMIT 1";

$result = $db->query($sql);

if ($result && $db->num_rows($result) > 0) {
    $row = $db->fetch_assoc($result);
    echo json_encode([
        'puede_ver' => (bool)$row['puede_ver'],
        'puede_crear' => (bool)$row['puede_crear'],
        'puede_editar' => (bool)$row['puede_editar'],
        'puede_eliminar' => (bool)$row['puede_eliminar']
    ]);
} else {
    echo json_encode([
        'puede_ver' => false,
        'puede_crear' => false,
        'puede_editar' => false,
        'puede_eliminar' => false
    ]);
}
