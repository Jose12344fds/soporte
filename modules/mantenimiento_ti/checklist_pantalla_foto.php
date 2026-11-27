<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist_pantalla.php');

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'ver');

$id = (int)($_GET['id'] ?? 0);
$row = $id > 0 ? $db->fetch_assoc($db->query(
    "SELECT f.* FROM mti_checklist_pantalla_fotos f "
    . "INNER JOIN mti_checklist_pantalla c ON c.id=f.checklist_id WHERE f.id={$id} LIMIT 1"
)) : null;
if (!$row) { http_response_code(404); exit('La fotografía solicitada no existe.'); }

$root = mti_cp_storage_root();
$rootReal = realpath($root);
$fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta']));
if (!$rootReal || !$fileReal || !str_starts_with(strtolower($fileReal), strtolower($rootReal . DIRECTORY_SEPARATOR)) || !is_file($fileReal)) {
    http_response_code(404); exit('No se encontró la fotografía.');
}
$mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($fileReal);
if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true) || $mime !== (string)$row['mime']) {
    http_response_code(415); exit('El archivo no es una imagen válida.');
}
// La respuesta debe comenzar directamente con los bytes de la imagen.
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fileReal));
header('Content-Disposition: inline; filename="evidencia-' . $id . '.' . pathinfo($fileReal, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($fileReal);
exit;
