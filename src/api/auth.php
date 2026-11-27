<?php
/**
 * API de Autenticación
 * Sistema de Soporte TI - TAMA
 */
header('Content-Type: application/json');

require_once(__DIR__ . '/../../includes/load.php');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'login':
        if ($method === 'POST') {
            handleLogin();
        }
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkSession();
        break;
    default:
        jsonResponse(false, 'Acción no válida');
}

/**
 * Manejar inicio de sesión
 */
function handleLogin() {
    global $session, $db;
    
    // Obtener datos JSON o POST
    $input = request_json_body();
    if (!$input) {
        $input = $_POST;
    }
    
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    
    // Validar campos
    if (empty($username) || empty($password)) {
        jsonResponse(false, 'Usuario y contraseña son requeridos');
        return;
    }
    
    // Sanitizar
    $username = $db->escape($username);
    
    // Autenticar
    $user_id = authenticate($username, $password);
    
    if ($user_id) {
        // Crear sesión
        $session->login($user_id);
        updateLastLogIn($user_id);
        
        // Obtener datos del usuario
        $user = find_by_id('users', $user_id);
        
        jsonResponse(true, 'Inicio de sesión exitoso', [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'level' => $user['user_level']
            ],
            'redirect' => '/soporte/home.php'
        ]);
    } else {
        // Log de intento fallido (opcional)
        jsonResponse(false, 'Usuario y/o contraseña incorrectos');
    }
}

/**
 * Manejar cierre de sesión
 */
function handleLogout() {
    global $session;
    $session->logout();
    jsonResponse(true, 'Sesión cerrada', ['redirect' => '/soporte/index.php']);
}

/**
 * Verificar sesión activa
 */
function checkSession() {
    global $session;
    
    if ($session->isUserLoggedIn(true)) {
        $user = current_user();
        jsonResponse(true, 'Sesión activa', [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'level' => $user['user_level']
            ]
        ]);
    } else {
        jsonResponse(false, 'No hay sesión activa');
    }
}

/**
 * Respuesta JSON estándar
 */
function jsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
