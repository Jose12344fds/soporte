<?php
require_once(__DIR__.'/../../includes/load.php');require_once(__DIR__.'/../../config/app.php');require_once(__DIR__.'/funciones_checklist.php');
ensure_mantenimiento_ti_schema();require_permiso('mantenimiento_ti','ver');mti_chk_require_schema();
$id=(int)($_GET['id']??0);$row=$db->fetch_assoc($db->query("SELECT * FROM activos_mantenimiento_hallazgo_evidencias WHERE id={$id} LIMIT 1"));if(!$row){http_response_code(404);exit;}
$root=realpath(mti_chk_evidence_root());$file=realpath(mti_chk_evidence_root().DIRECTORY_SEPARATOR.str_replace(['/', '\\'],DIRECTORY_SEPARATOR,$row['archivo_ruta']));
if(!$root||!$file||!str_starts_with($file,$root.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);exit;}
header('Content-Type: '.$row['archivo_mime']);header('Content-Length: '.filesize($file));header('Content-Disposition: inline; filename="'.rawurlencode($row['archivo_nombre_original']).'"');header('X-Content-Type-Options: nosniff');readfile($file);
