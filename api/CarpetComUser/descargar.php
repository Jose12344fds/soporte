<?php
/**
 * api/CarpetComUser/descargar.php
 * GET?id=X            — Descarga el archivo como adjunto.
 * GET?id=X&preview=1  — Sirve el archivo en línea (imágenes / PDF).
 * Verifica que el usuario sea propietario, tenga acceso compartido, o sea admin.
 */
ob_start(); // captura cualquier salida accidental de los includes (whitespace, etc.)
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';

require_permiso('CarpetComUser', 'ver');

$user    = current_user();
$userId  = (int)$user['id'];
$isAdmin = (int)($user['user_level'] ?? 99) <= 1;

$archId  = (int)($_GET['id'] ?? 0);
$preview = ($_GET['preview'] ?? '0') === '1';

if (!$archId) {
    ob_end_clean();
    http_response_code(400);
    exit('Archivo no especificado.');
}

// Verificar acceso
if ($isAdmin) {
    $res = $db->query("SELECT * FROM carpeta_archivos WHERE id = {$archId}");
} else {
    $res = $db->query(
        "SELECT a.* FROM carpeta_archivos a
         WHERE a.id = {$archId}
           AND (
               a.user_id = {$userId}
               OR EXISTS (
                   SELECT 1 FROM carpeta_compartidos c
                   WHERE c.archivo_id = {$archId}
                     AND c.compartido_con = {$userId}
               )
           )"
    );
}

if ($db->num_rows($res) === 0) {
    ob_end_clean();
    http_response_code(403);
    exit('Acceso denegado.');
}

$file    = $db->fetch_assoc($res);
$ownerId = (int)$file['user_id'];

$filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
          . DIRECTORY_SEPARATOR . 'CarpetComUser'
          . DIRECTORY_SEPARATOR . $ownerId
          . DIRECTORY_SEPARATOR . $file['nombre_guardado'];

if (!file_exists($filePath)) {
    ob_end_clean();
    http_response_code(404);
    exit('El archivo ya no está disponible.');
}

$ext      = strtolower($file['extension']);
$origName = $file['nombre_original'];

// Determinar MIME real basado en extensión para mayor compatibilidad
$mimeMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf',
    'mp4'  => 'video/mp4',
    'mov'  => 'video/quicktime',
    'webm' => 'video/webm',
    'avi'  => 'video/x-msvideo',
    'mkv'  => 'video/x-matroska',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];
$mime = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';

// Limpiar cualquier salida acumulada ANTES de enviar el archivo
ob_end_clean();

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');

if ($preview) {
    header('Content-Disposition: inline; filename="' . rawurlencode($origName) . '"');
} else {
    header('Content-Disposition: attachment; filename="' . rawurlencode($origName) . '"');
}

readfile($filePath);
exit;
