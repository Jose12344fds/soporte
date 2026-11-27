<?php
/**
 * API para obtener permisos de un módulo para un nivel específico
 * Returns JSON
 */

header('Content-Type: application/json');

// Incluir configuración
require_once(__DIR__ . '/../includes/load.php');

// Verificar que el usuario está logueado y es admin
$user = current_user();
if (!$user || $user['user_level'] > 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

global $db;

// Obtener parámetros
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$modulo = isset($_POST['modulo']) ? trim($_POST['modulo']) : '';
$group_level = isset($_POST['group_level']) ? (int)$_POST['group_level'] : 0;

if (!$modulo) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro modulo requerido']);
    exit;
}

// ACCIÓN 1: Obtener permisos de todos los grupos para un módulo
if ($action === 'obtener_todos') {
    $result = [];
    
    // Obtener dinámicamente todos los grupos activos
    $grupos_query = $db->query("SELECT id, group_name, group_level FROM user_groups WHERE group_status = 1");
    
    if ($grupos_query && $db->num_rows($grupos_query) > 0) {
        while ($grupo = $db->fetch_assoc($grupos_query)) {
            $nivel = $grupo['group_level'];
            
            $sql = "SELECT puede_ver, puede_crear, puede_editar, puede_eliminar 
                    FROM permisos_modulos 
                    WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$nivel}
                    LIMIT 1";
            
            $query = $db->query($sql);
            
            if ($query && $db->num_rows($query) > 0) {
                $row = $db->fetch_assoc($query);
                $result[$nivel] = [
                    'group_name' => $grupo['group_name'],
                    'puede_ver' => (bool)$row['puede_ver'],
                    'puede_crear' => (bool)$row['puede_crear'],
                    'puede_editar' => (bool)$row['puede_editar'],
                    'puede_eliminar' => (bool)$row['puede_eliminar']
                ];
            } else {
                // Si no existe, devolver permisos vacíos
                $result[$nivel] = [
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

// ACCIÓN 2: Obtener permisos de un nivel específico para un módulo
if ($group_level <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro group_level requerido']);
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
    // Si no existe, devolver permisos vacíos
    echo json_encode([
        'puede_ver' => false,
        'puede_crear' => false,
        'puede_editar' => false,
        'puede_eliminar' => false
    ]);
}
