<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'ver');

$photoId = (int)($_GET['foto'] ?? 0);
$id = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['tipo'] ?? '');
$column = $type === 'antes' ? 'foto_antes' : ($type === 'despues' ? 'foto_despues' : '');
if ($photoId <= 0 && ($id <= 0 || $column === '')) {
    http_response_code(400);
    exit('Solicitud de imagen no valida.');
}

$result = $photoId > 0
    ? $db->query("SELECT ruta,LOWER(etapa) tipo FROM activos_mantenimiento_fotos WHERE id={$photoId} LIMIT 1")
    : $db->query("SELECT {$column} AS ruta,'" . $db->escape($type) . "' AS tipo FROM activos_mantenimientos WHERE id={$id} LIMIT 1");
$row = $db->fetch_assoc($result);
if (!$row || empty($row['ruta'])) {
    http_response_code(404);
    exit('La evidencia solicitada no existe.');
}

$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'mantenimiento_ti';
$rootReal = realpath($root);
$fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta']));
if (!$rootReal || !$fileReal
    || !str_starts_with(strtolower($fileReal), strtolower($rootReal . DIRECTORY_SEPARATOR))
    || !is_file($fileReal)) {
    http_response_code(404);
    exit('No se encontro el archivo de evidencia.');
}

$mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($fileReal);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(415);
    exit('El archivo no es una imagen valida.');
}

// Algunos archivos heredados imprimen espacios al cargarse; una imagen debe empezar directamente con sus bytes.
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fileReal));
header('Content-Disposition: inline; filename="' . ($row['tipo'] ?? $type) . '-' . ($photoId ?: $id) . '.' . pathinfo($fileReal, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($fileReal);
exit;
