<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones.php');

$action = strtolower(trim((string)($_REQUEST['action'] ?? 'list')));
require_permiso('accesos_usuario', in_array($action, ['upload', 'status'], true) ? 'editar' : 'ver');

function f08_email_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function f08_email_root(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'accesos_usuario' . DIRECTORY_SEPARATOR . 'correos';
}

function f08_open_msg(string $path)
{
    require_once(__DIR__ . '/vendor/autoload.php');
    $documentFactory = new \Hfig\MAPI\OLE\Pear\DocumentFactory();
    $messageFactory = new \Hfig\MAPI\MapiMessageFactory();
    return $messageFactory->parseMessage($documentFactory->createFromFile($path));
}

function f08_email_file(array $row): ?string
{
    $root = f08_email_root();
    $rootReal = realpath($root);
    $fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta_relativa']));
    return $rootReal && $fileReal && str_starts_with($fileReal, $rootReal . DIRECTORY_SEPARATOR) && is_file($fileReal) ? $fileReal : null;
}

function f08_email_rows(int $recordId): array
{
    return find_by_sql("SELECT c.`id`,c.`asunto`,c.`descripcion`,c.`nombre_original`,c.`extension`,c.`tamano_bytes`,c.`estado`,
      DATE_FORMAT(c.`fecha_carga`,'%d/%m/%Y %H:%i') AS `fecha_carga`,u.`name` AS `cargado_por_nombre`
      FROM `usuariosf08_correos` c LEFT JOIN `users` u ON u.`id`=c.`cargado_por`
      WHERE c.`registro_id`={$recordId} ORDER BY c.`fecha_carga` DESC,c.`id` DESC");
}

$recordId = max(0, (int)($_REQUEST['registro_id'] ?? $_REQUEST['id_f08'] ?? 0));
if ($action === 'attachment') {
    $emailId = max(0, (int)($_GET['id'] ?? 0));
    $attachmentIndex = (int)($_GET['index'] ?? -1);
    $row = $db->fetch_assoc($db->query("SELECT * FROM `usuariosf08_correos` WHERE `id`={$emailId} LIMIT 1"));
    $fileReal = $row ? f08_email_file($row) : null;
    if (!$row || !$fileReal || strtolower((string)$row['extension']) !== 'msg' || $attachmentIndex < 0) {
        http_response_code(404);
        exit('Adjunto no encontrado.');
    }
    try {
        $attachments = f08_open_msg($fileReal)->getAttachments();
        if (!isset($attachments[$attachmentIndex])) throw new RuntimeException('Indice inexistente.');
        $attachment = $attachments[$attachmentIndex];
        $data = $attachment->getData();
        if (!is_string($data)) throw new RuntimeException('Adjunto compuesto no descargable.');
        $name = trim((string)$attachment->getFilename()) ?: 'adjunto-' . ($attachmentIndex + 1);
        $name = str_replace(["\r", "\n", '"'], '', $name);
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: ' . ((string)$attachment->getMimeType() ?: 'application/octet-stream'));
        header('Content-Length: ' . strlen($data));
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($name));
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        echo $data;
        exit;
    } catch (Throwable $error) {
        error_log('F08 MSG attachment #' . $emailId . ': ' . $error->getMessage());
        http_response_code(404);
        exit('No se pudo extraer este adjunto.');
    }
}
if ($action === 'download') {
    $emailId = max(0, (int)($_GET['id'] ?? 0));
    $row = $db->fetch_assoc($db->query("SELECT * FROM `usuariosf08_correos` WHERE `id`={$emailId} LIMIT 1"));
    if (!$row) {
        http_response_code(404);
        exit('Correo no encontrado.');
    }
    $root = f08_email_root();
    $rootReal = realpath($root);
    $fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta_relativa']));
    if (!$rootReal || !$fileReal || !str_starts_with($fileReal, $rootReal . DIRECTORY_SEPARATOR) || !is_file($fileReal)) {
        http_response_code(404);
        exit('El archivo historico no esta disponible.');
    }
    $name = str_replace(["\r", "\n", '"'], '', (string)$row['nombre_original']);
    $inline = strtolower((string)$row['extension']) === 'pdf' ? 'inline' : 'attachment';
    while (ob_get_level() > 0) ob_end_clean();
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: ' . ((string)$row['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($fileReal));
    header("Content-Disposition: {$inline}; filename*=UTF-8''" . rawurlencode($name));
    header('Content-Transfer-Encoding: binary');
    header('X-Content-Type-Options: nosniff');
    readfile($fileReal);
    exit;
}

if ($action === 'preview') {
    $emailId = max(0, (int)($_GET['id'] ?? 0));
    $row = $db->fetch_assoc($db->query("SELECT * FROM `usuariosf08_correos` WHERE `id`={$emailId} LIMIT 1"));
    if (!$row) f08_email_json(['success' => false, 'error' => 'Correo no encontrado.'], 404);
    $root = f08_email_root();
    $rootReal = realpath($root);
    $fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta_relativa']));
    if (!$rootReal || !$fileReal || !str_starts_with($fileReal, $rootReal . DIRECTORY_SEPARATOR) || !is_file($fileReal)) {
        f08_email_json(['success' => false, 'error' => 'El archivo historico no esta disponible.'], 404);
    }
    $extension = strtolower((string)$row['extension']);
    if ($extension === 'pdf') {
        f08_email_json(['success' => true, 'type' => 'pdf', 'url' => 'correos.php?action=download&id=' . $emailId]);
    }
    if ($extension === 'msg') {
        try {
            require_once(__DIR__ . '/vendor/autoload.php');
            $message = f08_open_msg($fileReal);
            $recipients = ['To' => [], 'Cc' => [], 'Bcc' => []];
            foreach ($message->getRecipients() as $recipient) {
                $type = (string)$recipient->getType();
                if (isset($recipients[$type])) $recipients[$type][] = (string)$recipient;
            }
            $attachments = [];
            foreach ($message->getAttachments() as $attachmentIndex => $attachment) {
                $filename = trim((string)$attachment->getFilename());
                if ($filename !== '') $attachments[] = [
                    'index' => (int)$attachmentIndex,
                    'name' => $filename,
                    'url' => 'correos.php?action=attachment&id=' . $emailId . '&index=' . (int)$attachmentIndex,
                ];
            }
            try {
                $body = (string)$message->getBody();
            } catch (Throwable $bodyError) {
                try {
                    $body = html_entity_decode(strip_tags((string)$message->getBodyHTML()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                } catch (Throwable $htmlError) {
                    $body = '';
                }
            }
            $dateValue = $message->properties['message_delivery_time'] ?? $message->properties['client_submit_time'] ?? $message->properties['creation_time'] ?? '';
            $date = $dateValue instanceof DateTimeInterface ? $dateValue->format('d/m/Y H:i') : (string)$dateValue;
            $content = [
                'subject' => trim((string)($message->properties['subject'] ?? $row['asunto'])),
                'sender' => trim((string)$message->getSender()),
                'to' => implode('; ', $recipients['To']),
                'cc' => implode('; ', $recipients['Cc']),
                'date' => $date,
                'body' => mb_substr(trim($body), 0, 200000),
                'attachments' => $attachments,
            ];
        } catch (Throwable $error) {
            error_log('F08 MSG preview #' . $emailId . ': ' . $error->getMessage());
            f08_email_json(['success' => false, 'error' => 'No se pudo interpretar este archivo MSG. Puede descargar el original.'], 422);
        }
        f08_email_json(['success' => true, 'type' => 'email', 'content' => $content]);
    }
    if ($extension === 'eml') {
        $raw = (string)file_get_contents($fileReal);
        [$headerText, $body] = array_pad(preg_split("/\r?\n\r?\n/", $raw, 2), 2, '');
        $headers = [];
        $headerText = preg_replace("/\r?\n[ \t]+/", ' ', $headerText) ?? $headerText;
        foreach (preg_split('/\r?\n/', $headerText) as $line) {
            if (strpos($line, ':') === false) continue;
            [$key, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($value);
        }
        $decode = static function ($value) {
            return function_exists('iconv_mime_decode') ? (iconv_mime_decode((string)$value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: (string)$value) : (string)$value;
        };
        if (stripos((string)($headers['content-transfer-encoding'] ?? ''), 'base64') !== false) $body = (string)base64_decode(preg_replace('/\s+/', '', $body), true);
        elseif (stripos((string)($headers['content-transfer-encoding'] ?? ''), 'quoted-printable') !== false) $body = quoted_printable_decode($body);
        f08_email_json(['success' => true, 'type' => 'email', 'content' => [
            'subject' => $decode($headers['subject'] ?? $row['asunto']), 'sender' => $decode($headers['from'] ?? ''),
            'to' => $decode($headers['to'] ?? ''), 'cc' => $decode($headers['cc'] ?? ''), 'date' => $decode($headers['date'] ?? ''),
            'body' => mb_substr(strip_tags($body), 0, 200000), 'attachments' => [],
        ]]);
    }
    f08_email_json(['success' => false, 'error' => 'Este formato no admite vista previa.'], 422);
}

if ($recordId <= 0 || !f08_get_record($recordId)) {
    f08_email_json(['success' => false, 'error' => 'La ficha F08 no existe.'], 404);
}

if ($action === 'list') {
    f08_email_json(['success' => true, 'rows' => f08_email_rows($recordId)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    f08_email_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

$operator = current_user();
$operatorId = (int)($operator['id'] ?? 0);

if ($action === 'status') {
    $emailId = max(0, (int)($_POST['correo_id'] ?? 0));
    $status = strtoupper(trim((string)($_POST['estado'] ?? '')));
    if (!in_array($status, ['VALIDO', 'INACTIVO'], true)) {
        f08_email_json(['success' => false, 'error' => 'Estado no valido.'], 422);
    }
    $email = $db->fetch_assoc($db->query("SELECT `id`,`estado` FROM `usuariosf08_correos` WHERE `id`={$emailId} AND `registro_id`={$recordId} LIMIT 1"));
    if (!$email) f08_email_json(['success' => false, 'error' => 'Correo historico no encontrado.'], 404);
    $db->query("UPDATE `usuariosf08_correos` SET `estado`=" . f08_q($status) . ",`actualizado_por`={$operatorId},`fecha_actualizacion`=NOW() WHERE `id`={$emailId} LIMIT 1");
    f08_audit($recordId, 'CORREO_' . $status, 'Correo historico #' . $emailId . ' marcado como ' . strtolower($status), null, null);
    f08_email_json(['success' => true, 'message' => 'Estado actualizado.', 'rows' => f08_email_rows($recordId)]);
}

if ($action !== 'upload') f08_email_json(['success' => false, 'error' => 'Accion no valida.'], 400);
if (!isset($_FILES['archivo']) || (int)($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    f08_email_json(['success' => false, 'error' => 'Seleccione un archivo de correo valido.'], 422);
}

$file = $_FILES['archivo'];
$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > 20 * 1024 * 1024) {
    f08_email_json(['success' => false, 'error' => 'El archivo debe pesar como maximo 20 MB.'], 422);
}
$original = trim((string)($file['name'] ?? 'correo'));
$extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
$allowed = ['eml' => ['message/rfc822', 'text/plain', 'application/octet-stream'], 'msg' => ['application/vnd.ms-outlook', 'application/octet-stream'], 'pdf' => ['application/pdf']];
$mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
if (!isset($allowed[$extension]) || !in_array($mime, $allowed[$extension], true)) {
    f08_email_json(['success' => false, 'error' => 'Solo se permiten correos EML, MSG o una evidencia PDF.'], 422);
}

$relativeDir = 'f08-' . $recordId . '/' . date('Y') . '/' . date('m');
$directory = f08_email_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    f08_email_json(['success' => false, 'error' => 'No se pudo crear la carpeta historica.'], 500);
}
$storedName = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
$absolute = $directory . DIRECTORY_SEPARATOR . $storedName;
if (!move_uploaded_file((string)$file['tmp_name'], $absolute)) {
    f08_email_json(['success' => false, 'error' => 'No se pudo guardar el archivo en el servidor.'], 500);
}

$subject = mb_substr(trim((string)($_POST['asunto'] ?? '')), 0, 180);
$description = mb_substr(trim((string)($_POST['descripcion'] ?? '')), 0, 500);
$relative = $relativeDir . '/' . $storedName;
try {
    $db->query("INSERT INTO `usuariosf08_correos` (`registro_id`,`asunto`,`descripcion`,`nombre_original`,`nombre_archivo`,`ruta_relativa`,`mime_type`,`extension`,`tamano_bytes`,`estado`,`cargado_por`)
      VALUES ({$recordId}," . f08_q($subject) . ',' . f08_q($description) . ',' . f08_q(mb_substr($original, 0, 255)) . ',' . f08_q($storedName) . ',' . f08_q($relative) . ',' . f08_q($mime) . ',' . f08_q($extension) . ",{$size},'VALIDO',{$operatorId})");
} catch (Throwable $error) {
    @unlink($absolute);
    f08_email_json(['success' => false, 'error' => 'No se pudo registrar el correo en la base de datos.'], 500);
}
f08_audit($recordId, 'CORREO_ADJUNTO', 'Se adjunto el correo ' . mb_substr($original, 0, 220), null, null);
f08_email_json(['success' => true, 'message' => 'Correo archivado correctamente.', 'rows' => f08_email_rows($recordId)]);
?>
