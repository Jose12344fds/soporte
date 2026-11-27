<?php
/**
 * API Router - Users Endpoint
 * Maneja todas las operaciones CRUD para usuarios
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Incluir conexión a base de datos
require_once __DIR__ . '/../../includes/load.php';

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Manejar preflight request
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if ($action === 'datatable') {
                handleDatatable();
            } elseif (isset($_GET['id'])) {
                getUser($_GET['id']);
            } else {
                getAllUsers();
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            createUser($data);
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            updateUser($data);
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            deleteUser($data['id']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Obtener todos los usuarios
 */
function getAllUsers() {
    global $db;
    $sql = "SELECT id, name, username, user_level, status, last_login FROM users ORDER BY name";
    $result = $db->query($sql);
    $users = $db->while_loop($result);
    echo json_encode(['data' => $users, 'success' => true]);
}

/**
 * Obtener usuario por ID
 */
function getUser($id) {
    global $db;
    $id = (int) $id;
    $sql = "SELECT * FROM users WHERE id = {$id} LIMIT 1";
    $result = $db->query($sql);
    $user = $db->fetch_assoc($result);
    
    if ($user) {
        unset($user['password']); // No enviar password
        echo json_encode(['data' => $user, 'success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
}

/**
 * Manejar petición DataTables server-side
 */
function handleDatatable() {
    global $db;
    
    // Parámetros de DataTables
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Columnas ordenables
    $columns = ['id', 'name', 'username', 'user_level', 'status', 'last_login'];
    $orderColumn = isset($_POST['order'][0]['column']) ? $columns[$_POST['order'][0]['column']] : 'id';
    $orderDir = isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
    
    // Query base
    $where = '';
    if (!empty($search)) {
        $search = $db->escape($search);
        $where = "WHERE name LIKE '%{$search}%' OR username LIKE '%{$search}%'";
    }
    
    // Contar total
    $totalQuery = "SELECT COUNT(*) as total FROM users";
    $totalResult = $db->query($totalQuery);
    $totalRow = $db->fetch_assoc($totalResult);
    $recordsTotal = $totalRow['total'];
    
    // Contar filtrados
    $filteredQuery = "SELECT COUNT(*) as total FROM users {$where}";
    $filteredResult = $db->query($filteredQuery);
    $filteredRow = $db->fetch_assoc($filteredResult);
    $recordsFiltered = $filteredRow['total'];
    
    // Obtener datos
    $sql = "SELECT id, name, username, user_level, status, last_login 
            FROM users {$where} 
            ORDER BY {$orderColumn} {$orderDir} 
            LIMIT {$start}, {$length}";
    $result = $db->query($sql);
    $data = $db->while_loop($result);
    
    // Formatear respuesta
    $response = [
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ];
    
    echo json_encode($response);
}

/**
 * Crear nuevo usuario
 */
function createUser($data) {
    global $db;
    
    // Validar datos requeridos
    if (empty($data['name']) || empty($data['username']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos requeridos']);
        return;
    }
    
    // Verificar si el usuario ya existe
    $username = $db->escape($data['username']);
    $check = $db->query("SELECT id FROM users WHERE username = '{$username}'");
    if ($db->num_rows($check) > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'El nombre de usuario ya existe']);
        return;
    }
    
    // Preparar datos
    $name = $db->escape($data['name']);
    $password = sha1($data['password']);
    $userLevel = isset($data['user_level']) ? (int) $data['user_level'] : 2;
    $status = isset($data['status']) ? (int) $data['status'] : 1;
    
    $sql = "INSERT INTO users (name, username, password, user_level, status) 
            VALUES ('{$name}', '{$username}', '{$password}', {$userLevel}, {$status})";
    
    if ($db->query($sql)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario creado correctamente',
            'id' => $db->insert_id()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el usuario']);
    }
}

/**
 * Actualizar usuario existente
 */
function updateUser($data) {
    global $db;
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario requerido']);
        return;
    }
    
    $id = (int) $data['id'];
    $updates = [];
    
    if (!empty($data['name'])) {
        $updates[] = "name = '" . $db->escape($data['name']) . "'";
    }
    if (!empty($data['username'])) {
        $updates[] = "username = '" . $db->escape($data['username']) . "'";
    }
    if (!empty($data['password'])) {
        $updates[] = "password = '" . sha1($data['password']) . "'";
    }
    if (isset($data['user_level'])) {
        $updates[] = "user_level = " . (int) $data['user_level'];
    }
    if (isset($data['status'])) {
        $updates[] = "status = " . (int) $data['status'];
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay datos para actualizar']);
        return;
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = {$id}";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el usuario']);
    }
}

/**
 * Eliminar usuario
 */
function deleteUser($id) {
    global $db;
    $id = (int) $id;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario inválido']);
        return;
    }
    
    $sql = "DELETE FROM users WHERE id = {$id} LIMIT 1";
    
    if ($db->query($sql) && $db->affected_rows() > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado o no se pudo eliminar']);
    }
}
