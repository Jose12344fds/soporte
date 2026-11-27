<?php
declare(strict_types=1);

ob_start();
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
if (!$session->isUserLoggedIn(false) || !tiene_permiso('mantenimientos', 'ver')) { http_response_code(403); exit('Sin permiso.'); }
global $db;
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Identificador inválido.'); }
$result = $db->query('SELECT nombre_original,ruta_relativa,tamano_bytes FROM mantenimientos_equipo_manuales WHERE id='.$id.' LIMIT 1');
$row = $db->fetch_assoc($result);
if (!$row) { http_response_code(404); exit('Manual no encontrado.'); }
$root = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'mantenimientos'.DIRECTORY_SEPARATOR.'manuales_equipos';
$rootReal = realpath($root);
$path = $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$row['ruta_relativa']);
$pathReal = realpath($path);
if (!$rootReal || !$pathReal || !str_starts_with($pathReal, $rootReal.DIRECTORY_SEPARATOR) || !is_file($pathReal)) { http_response_code(404); exit('Archivo no encontrado.'); }
while (ob_get_level()) ob_end_clean();
$download = isset($_GET['download']) && $_GET['download'] === '1';
$name = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$row['nombre_original']) ?: 'manual_equipo.pdf';
header('Content-Type: application/pdf');
header('Content-Length: '.filesize($pathReal));
header('Content-Disposition: '.($download?'attachment':'inline').'; filename="'.$name.'"');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'self'");
header('Cache-Control: private, max-age=300');
readfile($pathReal);
