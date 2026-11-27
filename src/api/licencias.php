<?php
/**
 * API Endpoint - Licencias
 * Maneja consultas de licencias
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
    
    if ($method === 'GET' && $action === 'obtener_all') {
        $response = obtenerTodasLicencias();
    } 
    elseif ($method === 'GET' && $action === 'obtener') {
        $id = (int)($_GET['id'] ?? 0);
        $response = obtenerLicencia($id);
    }
    elseif ($method === 'POST' && $action === 'agregar') {
        $response = agregarLicencia();
    }
    elseif ($method === 'PUT' && $action === 'editar') {
        $response = editarLicencia();
    }
    elseif ($method === 'DELETE' && $action === 'eliminar') {
        $response = eliminarLicencia();
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
 * Obtener todas las licencias
 */
function obtenerTodasLicencias() {
    $licencias = find_all('licencias');
    
    return [
        'success' => true,
        'data' => $licencias
    ];
}

/**
 * Obtener una licencia por ID
 */
function obtenerLicencia($id) {
    global $db;
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'ID inválido'];
    }
    
    $sql = "SELECT * FROM licencias WHERE IdLicencias = {$id}";
    $result = $db->query($sql);
    
    if (!$result || $db->num_rows($result) === 0) {
        return ['success' => false, 'message' => 'Licencia no encontrada'];
    }
    
    $licencia = $db->fetch_array($result);
    
    return [
        'success' => true,
        'data' => $licencia
    ];
}

/**
 * Agregar nueva licencia
 */
function agregarLicencia() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $tipo = trim($input['Tipo_Licencias'] ?? '');
    $serial = trim($input['Serial'] ?? '');
    $nombre = trim($input['Nombre'] ?? '');
    $fecha = trim($input['Fecha'] ?? '');
    $utilizado = trim($input['Utilizado'] ?? 'Activo');
    $nom_equipo = trim($input['NomEquipo'] ?? '');
    $observaciones = trim($input['Observaciones'] ?? '');
    
    // Validar campos requeridos
    if (empty($tipo) || empty($serial) || empty($nombre)) {
        return ['success' => false, 'message' => 'Tipo, Serial y Nombre son requeridos'];
    }
    
    // Escapar
    $tipo = $db->escape($tipo);
    $serial = $db->escape($serial);
    $nombre = $db->escape($nombre);
    $fecha = $db->escape($fecha);
    $utilizado = $db->escape($utilizado);
    $nom_equipo = $db->escape($nom_equipo);
    $observaciones = $db->escape($observaciones);
    
    // Insertar
    $insert_sql = "INSERT INTO licencias (Tipo_Licencias, Serial, Nombre, Fecha, Utilizado, NomEquipo, Observaciones) 
                   VALUES ('{$tipo}', '{$serial}', '{$nombre}', '{$fecha}', '{$utilizado}', '{$nom_equipo}', '{$observaciones}')";
    
    if (!$db->query($insert_sql)) {
        return ['success' => false, 'message' => 'Error en BD: ' . $db->error];
    }
    
    return [
        'success' => true,
        'message' => 'Licencia agregada correctamente'
    ];
}

/**
 * Editar licencia
 */
function editarLicencia() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = (int)($input['IdLicencias'] ?? 0);
    $tipo = trim($input['Tipo_Licencias'] ?? '');
    $serial = trim($input['Serial'] ?? '');
    $nombre = trim($input['Nombre'] ?? '');
    $fecha = trim($input['Fecha'] ?? '');
    $utilizado = trim($input['Utilizado'] ?? '');
    $nom_equipo = trim($input['NomEquipo'] ?? '');
    $observaciones = trim($input['Observaciones'] ?? '');
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'ID inválido'];
    }
    
    // Validar campos
    if (empty($tipo) || empty($serial) || empty($nombre)) {
        return ['success' => false, 'message' => 'Tipo, Serial y Nombre son requeridos'];
    }
    
    // Escapar
    $tipo = $db->escape($tipo);
    $serial = $db->escape($serial);
    $nombre = $db->escape($nombre);
    $fecha = $db->escape($fecha);
    $utilizado = $db->escape($utilizado);
    $nom_equipo = $db->escape($nom_equipo);
    $observaciones = $db->escape($observaciones);
    
    // Actualizar
    $update_sql = "UPDATE licencias 
                   SET Tipo_Licencias = '{$tipo}', Serial = '{$serial}', Nombre = '{$nombre}', 
                       Fecha = '{$fecha}', Utilizado = '{$utilizado}', NomEquipo = '{$nom_equipo}', 
                       Observaciones = '{$observaciones}'
                   WHERE IdLicencias = {$id}";
    
    if (!$db->query($update_sql)) {
        return ['success' => false, 'message' => 'Error en BD: ' . $db->error];
    }
    
    return [
        'success' => true,
        'message' => 'Licencia actualizada correctamente'
    ];
}

/**
 * Eliminar licencia
 */
function eliminarLicencia() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['IdLicencias'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'ID inválido'];
    }
    
    // Verificar que existe
    $result = $db->query("SELECT IdLicencias FROM licencias WHERE IdLicencias = {$id}");
    if (!$result || $db->num_rows($result) === 0) {
        return ['success' => false, 'message' => 'Licencia no encontrada'];
    }
    
    // Eliminar
    if (!$db->query("DELETE FROM licencias WHERE IdLicencias = {$id}")) {
        return ['success' => false, 'message' => 'Error en BD: ' . $db->error];
    }
    
    return [
        'success' => true,
        'message' => 'Licencia eliminada correctamente'
    ];
}
