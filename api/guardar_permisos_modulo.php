<?php
/**
 * API para guardar permisos de un módulo
 */
require_once(__DIR__ . '/../includes/load.php');
require_once(__DIR__ . '/../config/app.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener JSON del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Datos inválidos');
    }

    $modulo = isset($input['modulo']) ? trim($input['modulo']) : '';
    $permisos = isset($input['permisos']) ? $input['permisos'] : [];

    if (empty($modulo) || empty($permisos)) {
        throw new Exception('Datos incompletos');
    }

    global $db;
    
    if (!$db) {
        throw new Exception('Conexión a BD no disponible');
    }

    // Procesar cada permiso
    foreach ($permisos as $perm) {
        $group_level = isset($perm['group_level']) ? (int)$perm['group_level'] : 0;
        $accion = isset($perm['accion']) ? trim($perm['accion']) : '';
        $valor = isset($perm['valor']) ? (int)$perm['valor'] : 0;
        
        if ($group_level > 0 && !empty($accion)) {
            // Mapear acciones
            $columna = '';
            if ($accion === 'puede_ver') $columna = 'puede_ver';
            elseif ($accion === 'puede_crear') $columna = 'puede_crear';
            elseif ($accion === 'puede_editar') $columna = 'puede_editar';
            elseif ($accion === 'puede_eliminar') $columna = 'puede_eliminar';
            
            if (!empty($columna)) {
                // Verificar si el registro existe
                $check = $db->query("SELECT id FROM permisos_modulos 
                                    WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}");
                
                if ($check && $db->num_rows($check) > 0) {
                    // Actualizar
                    $sql = "UPDATE permisos_modulos SET {$columna} = {$valor} 
                            WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}";
                } else {
                    // Insertar nuevo registro con valores por defecto
                    $sql = "INSERT INTO permisos_modulos (modulo, group_level, puede_ver, puede_crear, puede_editar, puede_eliminar)
                            VALUES ('{$db->escape($modulo)}', {$group_level}, 0, 0, 0, 0)";
                    $db->query($sql);
                    
                    // Ahora actualizar el campo específico
                    $sql = "UPDATE permisos_modulos SET {$columna} = {$valor} 
                            WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}";
                }
                
                $db->query($sql);
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Permisos guardados']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

// Procesar cada permiso
foreach ($permisos as $perm) {
    $group_level = isset($perm['group_level']) ? (int)$perm['group_level'] : 0;
    $accion = isset($perm['accion']) ? trim($perm['accion']) : '';
    $valor = isset($perm['valor']) ? (int)$perm['valor'] : 0;
    
    if ($group_level > 0 && !empty($accion)) {
        // Mapear acciones
        $columna = '';
        if ($accion === 'puede_ver') $columna = 'puede_ver';
        elseif ($accion === 'puede_crear') $columna = 'puede_crear';
        elseif ($accion === 'puede_editar') $columna = 'puede_editar';
        elseif ($accion === 'puede_eliminar') $columna = 'puede_eliminar';
        
        if (!empty($columna)) {
            // Verificar si el registro existe
            $check = $db->query("SELECT id FROM permisos_modulos 
                                WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}");
            
            if ($check && $db->num_rows($check) > 0) {
                // Actualizar
                $sql = "UPDATE permisos_modulos SET {$columna} = {$valor} 
                        WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}";
            } else {
                // Insertar nuevo registro con valores por defecto
                $sql = "INSERT INTO permisos_modulos (modulo, group_level, puede_ver, puede_crear, puede_editar, puede_eliminar)
                        VALUES ('{$db->escape($modulo)}', {$group_level}, 0, 0, 0, 0)";
                $db->query($sql);
                
                // Ahora actualizar el campo específico
                $sql = "UPDATE permisos_modulos SET {$columna} = {$valor} 
                        WHERE modulo = '{$db->escape($modulo)}' AND group_level = {$group_level}";
            }
            
            $db->query($sql);
        }
    }
}

echo json_encode(['success' => true, 'message' => 'Permisos guardados']);
?>
