<?php
/**
 * Carga multiple de archivos para CarpetComUser.
 * Espera multipart/form-data con el campo files[].
 */
ob_start();
header('Content-Type: application/json; charset=utf-8');

@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');
@ini_set('memory_limit', '256M');
set_time_limit(300);

$GLOBALS['carpetUploadResponseSent'] = false;

function carpet_upload_json(array $data, int $status = 200): void
{
    $GLOBALS['carpetUploadResponseSent'] = true;
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );
    exit;
}

function carpet_ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') return 0;
    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    if ($unit === 'g') $number *= 1024;
    if ($unit === 'g' || $unit === 'm') $number *= 1024;
    if (in_array($unit, ['g', 'm', 'k'], true)) $number *= 1024;
    return (int)$number;
}

function carpet_upload_error(int $code): string
{
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'supera el limite permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE  => 'supera el limite permitido por el formulario',
        UPLOAD_ERR_PARTIAL    => 'solo se recibio una parte del archivo',
        UPLOAD_ERR_NO_FILE    => 'no se recibio el archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'el servidor no tiene carpeta temporal disponible',
        UPLOAD_ERR_CANT_WRITE => 'el servidor no pudo escribir el archivo',
        UPLOAD_ERR_EXTENSION  => 'una extension de PHP detuvo la carga',
    ];
    return $messages[$code] ?? ('error de carga ' . $code);
}

register_shutdown_function(function (): void {
    if (!empty($GLOBALS['carpetUploadResponseSent'])) return;
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => 'El servidor interrumpio la carga. Revise el registro del sistema.',
    ]);
});

require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_permiso('CarpetComUser', 'crear');

$user = current_user();
$userId = (int)($user['id'] ?? 0);
$area = (string)($user['area'] ?? '');
if ($userId <= 0) carpet_upload_json(['ok' => false, 'error' => 'Sesion de usuario no valida.'], 401);

$serverUploadMax = carpet_ini_bytes((string)ini_get('upload_max_filesize'));
$postMax = carpet_ini_bytes((string)ini_get('post_max_size'));
$configuredMax = 110 * 1024 * 1024;
$maxFileSize = $serverUploadMax > 0 ? min($configuredMax, $serverUploadMax) : $configuredMax;
$maxLabel = max(1, (int)floor($maxFileSize / 1024 / 1024));
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

if ($postMax > 0 && $contentLength > $postMax) {
    carpet_upload_json([
        'ok' => false,
        'error' => 'El lote pesa demasiado. Se enviara nuevamente dividido en grupos mas pequenos.',
        'limit_mb' => (int)floor($postMax / 1024 / 1024),
    ], 413);
}

if (empty($_FILES['files'])) {
    carpet_upload_json([
        'ok' => false,
        'error' => 'No se recibieron archivos. El lote puede haber superado el limite del servidor.',
    ], 400);
}

$allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4', 'mov', 'webm', 'avi', 'mkv'];
$allowedMime = [
    'image/jpeg',
    'image/pjpeg',
    'image/png',
    'image/x-png',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];

$videoMime = [
    'mp4' => ['video/mp4', 'application/mp4'],
    'mov' => ['video/quicktime'],
    'webm' => ['video/webm'],
    'avi' => ['video/x-msvideo', 'video/avi', 'video/msvideo'],
    'mkv' => ['video/x-matroska', 'video/matroska'],
];

$uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
    . DIRECTORY_SEPARATOR . 'CarpetComUser'
    . DIRECTORY_SEPARATOR . $userId . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    carpet_upload_json(['ok' => false, 'error' => 'No se pudo preparar la carpeta de destino.'], 500);
}
if (!is_writable($uploadDir)) {
    carpet_upload_json(['ok' => false, 'error' => 'La carpeta de destino no tiene permiso de escritura.'], 500);
}

mysqli_report(MYSQLI_REPORT_OFF);
$insertDb = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($insertDb->connect_errno) {
    carpet_upload_json(['ok' => false, 'error' => 'No se pudo conectar con el registro de archivos.'], 503);
}
$insertDb->set_charset('utf8mb4');
$insert = $insertDb->prepare(
    'INSERT INTO carpeta_archivos '
    . '(user_id, nombre_original, nombre_guardado, tipo_mime, extension, tamano, area) '
    . 'VALUES (?, ?, ?, ?, ?, ?, ?)'
);
if (!$insert) {
    error_log('CarpetComUser upload prepare: ' . $insertDb->error);
    carpet_upload_json(['ok' => false, 'error' => 'No se pudo preparar el registro de archivos.'], 500);
}

$files = $_FILES['files'];
$count = is_array($files['name']) ? count($files['name']) : 1;
$subidos = [];
$errores = [];
$finfo = new finfo(FILEINFO_MIME_TYPE);

for ($i = 0; $i < $count; $i++) {
    $origName = (string)(is_array($files['name']) ? $files['name'][$i] : $files['name']);
    $tmpName = (string)(is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name']);
    $size = (int)(is_array($files['size']) ? $files['size'][$i] : $files['size']);
    $errCode = (int)(is_array($files['error']) ? $files['error'][$i] : $files['error']);
    $safeDisplayName = basename(str_replace("\0", '', $origName));

    if ($errCode !== UPLOAD_ERR_OK) {
        $errores[] = $safeDisplayName . ': ' . carpet_upload_error($errCode) . '.';
        continue;
    }
    if ($size <= 0 || !is_uploaded_file($tmpName)) {
        $errores[] = $safeDisplayName . ': el archivo temporal no es valido.';
        continue;
    }
    if ($size > $maxFileSize) {
        $errores[] = $safeDisplayName . ': excede el limite activo de ' . $maxLabel . ' MB.';
        continue;
    }

    $origName = $safeDisplayName;
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $mime = (string)$finfo->file($tmpName);
    if (!in_array($ext, $allowedExt, true) || !in_array($mime, $videoMime[$ext] ?? $allowedMime, true)) {
        $errores[] = $origName . ': tipo de archivo no permitido (' . ($mime ?: 'desconocido') . ').';
        continue;
    }

    try {
        $savedName = bin2hex(random_bytes(16)) . '.' . $ext;
    } catch (Throwable $e) {
        $errores[] = $origName . ': no se pudo generar un nombre seguro.';
        continue;
    }
    $dest = $uploadDir . $savedName;
    if (!move_uploaded_file($tmpName, $dest)) {
        $errores[] = $origName . ': no se pudo guardar en el servidor.';
        continue;
    }

    $insert->bind_param('issssis', $userId, $origName, $savedName, $mime, $ext, $size, $area);
    if (!$insert->execute()) {
        @unlink($dest);
        error_log('CarpetComUser upload insert [' . $origName . ']: ' . $insert->error);
        $errores[] = $origName . ': no se pudo registrar en la base de datos.';
        continue;
    }

    $subidos[] = [
        'indice' => $i,
        'id' => $insert->insert_id,
        'nombre' => $origName,
        'ext' => $ext,
        'tamano' => $size,
    ];
}

$insert->close();
$insertDb->close();

carpet_upload_json([
    'ok' => count($subidos) > 0,
    'subidos' => $subidos,
    'errores' => $errores,
    'limite_archivo_mb' => $maxLabel,
]);
