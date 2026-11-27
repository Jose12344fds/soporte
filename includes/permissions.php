<?php
/**
 * Sistema de Permisos por Módulos
 * Sistema de Soporte TI - TAMA
 */

/**
 * Verifica si el usuario tiene permiso para una acción en un módulo
 * @param string $modulo - Nombre del módulo (usuarios, activos, incidencias, etc.)
 * @param string $accion - Acción a verificar (ver, crear, editar, eliminar)
 * @return bool
 */
function tiene_permiso($modulo, $accion = 'ver') {
    global $db;
    $user = current_user();
    
    if (!$user) {
        return false;
    }
    
    $user_level = (int) $user['user_level'];
    $modulo = $db->escape($modulo);
    
    // Mapear acción a columna
    $columnas = [
        'ver' => 'puede_ver',
        'crear' => 'puede_crear',
        'editar' => 'puede_editar',
        'eliminar' => 'puede_eliminar'
    ];
    
    $columna = isset($columnas[$accion]) ? $columnas[$accion] : 'puede_ver';
    
    // Verificar si existe la tabla de permisos
    $check = $db->query("SHOW TABLES LIKE 'permisos_modulos'");
    if ($db->num_rows($check) == 0) {
        // Si no existe la tabla, usar el sistema legacy (nivel 1 = admin tiene todo)
        return $user_level <= 1;
    }
    
    $sql = "SELECT {$columna} FROM permisos_modulos 
            WHERE group_level = {$user_level} AND modulo = '{$modulo}' 
            LIMIT 1";
    $result = $db->query($sql);
    
    if ($db->num_rows($result) > 0) {
        $row = $db->fetch_assoc($result);
        return (bool) $row[$columna];
    }
    
    // Si no hay permiso definido, solo admin (nivel 1) tiene acceso
    return $user_level <= 1;
}

/**
 * Requiere permiso para acceder a una página
 * Redirige si no tiene permiso
 * @param string $modulo
 * @param string $accion
 */
function require_permiso($modulo, $accion = 'ver') {
    global $session;
    
    // Verificar si está logueado
    if (!$session->isUserLoggedIn(true)) {
        $session->msg('d', 'Por favor inicie sesión.');
        redirect('/soporte/index.php', false);
        exit;
    }
    
    // Verificar permiso
    if (!tiene_permiso($modulo, $accion)) {
        $session->msg('d', '¡No tiene permiso para acceder a esta sección!');
        redirect('/soporte/home.php', false);
        exit;
    }
    
    return true;
}

/**
 * Obtiene todos los permisos de un grupo
 * @param int $group_level
 * @return array
 */
function obtener_permisos_grupo($group_level) {
    global $db;
    $group_level = (int) $group_level;
    
    $sql = "SELECT * FROM permisos_modulos WHERE group_level = {$group_level}";
    $result = $db->query($sql);
    
    $permisos = [];
    while ($row = $db->fetch_assoc($result)) {
        $permisos[$row['modulo']] = [
            'ver' => (bool) $row['puede_ver'],
            'crear' => (bool) $row['puede_crear'],
            'editar' => (bool) $row['puede_editar'],
            'eliminar' => (bool) $row['puede_eliminar']
        ];
    }
    
    return $permisos;
}

/**
 * Guarda los permisos de un grupo
 * @param int $group_level
 * @param array $permisos
 * @return bool
 */
function guardar_permisos_grupo($group_level, $permisos) {
    global $db;
    $group_level = (int) $group_level;
    
    foreach ($permisos as $modulo => $acciones) {
        $modulo = $db->escape($modulo);
        $ver = isset($acciones['ver']) && $acciones['ver'] ? 1 : 0;
        $crear = isset($acciones['crear']) && $acciones['crear'] ? 1 : 0;
        $editar = isset($acciones['editar']) && $acciones['editar'] ? 1 : 0;
        $eliminar = isset($acciones['eliminar']) && $acciones['eliminar'] ? 1 : 0;
        
        $sql = "INSERT INTO permisos_modulos (group_level, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar)
                VALUES ({$group_level}, '{$modulo}', {$ver}, {$crear}, {$editar}, {$eliminar})
                ON DUPLICATE KEY UPDATE 
                    puede_ver = {$ver},
                    puede_crear = {$crear},
                    puede_editar = {$editar},
                    puede_eliminar = {$eliminar}";
        
        if (!$db->query($sql)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Lista de módulos del sistema obtenida dinámicamente desde la tabla `modulos`
 */
function obtener_modulos() {
    global $db;

    $modulos = [];

    // Verificar que la tabla exista
    $check = $db->query("SHOW TABLES LIKE 'modulos'");
    if ($db->num_rows($check) == 0) {
        return $modulos;
    }

    $result = $db->query("SELECT slug, nombre FROM modulos WHERE estado = 1 ORDER BY orden ASC, nombre ASC");
    while ($row = $db->fetch_assoc($result)) {
        $modulos[$row['slug']] = $row['nombre'];
    }

    return $modulos;
}

/**
 * Crea la tabla de permisos si no existe
 */
function crear_tabla_permisos() {
    global $db;
    
    $sql = "CREATE TABLE IF NOT EXISTS permisos_modulos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_level INT NOT NULL,
        modulo VARCHAR(50) NOT NULL,
        puede_ver TINYINT(1) DEFAULT 0,
        puede_crear TINYINT(1) DEFAULT 0,
        puede_editar TINYINT(1) DEFAULT 0,
        puede_eliminar TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_permiso (group_level, modulo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($db->query($sql)) {
        // Insertar permisos por defecto para Admin (nivel 1)
        $modulos = obtener_modulos();
        foreach ($modulos as $modulo => $nombre) {
            $db->query("INSERT IGNORE INTO permisos_modulos 
                        (group_level, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar)
                        VALUES (1, '{$modulo}', 1, 1, 1, 1)");
        }
        
        // Permisos básicos para Usuario (nivel 2)
        $db->query("INSERT IGNORE INTO permisos_modulos VALUES (NULL, 2, 'activos', 1, 0, 0, 0, NOW())");
        $db->query("INSERT IGNORE INTO permisos_modulos VALUES (NULL, 2, 'incidencias', 1, 1, 1, 0, NOW())");
        $db->query("INSERT IGNORE INTO permisos_modulos VALUES (NULL, 2, 'mantenimientos', 1, 1, 1, 0, NOW())");
        
        return true;
    }
    
    return false;
}
