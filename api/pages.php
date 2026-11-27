<?php
/**
 * API para gestión de páginas (subpáginas)
 * Maneja CRUD básico para module_pages
 */

require_once(__DIR__ . '/../includes/load.php');
require_once(__DIR__ . '/../config/app.php');

header('Content-Type: application/json');

global $db;

// Verificar sesión y permisos
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');

try {
    switch ($action) {
        case 'obtener':
            obtenerPage();
            break;
        
        case 'obtener_por_modulo':
            obtenerPagesPorModulo();
            break;
        
        case 'agregar':
            agregarPage();
            break;
        
        case 'actualizar':
            actualizarPage();
            break;
        
        case 'eliminar':
            eliminarPage();
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Obtener una página por ID
 */
function obtenerPage() {
    global $db;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }
    
    $sql = "SELECT mp.*, m.nombre as modulo_nombre FROM module_pages mp
            LEFT JOIN modulos m ON mp.modulo_slug = m.slug
            WHERE mp.id = $id";
    
    $result = $db->query($sql);
    
    if ($result && $db->num_rows($result) > 0) {
        $page = $db->fetch_assoc($result);
        $page['url'] = app_relative_url($page['url']);
        echo json_encode(['success' => true, 'page' => $page]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Página no encontrada']);
    }
}

/**
 * Obtener todas las páginas de un módulo
 */
function obtenerPagesPorModulo() {
    global $db;
    
    $modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
    
    if (!$modulo) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Módulo requerido']);
        return;
    }
    
    $modulo_esc = $db->escape($modulo);
    $sql = "SELECT id, modulo_slug, parent_id, title, url, icon, orden, estado 
            FROM module_pages 
            WHERE modulo_slug = '$modulo_esc' AND estado = 1
            ORDER BY parent_id, orden";
    
    $result = $db->query($sql);
    $pages = [];
    
    if ($result && $db->num_rows($result) > 0) {
        while ($row = $db->fetch_assoc($result)) {
            $row['url'] = app_relative_url($row['url']);
            $pages[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'pages' => $pages]);
}

/**
 * Agregar una nueva página
 */
function agregarPage() {
    global $db;
    
    $modulo_slug = isset($_POST['modulo_slug']) ? trim($_POST['modulo_slug']) : '';
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : 'NULL';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $url = isset($_POST['url']) ? app_relative_url(trim($_POST['url'])) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : 'fas fa-file';
    $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 1;
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
    
    // Validar campos requeridos
    if (!$modulo_slug || !$title || !$url) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Campos requeridos faltantes']);
        return;
    }
    
    // Validar que el módulo exista
    $modulo_esc = $db->escape($modulo_slug);
    $sql_check = "SELECT id FROM modulos WHERE slug = '$modulo_esc'";
    $result_check = $db->query($sql_check);
    
    if (!$result_check || $db->num_rows($result_check) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Módulo no existe']);
        return;
    }
    
    // Insertar página
    $title_esc = $db->escape($title);
    $url_esc = $db->escape($url);
    $icon_esc = $db->escape($icon);
    
    $sql = "INSERT INTO module_pages (modulo_slug, parent_id, title, url, icon, orden, estado, created_at, updated_at)
            VALUES ('$modulo_esc', $parent_id, '$title_esc', '$url_esc', '$icon_esc', $orden, $estado, NOW(), NOW())";
    
    if ($db->query($sql)) {
        $page_id = $db->insert_id();
        echo json_encode(['success' => true, 'message' => 'Página agregada exitosamente', 'id' => $page_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al insertar: ' . $db->error]);
    }
}

/**
 * Actualizar una página existente
 */
function actualizarPage() {
    global $db;
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : 'NULL';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $url = isset($_POST['url']) ? app_relative_url(trim($_POST['url'])) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : 'fas fa-file';
    $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 1;
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
    
    // Validar
    if (!$id || !$title || !$url) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }
    
    // Verificar que la página exista
    $sql_check = "SELECT id FROM module_pages WHERE id = $id";
    $result_check = $db->query($sql_check);
    
    if (!$result_check || $db->num_rows($result_check) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Página no encontrada']);
        return;
    }
    
    // Actualizar
    $title_esc = $db->escape($title);
    $url_esc = $db->escape($url);
    $icon_esc = $db->escape($icon);
    
    $sql = "UPDATE module_pages 
            SET parent_id = $parent_id, 
                title = '$title_esc', 
                url = '$url_esc', 
                icon = '$icon_esc', 
                orden = $orden, 
                estado = $estado,
                updated_at = NOW()
            WHERE id = $id";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Página actualizada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $db->error]);
    }
}

/**
 * Eliminar una página
 */
function eliminarPage() {
    global $db;
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }
    
    // Verificar si la página existe
    $sql_check = "SELECT id FROM module_pages WHERE id = $id";
    $result_check = $db->query($sql_check);
    
    if (!$result_check || $db->num_rows($result_check) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Página no encontrada']);
        return;
    }
    
    // Eliminar (CASCADE deletes manejado por BD)
    $sql = "DELETE FROM module_pages WHERE id = $id";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Página eliminada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $db->error]);
    }
}

?>
