<?php
/**
 * Envia por SMTP el listado semanal de programacion de mantenimiento.
 *
 * Uso recomendado en tarea programada:
 *   php scripts/enviar_programacion_mantenimiento.php
 *
 * Pruebas:
 *   php scripts/enviar_programacion_mantenimiento.php --dry-run --force
 *   php scripts/enviar_programacion_mantenimiento.php --force
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';
date_default_timezone_set(APP_TIMEZONE);
require_once $root . '/api/mantenimientos/conexion_hana.php';

$isCli = PHP_SAPI === 'cli';
$args = $isCli ? array_slice($argv ?? [], 1) : [];

function cli_flag(array $names): bool {
    global $args;
    foreach ($names as $name) {
        if (in_array('--' . $name, $args, true)) {
            return true;
        }
    }
    return false;
}

$force = cli_flag(['force', 'forzar', 'forcé']) || (!$isCli && (!empty($_GET['force']) || !empty($_GET['forzar'])));
$dryRun = cli_flag(['dry-run', 'dryrun', 'prueba']) || (!$isCli && (!empty($_GET['dry_run']) || !empty($_GET['prueba'])));

function cli_arg(string $name, ?string $default = null): ?string {
    global $args;
    $prefix = '--' . $name . '=';
    foreach ($args as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $_ENV['TAMA_' . strtoupper(str_replace('-', '_', $name))] ?? getenv('TAMA_' . strtoupper(str_replace('-', '_', $name))) ?: $default;
}

function say(string $msg): void {
    global $isCli;
    if ($isCli) {
        echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    }
}

function finish(array $data, int $status = 200): void {
    global $isCli;
    if ($isCli) {
        if (!empty($data['message'])) say($data['message']);
        if (!empty($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $error) {
                say('ERROR: ' . $error);
            }
        }
        exit($status >= 400 ? 1 : 0);
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function db_local(): mysqli {
    $host = cli_arg('db-host', DB_HOST);
    $user = cli_arg('db-user', DB_USER);
    $pass = cli_arg('db-pass', DB_PASS);
    $name = cli_arg('db-name', DB_NAME);
    $db = new mysqli($host, $user, $pass, $name);
    if ($db->connect_error) {
        throw new RuntimeException('MySQL: ' . $db->connect_error);
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function q(mysqli $db, ?string $value): string {
    $s = trim((string)($value ?? ''));
    if ($s === '') return 'NULL';
    return "'" . $db->real_escape_string($s) . "'";
}

function ensure_log_table(mysqli $db): void {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `mantenimientos_programacion_envios_log` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `correo_destino` VARCHAR(160) NOT NULL,
            `nombre_destino` VARCHAR(120) NULL,
            `estado` VARCHAR(20) NOT NULL,
            `mensaje` TEXT NULL,
            `total_programaciones` INT NOT NULL DEFAULT 0,
            `dry_run` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_prog_mail_log_created` (`created_at`),
            KEY `idx_prog_mail_log_correo` (`correo_destino`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function log_send(mysqli $db, array $dest, string $estado, string $mensaje, int $total, bool $dryRun): void {
    $correo = $db->real_escape_string((string)($dest['correo_destino'] ?? ''));
    $nombre = $db->real_escape_string((string)($dest['nombre_destino'] ?? ''));
    $estado = $db->real_escape_string($estado);
    $mensaje = $db->real_escape_string($mensaje);
    $dry = $dryRun ? 1 : 0;
    $db->query(
        "INSERT INTO mantenimientos_programacion_envios_log
            (correo_destino, nombre_destino, estado, mensaje, total_programaciones, dry_run)
         VALUES ('{$correo}', '{$nombre}', '{$estado}', '{$mensaje}', {$total}, {$dry})"
    );
}

function get_smtp_config(mysqli $db): array {
    $res = $db->query(
        "SELECT *
         FROM correo_smtp_config
         WHERE activo = 1
         ORDER BY id DESC
         LIMIT 1"
    );
    if (!$res || $res->num_rows === 0) {
        throw new RuntimeException('No hay configuracion SMTP activa en correo_smtp_config.');
    }
    return $res->fetch_assoc();
}

function get_destinatarios(mysqli $db, bool $force): array {
    $dia = (int)date('N');
    $hora = date('H:i:s');
    $where = "activo = 1 AND tipo_reporte = 'PROGRAMACION_MANTENIMIENTO'";
    if (!$force) {
        $where .= " AND dia_envio = {$dia}
                    AND hora_envio <= '{$hora}'
                    AND (ultimo_envio IS NULL OR DATE(ultimo_envio) < CURDATE())";
    }
    $res = $db->query(
        "SELECT *
         FROM mantenimientos_programacion_correos
         WHERE {$where}
         ORDER BY nombre_destino"
    );
    $rows = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    return $rows;
}

function get_programaciones(): array {
    $pdo = getHanaPdo();
    $sql = <<<SQL
SELECT
    T0."Code",
    T0."Name",
    T0."U_ItemCode",
    COALESCE(NULLIF(T3."U_ItemNameEq", ''), T0."U_ItemCode") AS "ItemName",
    T3."U_InventryNo" AS "InventryNo",
    T3."U_AssetSerNo" AS "AssetSerNo",
    T0."U_TipoMant",
    COALESCE(T2."Name", T0."U_TipoMant") AS "TipoMantName",
    SUBSTRING(TO_VARCHAR(T0."U_FechaProg"), 1, 10) AS "U_FechaProg",
    T0."U_Frecuencia",
    COALESCE(NULLIF(T0."U_Estado", ''), 'Pendiente') AS "U_Estado",
    T0."U_Observacion"
FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" T0
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT" T2 ON T0."U_TipoMant" = T2."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T3 ON T0."U_ItemCode" = T3."Name"
WHERE COALESCE(NULLIF(T0."U_Estado", ''), 'Pendiente') <> 'Ejecutado'
  AND T0."U_FechaProg" <= ADD_DAYS(CURRENT_DATE, 7)
ORDER BY T0."U_FechaProg" ASC, T0."U_ItemCode" ASC
SQL;
    return $pdo->query($sql)->fetchAll();
}

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function csv_escape($v): string {
    $s = str_replace('"', '""', (string)$v);
    return '"' . $s . '"';
}

function build_csv(array $rows): string {
    $headers = ['Code','Descripcion','Equipo','Nombre equipo','Inventario','Serie','Tipo','Fecha Programada','Frecuencia','Estado','Observacion'];
    $lines = [implode(';', array_map('csv_escape', $headers))];
    foreach ($rows as $r) {
        $lines[] = implode(';', array_map('csv_escape', [
            $r['Code'] ?? '',
            $r['Name'] ?? '',
            $r['U_ItemCode'] ?? '',
            $r['ItemName'] ?? '',
            $r['InventryNo'] ?? '',
            $r['AssetSerNo'] ?? '',
            $r['TipoMantName'] ?? $r['U_TipoMant'] ?? '',
            $r['U_FechaProg'] ?? '',
            $r['U_Frecuencia'] ?? '',
            $r['U_Estado'] ?? '',
            $r['U_Observacion'] ?? '',
        ]));
    }
    return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
}

function build_html(array $rows): string {
    $today = date('d/m/Y');
    $total = count($rows);
    $body = '<!doctype html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;color:#1f2933">';
    $body .= '<h2 style="margin:0 0 8px;color:#166534">Programacion semanal de mantenimiento</h2>';
    $body .= '<p style="margin:0 0 14px">Fecha de envio: <b>' . h($today) . '</b> | Registros: <b>' . $total . '</b></p>';
    if ($total === 0) {
        $body .= '<p>No hay programaciones pendientes/vencidas para los proximos 7 dias.</p>';
    } else {
        $body .= '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-size:12px;width:100%;border-color:#d1d5db">';
        $body .= '<thead><tr style="background:#166534;color:#fff">'
            . '<th>Code</th><th>Equipo</th><th>Descripcion</th><th>Tipo</th><th>Fecha</th><th>Frecuencia</th><th>Estado</th><th>Observacion</th>'
            . '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $estado = (string)($r['U_Estado'] ?? '');
            $bg = stripos($estado, 'venc') !== false ? '#fee2e2' : '#ffffff';
            $body .= '<tr style="background:' . $bg . '">'
                . '<td>' . h($r['Code'] ?? '') . '</td>'
                . '<td><b>' . h($r['U_ItemCode'] ?? '') . '</b><br><span style="color:#6b7280">' . h($r['ItemName'] ?? '') . '</span></td>'
                . '<td>' . h($r['Name'] ?? '') . '</td>'
                . '<td>' . h($r['TipoMantName'] ?? $r['U_TipoMant'] ?? '') . '</td>'
                . '<td>' . h($r['U_FechaProg'] ?? '') . '</td>'
                . '<td>' . h($r['U_Frecuencia'] ?? '') . '</td>'
                . '<td>' . h($estado) . '</td>'
                . '<td>' . h($r['U_Observacion'] ?? '') . '</td>'
                . '</tr>';
        }
        $body .= '</tbody></table>';
    }
    $body .= '<p style="margin-top:16px;color:#6b7280;font-size:12px">Correo automatico del Sistema de Soporte TAMA.</p>';
    $body .= '</body></html>';
    return $body;
}

function smtp_read($fp): string {
    $data = '';
    while (($line = fgets($fp, 515)) !== false) {
        $data .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_cmd($fp, string $cmd, array $okCodes): string {
    fwrite($fp, $cmd . "\r\n");
    $resp = smtp_read($fp);
    $code = substr($resp, 0, 3);
    if (!in_array($code, $okCodes, true)) {
        throw new RuntimeException("SMTP comando fallo ({$cmd}): {$resp}");
    }
    return $resp;
}

function header_encode(string $s): string {
    return '=?UTF-8?B?' . base64_encode($s) . '?=';
}

function build_mime_message(array $smtp, array $dest, string $subject, string $html, ?string $csv): string {
    $fromEmail = trim((string)($smtp['from_email'] ?? $smtp['smtp_user'] ?? ''));
    $fromName = trim((string)($smtp['from_name'] ?? 'Sistema de Soporte TAMA'));
    $toEmail = trim((string)$dest['correo_destino']);
    $toName = trim((string)($dest['nombre_destino'] ?? $toEmail));
    $boundary = '=_TAMA_' . bin2hex(random_bytes(12));

    $headers = [
        'From: ' . header_encode($fromName) . " <{$fromEmail}>",
        'To: ' . header_encode($toName) . " <{$toEmail}>",
        'Subject: ' . header_encode($subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        'Date: ' . date(DATE_RFC2822),
    ];
    if (!empty($smtp['reply_to'])) {
        $headers[] = 'Reply-To: ' . trim((string)$smtp['reply_to']);
    }

    $msg = implode("\r\n", $headers) . "\r\n\r\n";
    $msg .= "--{$boundary}\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($html));

    if ($csv !== null) {
        $filename = 'programacion_mantenimiento_' . date('Y-m-d') . '.csv';
        $msg .= "\r\n--{$boundary}\r\n";
        $msg .= 'Content-Type: text/csv; name="' . $filename . "\"\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n";
        $msg .= 'Content-Disposition: attachment; filename="' . $filename . "\"\r\n\r\n";
        $msg .= chunk_split(base64_encode($csv));
    }

    $msg .= "\r\n--{$boundary}--\r\n";
    return $msg;
}

function smtp_send(array $smtp, array $dest, string $subject, string $html, ?string $csv): void {
    $host = trim((string)($smtp['smtp_host'] ?? ''));
    $port = (int)($smtp['smtp_port'] ?? 587);
    $secure = strtolower(trim((string)($smtp['smtp_secure'] ?? 'tls')));
    $auth = (int)($smtp['smtp_auth'] ?? 1) === 1;
    $user = trim((string)($smtp['smtp_user'] ?? ''));
    $pass = (string)($smtp['smtp_pass'] ?? '');
    $from = trim((string)($smtp['from_email'] ?? $user));
    $to = trim((string)$dest['correo_destino']);

    if ($host === '' || $from === '' || $to === '') {
        throw new RuntimeException('SMTP incompleto: host/from/to requerido.');
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);
    if (!$fp) throw new RuntimeException("No se pudo conectar SMTP {$host}:{$port} - {$errstr}");
    stream_set_timeout($fp, 30);

    $hello = smtp_read($fp);
    if (substr($hello, 0, 3) !== '220') throw new RuntimeException('SMTP no saludo correctamente: ' . $hello);
    smtp_cmd($fp, 'EHLO soporte-tama', ['250']);

    if ($secure === 'tls') {
        smtp_cmd($fp, 'STARTTLS', ['220']);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('No se pudo iniciar TLS con SMTP.');
        }
        smtp_cmd($fp, 'EHLO soporte-tama', ['250']);
    }

    if ($auth) {
        smtp_cmd($fp, 'AUTH LOGIN', ['334']);
        smtp_cmd($fp, base64_encode($user), ['334']);
        smtp_cmd($fp, base64_encode($pass), ['235']);
    }

    smtp_cmd($fp, 'MAIL FROM:<' . $from . '>', ['250']);
    smtp_cmd($fp, 'RCPT TO:<' . $to . '>', ['250', '251']);
    smtp_cmd($fp, 'DATA', ['354']);

    $msg = build_mime_message($smtp, $dest, $subject, $html, $csv);
    $msg = preg_replace('/^\./m', '..', $msg);
    fwrite($fp, $msg . "\r\n.\r\n");
    $resp = smtp_read($fp);
    if (substr($resp, 0, 3) !== '250') throw new RuntimeException('SMTP rechazo DATA: ' . $resp);
    smtp_cmd($fp, 'QUIT', ['221']);
    fclose($fp);
}

try {
    $db = db_local();
    ensure_log_table($db);

    $smtp = get_smtp_config($db);
    $destinatarios = get_destinatarios($db, $force);
    $programaciones = get_programaciones();
    $html = build_html($programaciones);
    $csv = build_csv($programaciones);
    $subject = 'Programacion semanal de mantenimiento - ' . date('d/m/Y');

    if (!$destinatarios) {
        finish(['success' => true, 'message' => 'No hay destinatarios pendientes de envio.']);
    }

    $sent = 0;
    $errors = [];
    foreach ($destinatarios as $dest) {
        try {
            $attachCsv = (int)($dest['recibe_excel'] ?? 1) === 1 ? $csv : null;
            $bodyHtml = (int)($dest['recibe_html'] ?? 1) === 1 ? $html : '<p>Se adjunta programacion semanal de mantenimiento.</p>';
            if ($dryRun) {
                say('DRY-RUN: enviaria a ' . $dest['correo_destino']);
            } else {
                smtp_send($smtp, $dest, $subject, $bodyHtml, $attachCsv);
                $id = (int)$dest['id'];
                $db->query("UPDATE mantenimientos_programacion_correos SET ultimo_envio = NOW() WHERE id = {$id}");
            }
            log_send($db, $dest, 'OK', $dryRun ? 'Dry run OK' : 'Enviado correctamente', count($programaciones), $dryRun);
            $sent++;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $errors[] = ($dest['correo_destino'] ?? '') . ': ' . $msg;
            log_send($db, $dest, 'ERROR', $msg, count($programaciones), $dryRun);
        }
    }

    finish([
        'success' => empty($errors),
        'sent' => $sent,
        'errors' => $errors,
        'total_programaciones' => count($programaciones),
        'message' => "Proceso terminado. Enviados: {$sent}. Errores: " . count($errors),
    ], empty($errors) ? 200 : 500);
} catch (Throwable $e) {
    finish(['success' => false, 'message' => $e->getMessage()], 500);
}
