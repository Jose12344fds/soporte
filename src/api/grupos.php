<?php
/**
 * API Endpoint - Grupos (User Groups)
 * Maneja agregar y editar grupos de usuarios
 */

// Iniciar output buffering
ob_start();

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Cargar dependencias
$base_path = dirname(dirname(dirname(__FILE__)));
require_once $base_path . '/includes/load.php';

// Respuesta por default
$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    // Limpiar buffer
    ob_end_clean();
    
    // Obtener método y acción
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    if ($method === 'POST' && $action === 'agregar') {
        $response = agregarGrupo();
    } 
    elseif ($method === 'PUT' && $action === 'editar') {
        $response = editarGrupo();
    }
    elseif ($method === 'DELETE' && $action === 'eliminar') {
        $response = eliminarGrupo();
    }
    else {
        $response = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Agregar nuevo grupo
 */
function agregarGrupo() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $group_name = trim($input['group_name'] ?? '');
    $group_level = (int)($input['group_level'] ?? 0);
    $group_status = isset($input['group_status']) ? 1 : 0;
    
    if (empty($group_name)) {
        return ['success' => false, 'message' => 'El nombre es requerido'];
    }
    if ($group_level <= 0) {
        return ['success' => false, 'message' => 'El nivel debe ser mayor a 0'];
    }
    
    // Escapar
    $group_name = $db->escape($group_name);
    
    // Verificar si existe
    $sql = "SELECT id FROM user_groups WHERE group_level = {$group_level}";
    $result = $db->query($sql);
    
    if ($result && $db->num_rows($result) > 0) {
        return ['success' => false, 'message' => 'El nivel ' . $group_level . ' ya existe'];
    }
    
    // Insertar
    $insert_sql = "INSERT INTO user_groups (group_name, group_level, group_status) 
                   VALUES ('{$group_name}', {$group_level}, {$group_status})";
    
    if (!$db->query($insert_sql)) {
        return ['success' => false, 'message' => 'Error en BD: ' . $db->error];
    }
    
    // Inicializar permisos
    if (function_exists('inicializar_permisos_grupo')) {
        inicializar_permisos_grupo($group_level);
    }
    
    return [
        'success' => true,
        'message' => 'Grupo agregado correctamente'
    ];
}

/**
 * Editar grupo
 */
function editarGrupo() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $group_id = (int)($input['group_id'] ?? 0);
    $group_name = trim($input['group_name'] ?? '');
    $group_status = isset($input['group_status']) ? 1 : 0;
    
    if ($group_id <= 0) {
        return ['success' => false, 'message' => 'ID inválido'];
    }
    if (empty($group_name)) {
        return ['success' => false, 'message' => 'El nombre es requerido'];
    }
    
    // Escapar
    $group_name = $db->escape($group_name);
    
    // Verificar que existe
    $result = $db->query("SELECT id FROM user_groups WHERE id = {$group_id}");
    if (!$result || $db->num_rows($result) === 0) {
        return ['success' => false, 'message' => 'El grupo no existe'];
    }
    
    // Actualizar
    $update_sql = "UPDATE user_groups 
                   SET group_name = '{$group_name}', group_status = {$group_status} 
                   WHERE id = {$group_id}";
    
    if (!$db->query($update_sql)) {
        return ['success' => false, 'message' => 'Error en BD: ' . $db->error];
    }
    
    return [
        'success' => true,
        'message' => 'Grupo actualizado correctamente'
    ];
}

/**
 * Eliminar grupo
 */
function eliminarGrupo() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $group_id = (int)($input['group_id'] ?? 0);
    
    if ($group_id <= 0) {
        return ['success' => false, 'message' => 'ID inválido'];
    }
    
    // Verificar
    $result = $db->query("SELECT group_level FROM user_groups WHERE id = {$group_id}");
    if (!$result || $db->num_rows($result) === 0) {
        return ['success' => false, 'message' => 'El grupo no existe'];
    }
    
    $row = $db->fetch_array($result);
    if ($row['group_level'] == 1) {
        return ['success' => false, 'message' => 'No se puede eliminar el grupo Admin'];
    }
    
    // Eliminar
    if (!$db->query("DELETE FROM user_groups WHERE id = {$group_id}")) {
        return ['success' => false, 'message' => 'Error en BD: ' . $db->error];
    }
    
    return [
        'success' => true,
        'message' => 'Grupo eliminado correctamente'
    ];
}
