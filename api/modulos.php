<?php
/**
 * API para operaciones de módulos
 */
require_once(__DIR__ . '/../includes/load.php');
require_once(__DIR__ . '/../config/app.php');

header('Content-Type: application/json');

try {
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

    global $db;
    
    if (!$db) {
        throw new Exception('Conexión a BD no disponible');
    }

    // OBTENER MÓDULO
    if ($action === 'obtener' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        $sql = "SELECT id, slug, nombre, descripcion, icono, estado, orden FROM modulos WHERE id = {$id}";
        $result = $db->query($sql);
        
        if ($result && $db->num_rows($result) > 0) {
            $modulo = $db->fetch_assoc($result);
            echo json_encode(['success' => true, 'modulo' => $modulo]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Módulo no encontrado']);
        }
        exit;
    }

    // AGREGAR MÓDULO
    if ($action === 'agregar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $icono = isset($_POST['icono']) ? trim($_POST['icono']) : 'fas fa-cube';
        $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 1;
        
        // Validar
        $errores = [];
        if (strlen($slug) < 3) $errores[] = "El slug debe tener al menos 3 caracteres";
        if (strlen($nombre) < 3) $errores[] = "El nombre debe tener al menos 3 caracteres";
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) $errores[] = "Solo minúsculas, números, guiones y guiones bajos";
        
        if (empty($errores)) {
            // Verificar que no exista
            $check = $db->query("SELECT id FROM modulos WHERE slug = '{$db->escape($slug)}' LIMIT 1");
            if ($check && $db->num_rows($check) > 0) {
                echo json_encode(['success' => false, 'message' => "El slug '{$slug}' ya existe"]);
            } else {
                $sql = "INSERT INTO modulos (slug, nombre, descripcion, icono, estado, orden) 
                        VALUES ('{$db->escape($slug)}', '{$db->escape($nombre)}', '{$db->escape($descripcion)}', '{$db->escape($icono)}', 1, {$orden})";
                
                if ($db->query($sql)) {
                    echo json_encode(['success' => true, 'message' => "Módulo '{$nombre}' creado"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al crear el módulo']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
        }
        exit;
    }

    // ACTUALIZAR MÓDULO
    if ($action === 'actualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $icono = isset($_POST['icono']) ? trim($_POST['icono']) : 'fas fa-cube';
        $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 1;
        $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;
        
        // Validar
        if (strlen($nombre) < 3) {
            echo json_encode(['success' => false, 'message' => 'El nombre debe tener al menos 3 caracteres']);
            exit;
        }
        
        if ($id > 0) {
            $sql = "UPDATE modulos SET nombre = '{$db->escape($nombre)}', 
                    descripcion = '{$db->escape($descripcion)}', 
                    icono = '{$db->escape($icono)}',
                    estado = {$estado},
                    orden = {$orden}
                    WHERE id = {$id}";
            
            if ($db->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Módulo actualizado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de módulo inválido']);
        }
        exit;
    }

    // ELIMINAR MÓDULO
    if ($action === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';

        // Compatibilidad con bases antiguas cuyos registros tienen ID 0.
        // Se elimina solo la fila seleccionada, no todas sus copias.
        if ($id === 0 && preg_match('/^[a-z0-9_-]+$/', $slug)) {
            $slug_escaped = $db->escape($slug);
            $select = $db->query("SELECT nombre FROM modulos WHERE id = 0 AND slug = '{$slug_escaped}' LIMIT 1");

            if (!$select || $db->num_rows($select) === 0) {
                echo json_encode(['success' => false, 'message' => 'Modulo no encontrado']);
                exit;
            }

            $row = $db->fetch_assoc($select);
            $modulo_nombre = $row['nombre'];
            $sql = "DELETE FROM modulos WHERE id = 0 AND slug = '{$slug_escaped}' LIMIT 1";

            if ($db->query($sql)) {
                echo json_encode(['success' => true, 'message' => "Modulo '{$modulo_nombre}' eliminado"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
            }
            exit;
        }
        
        if ($id > 0) {
            // Obtener datos para mostrar en respuesta
            $select = $db->query("SELECT nombre FROM modulos WHERE id = {$id}");
            $modulo_nombre = '';
            if ($select && $db->num_rows($select) > 0) {
                $row = $db->fetch_assoc($select);
                $modulo_nombre = $row['nombre'];
            }
            
            $sql = "DELETE FROM modulos WHERE id = {$id}";
            
            if ($db->query($sql)) {
                echo json_encode(['success' => true, 'message' => "Módulo '{$modulo_nombre}' eliminado"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de módulo inválido']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no especificada o método inválido']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

// OBTENER MÓDULO
if ($action === 'obtener' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $sql = "SELECT id, slug, nombre, descripcion, icono, estado, orden FROM modulos WHERE id = {$id}";
    $result = $db->query($sql);
    
    if ($result && $db->num_rows($result) > 0) {
        $modulo = $db->fetch_assoc($result);
        echo json_encode(['success' => true, 'modulo' => $modulo]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Módulo no encontrado']);
    }
    exit;
}

// AGREGAR MÓDULO
if ($action === 'agregar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $icono = isset($_POST['icono']) ? trim($_POST['icono']) : 'fas fa-cube';
    $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 1;
    
    // Validar
    $errores = [];
    if (strlen($slug) < 3) $errores[] = "El slug debe tener al menos 3 caracteres";
    if (strlen($nombre) < 3) $errores[] = "El nombre debe tener al menos 3 caracteres";
    if (!preg_match('/^[a-z0-9_-]+$/', $slug)) $errores[] = "Solo minúsculas, números, guiones y guiones bajos";
    
    if (empty($errores)) {
        // Verificar que no exista
        $check = $db->query("SELECT id FROM modulos WHERE slug = '{$db->escape($slug)}' LIMIT 1");
        if ($check && $db->num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => "El slug '{$slug}' ya existe"]);
        } else {
            $sql = "INSERT INTO modulos (slug, nombre, descripcion, icono, estado, orden) 
                    VALUES ('{$db->escape($slug)}', '{$db->escape($nombre)}', '{$db->escape($descripcion)}', '{$db->escape($icono)}', 1, {$orden})";
            
            if ($db->query($sql)) {
                echo json_encode(['success' => true, 'message' => "Módulo '{$nombre}' creado"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el módulo']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    }
    exit;
}

// ACTUALIZAR MÓDULO
if ($action === 'actualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $icono = isset($_POST['icono']) ? trim($_POST['icono']) : 'fas fa-cube';
    $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 1;
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;
    
    // Validar
    if (strlen($nombre) < 3) {
        echo json_encode(['success' => false, 'message' => 'El nombre debe tener al menos 3 caracteres']);
        exit;
    }
    
    if ($id > 0) {
        $sql = "UPDATE modulos SET nombre = '{$db->escape($nombre)}', 
                descripcion = '{$db->escape($descripcion)}', 
                icono = '{$db->escape($icono)}',
                estado = {$estado},
                orden = {$orden}
                WHERE id = {$id}";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Módulo actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de módulo inválido']);
    }
    exit;
}

// ELIMINAR MÓDULO
if ($action === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id > 0) {
        // Obtener datos para mostrar en respuesta
        $select = $db->query("SELECT nombre FROM modulos WHERE id = {$id}");
        $modulo_nombre = '';
        if ($select && $db->num_rows($select) > 0) {
            $row = $db->fetch_assoc($select);
            $modulo_nombre = $row['nombre'];
        }
        
        $sql = "DELETE FROM modulos WHERE id = {$id}";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => "Módulo '{$modulo_nombre}' eliminado"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de módulo inválido']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no especificada o método inválido']);
?>
