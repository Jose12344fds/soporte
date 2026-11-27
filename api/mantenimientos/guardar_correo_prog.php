<?php
/**
 * API: guardar destinatarios del correo semanal de programacion.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out_json(['success' => false, 'error' => 'Metodo no permitido'], 405);
}

if (!$session->isUserLoggedIn(false) || !tiene_permiso('mantenimientos', 'editar')) {
    out_json(['success' => false, 'error' => 'No autorizado'], 401);
}

function out_json(array $data, int $code = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function v($value): string {
    return trim((string)($value ?? ''));
}

function email_ok(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function table_exists_local(string $table): bool {
    global $db;
    $safe = $db->escape($table);
    $res = $db->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $db->num_rows($res) > 0;
}

function fetch_user_for_mail(int $id): ?array {
    global $db;
    if ($id <= 0) return null;
    $res = $db->query(
        "SELECT id, name, correo
         FROM users
         WHERE id = {$id}
         LIMIT 1"
    );
    return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res) : null;
}

try {
    global $db;
    if (!table_exists_local('mantenimientos_programacion_correos')) {
        out_json([
            'success' => false,
            'error' => 'Falta crear la tabla. Ejecuta /soporte/sql/_setup_mantenimientos_correo.php',
        ], 428);
    }

    $raw = file_get_contents('php://input');
    $input = !empty($raw) ? (json_decode($raw, true) ?? $_POST) : $_POST;
    $action = strtolower(v($input['action'] ?? 'save'));
    $id = (int)($input['id'] ?? 0);

    if ($action === 'delete') {
        if ($id <= 0) out_json(['success' => false, 'error' => 'ID requerido'], 400);
        $db->query("DELETE FROM mantenimientos_programacion_correos WHERE id = {$id}");
        out_json(['success' => true, 'action' => 'deleted']);
    }

    $userId = (int)($input['user_id'] ?? 0);
    $nombre = v($input['nombre_destino'] ?? '');
    $correo = strtolower(v($input['correo_destino'] ?? ''));
    $dia = (int)($input['dia_envio'] ?? 1);
    $hora = v($input['hora_envio'] ?? '08:00');
    $activo = !empty($input['activo']) ? 1 : 0;
    $recibeHtml = !empty($input['recibe_html']) ? 1 : 0;
    $recibeExcel = !empty($input['recibe_excel']) ? 1 : 0;
    $observacion = v($input['observacion'] ?? '');

    $usuario = fetch_user_for_mail($userId);
    if ($usuario) {
        if ($nombre === '') $nombre = v($usuario['name'] ?? '');
        if ($correo === '') $correo = strtolower(v($usuario['correo'] ?? ''));
    }

    if ($nombre === '') out_json(['success' => false, 'error' => 'Nombre destino requerido'], 400);
    if (!email_ok($correo)) out_json(['success' => false, 'error' => 'Correo destino invalido'], 400);
    if ($dia < 1 || $dia > 7) $dia = 1;
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) $hora = '08:00';
    if (strlen($hora) === 5) $hora .= ':00';

    $userSql = $userId > 0 ? (string)$userId : 'NULL';
    $nombreSql = $db->escape($nombre);
    $correoSql = $db->escape($correo);
    $horaSql = $db->escape($hora);
    $obsSql = $db->escape($observacion);

    if ($id > 0) {
        $sql = "UPDATE mantenimientos_programacion_correos SET
                    user_id = {$userSql},
                    nombre_destino = '{$nombreSql}',
                    correo_destino = '{$correoSql}',
                    dia_envio = {$dia},
                    hora_envio = '{$horaSql}',
                    activo = {$activo},
                    recibe_html = {$recibeHtml},
                    recibe_excel = {$recibeExcel},
                    observacion = " . ($obsSql === '' ? 'NULL' : "'{$obsSql}'") . "
                WHERE id = {$id}";
        $db->query($sql);
        out_json(['success' => true, 'action' => 'updated', 'id' => $id]);
    }

    $sql = "INSERT INTO mantenimientos_programacion_correos
                (user_id, nombre_destino, correo_destino, tipo_reporte, dia_envio, hora_envio,
                 activo, recibe_html, recibe_excel, observacion)
            VALUES
                ({$userSql}, '{$nombreSql}', '{$correoSql}', 'PROGRAMACION_MANTENIMIENTO',
                 {$dia}, '{$horaSql}', {$activo}, {$recibeHtml}, {$recibeExcel},
                 " . ($obsSql === '' ? 'NULL' : "'{$obsSql}'") . ")
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                nombre_destino = VALUES(nombre_destino),
                dia_envio = VALUES(dia_envio),
                hora_envio = VALUES(hora_envio),
                activo = VALUES(activo),
                recibe_html = VALUES(recibe_html),
                recibe_excel = VALUES(recibe_excel),
                observacion = VALUES(observacion)";
    $db->query($sql);
    out_json(['success' => true, 'action' => 'saved']);
} catch (Throwable $e) {
    out_json(['success' => false, 'error' => $e->getMessage()], 500);
}
