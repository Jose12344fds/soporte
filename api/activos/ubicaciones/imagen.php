<?php
require_once dirname(__DIR__, 3) . '/includes/load.php';
require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/api/activos/_interno/ubicaciones/imagenes.php';

require_permiso('activos', 'ver');

$id = (int)($_GET['id'] ?? 0);
$row = $id > 0 ? $db->fetch_assoc($db->query(
    "SELECT i.* FROM ubicaciones_fisicas_imagenes i "
    . "INNER JOIN ubicaciones_fisicas u ON u.IdUbicacion=i.IdUbicacion WHERE i.IdImagen={$id} LIMIT 1"
)) : null;
if (!$row) { http_response_code(404); exit('La imagen solicitada no existe.'); }

$file = ubi_img_safe_path((string)$row['Ruta']);
if (!$file) { http_response_code(404); exit('No se encontró el archivo solicitado.'); }
$mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg','image/png','image/webp','application/pdf'], true) || $mime !== (string)$row['Mime']) {
    http_response_code(415); exit('El archivo no es una evidencia válida.');
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="ubicacion-' . $id . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($file);
exit;
