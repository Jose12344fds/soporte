<?php
declare(strict_types=1);

ob_start();
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if (!$session->isUserLoggedIn(false) || !tiene_permiso('mantenimientos', 'ver')) {
    http_response_code(403);
    exit('Sin permiso.');
}

global $db;
$id = (int)($_GET['id'] ?? 0);
$code = trim((string)($_GET['code'] ?? ''));
if ($id <= 0 && ($code === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $code))) {
    http_response_code(400);
    exit('Identificador invalido.');
}
$where = $id > 0
    ? 'id=' . $id
    : "sap_equipo_code='" . $db->escape($code) . "'";
$result = $db->query(
    'SELECT sap_equipo_code,nombre_original,ruta_relativa,mime_type FROM mantenimientos_equipo_imagenes WHERE ' . $where . ' LIMIT 1'
);
$row = $db->fetch_assoc($result);
if (!$row) { http_response_code(404); exit('Imagen no encontrada.'); }

$root = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'mantenimientos' . DIRECTORY_SEPARATOR . 'equipos';
$rootReal = realpath($root);
$path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta_relativa']);
$pathReal = realpath($path);
$rootNormalized = $rootReal ? strtolower(str_replace('\\', '/', rtrim($rootReal, "\\/"))) . '/' : '';
$pathNormalized = $pathReal ? strtolower(str_replace('\\', '/', $pathReal)) : '';
$isValidFile = $rootReal && $pathReal && str_starts_with($pathNormalized, $rootNormalized) && is_file($pathReal);

// A replacement can leave a stale DB path when an earlier upload was interrupted.
// In that case, safely recover the most recent file inside this equipment's own folder.
if (!$isValidFile) {
    $equipmentCode = (string)$row['sap_equipo_code'];
    if (preg_match('/^[A-Za-z0-9._-]+$/', $equipmentCode)) {
        $pattern = $root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*'
            . DIRECTORY_SEPARATOR . 'equipo_' . $equipmentCode . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}';
        $candidates = glob($pattern, GLOB_BRACE) ?: [];
        usort($candidates, static function (string $left, string $right): int {
            return (filemtime($right) ?: 0) <=> (filemtime($left) ?: 0);
        });
        if ($candidates) {
            $candidateReal = realpath($candidates[0]);
            $candidateNormalized = $candidateReal ? strtolower(str_replace('\\', '/', $candidateReal)) : '';
            if ($candidateReal && $rootReal && str_starts_with($candidateNormalized, $rootNormalized) && is_file($candidateReal)) {
                $pathReal = $candidateReal;
                $isValidFile = true;
            }
        }
    }
}
if (!$isValidFile) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

while (ob_get_level()) { ob_end_clean(); }
$detectedMime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($pathReal);
header('Content-Type: ' . (in_array($detectedMime, ['image/jpeg', 'image/png', 'image/webp'], true) ? $detectedMime : (string)$row['mime_type']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
readfile($pathReal);
exit;
