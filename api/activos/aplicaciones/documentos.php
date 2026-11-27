<?php
ob_start();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{
 require_once dirname(__DIR__).'/../../includes/load.php';require_once __DIR__ . '/../../../api/activos/_interno/aplicaciones/documentos.php';
 if(!$session->isUserLoggedIn(false)){http_response_code(401);throw new RuntimeException('La sesión venció. Inicia sesión nuevamente.');}
 if(!tiene_permiso('activos','ver')){http_response_code(403);throw new RuntimeException('Sin permiso para consultar servicios.');}
 $db->query('SET NAMES utf8mb4');
 
 
 $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
 if($method==='GET'&&isset($_GET['archivo'])){
  $id=(int)$_GET['archivo'];$r=find_by_sql("SELECT * FROM activos_aplicaciones_documentos WHERE IdFactura=$id LIMIT 1");if(!$r){http_response_code(404);throw new RuntimeException('Documento no encontrado.');}$r=$r[0];$name=basename($r['NombreGuardado']);$path=dirname(dirname(dirname(__DIR__))).'/storage/aplicaciones_documentos/'.$name;if(!is_file($path)){http_response_code(404);throw new RuntimeException('El archivo no está disponible.');}
  $inline=($_GET['vista']??'')==='1'&&in_array($r['Mime'],['application/pdf','image/jpeg','image/png'],true);
  ob_end_clean();header('Content-Type: '.$r['Mime']);header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));$fallback=preg_replace('/[^A-Za-z0-9._-]/','_',$r['NombreOriginal']);header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.$fallback.'"; filename*=UTF-8\'\''.rawurlencode($r['NombreOriginal']));readfile($path);exit;
 }
 $service=(int)($method==='POST'?($_POST['id_servicio']??0):($_GET['id_servicio']??0));$srv=ar_contract($service);if(!$srv)throw new RuntimeException('Contrato no encontrado.');
 $canUpload=tiene_permiso('activos','crear')||tiene_permiso('activos','editar');
 if($method==='GET'){
  $where="IdContrato=$service";$year=(int)($_GET['anio']??0);if($year){if($year<2000||$year>2100)throw new RuntimeException('Año inválido.');$where.=" AND Anio=$year";}
  $period=(string)($_GET['mes']??'');if($period==='anual')$where.=" AND TipoPeriodo='ANUAL'";elseif($period!==''){$month=(int)$period;if($month<1||$month>12)throw new RuntimeException('Mes inválido.');$where.=" AND TipoPeriodo='MENSUAL' AND Mes=$month";}
  $rid=(int)($_GET['id_renovacion']??0);if($rid)$where.=" AND f.IdRenovacion=$rid";
  $dtype=strtoupper((string)($_GET['tipo_documento']??''));if(in_array($dtype,['FACTURA','COTIZACION','CONTRATO','ORDEN_COMPRA','OTRO'],true))$where.=" AND f.TipoDocumento='".$db->escape($dtype)."'";
  $periodos=find_by_sql("SELECT IdRenovacion,Etiqueta,FechaInicio,FechaFin,Estado FROM activos_aplicaciones_renovaciones WHERE IdContrato=$service ORDER BY FechaFin DESC,IdRenovacion DESC");
  $rows=find_by_sql("SELECT f.IdFactura,f.IdContrato,f.TipoPeriodo,f.Anio,f.Mes,f.NumeroFactura,f.FechaEmision,f.Importe,f.Moneda,f.Notas,f.IdRenovacion,f.TipoDocumento,f.NombreOriginal,f.Mime,f.Tamano,f.FechaRegistro,COALESCE(u.name,'') AS Usuario FROM activos_aplicaciones_documentos f LEFT JOIN users u ON u.id=f.CreadoPor WHERE $where ORDER BY Anio DESC,COALESCE(Mes,13) DESC,f.FechaRegistro DESC,f.IdFactura DESC");
  $years=find_by_sql("SELECT DISTINCT Anio FROM activos_aplicaciones_documentos WHERE IdContrato=$service ORDER BY Anio DESC");
  ob_end_clean();echo json_encode(['success'=>true,'servicio'=>$srv[0],'can_upload'=>$canUpload,'can_edit'=>tiene_permiso('activos','editar'),'renovaciones'=>$periodos,'facturas'=>$rows,'anios'=>array_map(fn($y)=>(int)$y['Anio'],$years),'limites'=>['archivo'=>min(15*1048576,af_ini_bytes(ini_get('upload_max_filesize'))?:15*1048576),'carga'=>af_ini_bytes(ini_get('post_max_size'))]],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
 }
 if($method!=='POST'){http_response_code(405);throw new RuntimeException('Método no permitido.');}
 if(!$canUpload){http_response_code(403);throw new RuntimeException('Sin permiso para adjuntar facturas.');}csrf_validate_or_fail();$m=($_POST['action']??'')==='vincular'?[]:af_metadata($_POST);
 if(($_POST['action']??'')==='vincular'){
   if(!tiene_permiso('activos','editar'))throw new RuntimeException('Sin permiso para vincular documentos.');$doc=(int)($_POST['id_documento']??0);$rid=(int)($_POST['id_renovacion']??0);
   if(!$rid||!find_by_sql("SELECT IdRenovacion FROM activos_aplicaciones_renovaciones WHERE IdContrato=$service AND IdRenovacion=$rid"))throw new RuntimeException('Selecciona una renovación de este servicio.');
   $dtype=strtoupper((string)($_POST['tipo_documento']??'FACTURA'));if(!in_array($dtype,['FACTURA','COTIZACION','CONTRATO','ORDEN_COMPRA','OTRO'],true))throw new RuntimeException('Tipo de documento inválido.');
   if(!find_by_sql("SELECT IdFactura FROM activos_aplicaciones_documentos WHERE IdFactura=$doc AND IdContrato=$service"))throw new RuntimeException('Documento no encontrado.');
   $dtype=$db->escape($dtype);$db->query("UPDATE activos_aplicaciones_documentos SET IdRenovacion=$rid,TipoDocumento='$dtype' WHERE IdFactura=$doc AND IdContrato=$service");ob_end_clean();echo json_encode(['success'=>true]);exit;
 }
 $files=$_FILES['archivos']??null;if(!$files||!is_array($files['name']??null)||!count($files['name']))throw new RuntimeException('Selecciona al menos un documento. Si supera el límite del servidor, carga un archivo a la vez.');if(count($files['name'])>10)throw new RuntimeException('Puedes adjuntar hasta 10 archivos por carga.');
 $renovacion=(int)($_POST['id_renovacion']??0);$documentType=strtoupper((string)($_POST['tipo_documento']??'FACTURA'));
 if(!in_array($documentType,['FACTURA','COTIZACION','CONTRATO','ORDEN_COMPRA','OTRO'],true))throw new RuntimeException('Tipo de documento inválido.');
 if($renovacion&&!find_by_sql("SELECT IdRenovacion FROM activos_aplicaciones_renovaciones WHERE IdRenovacion=$renovacion AND IdContrato=$service"))throw new RuntimeException('La renovación no pertenece a este servicio.');
 $dir=af_directory();$inserted=0;$skipped=0;$errors=[];$uid=(int)($_SESSION['user_id']??0);
 foreach($files['name'] as $i=>$original){$dest=null;try{
  if(($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('No se recibió el archivo completo; revisa su tamaño.');$tmp=$files['tmp_name'][$i];if(!is_uploaded_file($tmp))throw new RuntimeException('Carga no válida.');$f=af_file($tmp,(string)$original,(int)$files['size'][$i]);$hash=$db->escape($f['hash']);if(find_by_sql("SELECT IdFactura FROM activos_aplicaciones_documentos WHERE IdContrato=$service AND Sha256='$hash' LIMIT 1")){$skipped++;continue;}
  $stored=bin2hex(random_bytes(20)).'.bin';$dest=$dir.'/'.$stored;if(!move_uploaded_file($tmp,$dest))throw new RuntimeException('No se pudo almacenar el documento.');
  $q=fn($v)=>$v===null?'NULL':"'".$db->escape((string)$v)."'";
  $db->query("INSERT INTO activos_aplicaciones_documentos (IdRenovacion,TipoDocumento,IdContrato,TipoPeriodo,Anio,Mes,NumeroFactura,FechaEmision,Importe,Moneda,Notas,NombreOriginal,NombreGuardado,Mime,Tamano,Sha256,CreadoPor) VALUES (".($renovacion?:'NULL').",".$q($documentType).",$service,".$q($m['tipo']).",".$m['anio'].",".$q($m['mes']).",".$q($m['numero']).",".$q($m['fecha']).",".$q($m['importe']).",".$q($m['moneda']).",".$q($m['notas']).",".$q($f['nombre']).",".$q($stored).",".$q($f['mime']).",".(int)$files['size'][$i].",'$hash',$uid)");$inserted++;$dest=null;
 }catch(Throwable $e){if($dest&&is_file($dest))unlink($dest);if((int)$e->getCode()===1062){$skipped++;continue;}$errors[]=['indice'=>$i,'archivo'=>(string)$original,'error'=>$e->getMessage()];}}
 ob_end_clean();echo json_encode(['success'=>$inserted>0||$skipped>0,'guardados'=>$inserted,'duplicados'=>$skipped,'errores'=>$errors],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
}catch(Throwable $e){if(ob_get_level())ob_end_clean();if(http_response_code()<400)http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}