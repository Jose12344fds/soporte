<?php
/**
 * API: destinatarios del correo semanal de programacion.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if (!$session->isUserLoggedIn(false) || !tiene_permiso('mantenimientos', 'ver')) {
    out_json(['success' => false, 'error' => 'No autorizado'], 401);
}

function out_json(array $data, int $code = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function table_exists_local(string $table): bool {
    global $db;
    $safe = $db->escape($table);
    $res = $db->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $db->num_rows($res) > 0;
}

try {
    global $db;

    if (!table_exists_local('mantenimientos_programacion_correos')) {
        out_json([
            'success' => false,
            'error' => 'Falta crear la tabla mantenimientos_programacion_correos.',
            'setup_url' => '/soporte/sql/_setup_mantenimientos_correo.php',
        ], 428);
    }

    $usuarios = [];
    $ru = $db->query(
        "SELECT id, name, username, correo, area, cargo
         FROM users
         WHERE estado = 1
           AND correo IS NOT NULL
           AND correo <> ''
           AND correo <> 'NO'
         ORDER BY name"
    );
    if ($ru) {
        while ($u = $db->fetch_assoc($ru)) {
            $usuarios[] = $u;
        }
    }

    $rows = [];
    $sql = "SELECT c.*,
                   u.name AS usuario_nombre,
                   u.username AS usuario_login,
                   u.area AS usuario_area,
                   u.cargo AS usuario_cargo,
                   u.correo AS usuario_correo
            FROM mantenimientos_programacion_correos c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.tipo_reporte = 'PROGRAMACION_MANTENIMIENTO'
            ORDER BY c.activo DESC, c.nombre_destino ASC";
    $res = $db->query($sql);
    if ($res) {
        while ($r = $db->fetch_assoc($res)) {
            $rows[] = $r;
        }
    }

    out_json(['success' => true, 'data' => $rows, 'usuarios' => $usuarios]);
} catch (Throwable $e) {
    out_json(['success' => false, 'error' => $e->getMessage()], 500);
}
