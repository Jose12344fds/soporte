<?php
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/bootstrap.php';

inc_schema();
require_permiso('incidencias', 'ver');

$id = (int)($_GET['id'] ?? 0);
$rows = find_by_sql(
    "SELECT a.*,t.SolicitanteId
     FROM inc_adjuntos a
     INNER JOIN inc_tickets t ON t.IdTicket=a.IdTicket
     WHERE a.IdAdjunto={$id} AND a.Activo=1 LIMIT 1"
);
if (!$rows || !inc_can_view_ticket($rows[0])) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$file = $rows[0];
$root = realpath(inc_upload_root());
$path = realpath(inc_upload_root().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$file['Ruta']));
if (!$root || !$path || strpos($path, $root.DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit('El archivo fisico no esta disponible.');
}

while (ob_get_level()) ob_end_clean();
$mime = trim((string)$file['Mime']) ?: 'application/octet-stream';
$inline = strpos($mime, 'image/') === 0 || $mime === 'application/pdf' || $mime === 'text/plain';
$name = str_replace(["\r", "\n", '"'], '', basename((string)$file['NombreOriginal']));
$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'evidencia';
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Content-Disposition: '.($inline ? 'inline' : 'attachment').'; filename="'.$asciiName.'"; filename*=UTF-8\'\''.rawurlencode($name));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;

