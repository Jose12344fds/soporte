<?php
ob_start();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');$lock=null;$tx=false;
try{
 require_once dirname(__DIR__).'/../../includes/load.php';require_once __DIR__ . '/../../../api/activos/_interno/servicios/renovaciones.php';
 if(!$session->isUserLoggedIn(false)||!tiene_permiso('activos','ver')){http_response_code(403);throw new RuntimeException('No autorizado.');}$db->query('SET NAMES utf8mb4');
 $method=$_SERVER['REQUEST_METHOD']??'GET';$body=$method==='POST'?json_decode(file_get_contents('php://input'),true):$_GET;
 if(!is_array($body))throw new RuntimeException('Datos inválidos.');$sid=(int)($body['id_servicio']??0);$srv=find_by_sql("SELECT IdServicio,CodigoServicio,NombreServicio,Proveedor,FechaInicio,FechaFin,EstadoServicio FROM servicios_ti WHERE IdServicio=$sid LIMIT 1");if(!$srv)throw new RuntimeException('Servicio no encontrado.');
 $can=tiene_permiso('activos','editar');
 if($method==='GET'){
  $table=$db->query("SHOW TABLES LIKE 'activos_servicios_facturas'");$hasDocs=$db->num_rows($table)>0;$docs='0';
  if($hasDocs){$col=$db->query("SHOW COLUMNS FROM activos_servicios_facturas LIKE 'IdRenovacion'");if($db->num_rows($col))$docs='(SELECT COUNT(*) FROM activos_servicios_facturas f WHERE f.IdRenovacion=r.IdRenovacion AND f.IdServicio=r.IdServicio)';}
  $rows=find_by_sql("SELECT r.*,$docs AS TotalDocumentos FROM activos_servicios_renovaciones r WHERE r.IdServicio=$sid ORDER BY r.FechaFin DESC,r.IdRenovacion DESC");ob_end_clean();echo json_encode(['success'=>true,'servicio'=>$srv[0],'periodos'=>$rows,'can_edit'=>$can,'hoy'=>sr_today()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
 }
 if($method!=='POST'){http_response_code(405);throw new RuntimeException('Método no permitido.');}if(!$can){http_response_code(403);throw new RuntimeException('Sin permiso para gestionar renovaciones.');}csrf_validate_or_fail();
 $lock=fopen(dirname(dirname(dirname(__DIR__))).'/storage/srv_renovacion_'.$sid.'.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Hay otra operación en curso. Intenta nuevamente.');
 $action=$body['action']??'guardar';$id=(int)($body['id_renovacion']??0);$uid=(int)$_SESSION['user_id'];$current=$id?find_by_sql("SELECT * FROM activos_servicios_renovaciones WHERE IdRenovacion=$id AND IdServicio=$sid LIMIT 1"):[];$q=fn($v)=>$v===null?'NULL':"'".$db->escape((string)$v)."'";
 if($id&&!$current)throw new RuntimeException('Periodo no encontrado.');
 if($action==='guardar'){
  $m=sr_validate($body);$state=$id?$current[0]['Estado']:strtoupper($body['estado']??'BORRADOR');if(!in_array($state,['BORRADOR','VIGENTE'],true))throw new RuntimeException('Los periodos anteriores son de consulta.');
  $active=find_by_sql("SELECT * FROM activos_servicios_renovaciones WHERE IdServicio=$sid AND Estado='VIGENTE' AND IdRenovacion<>$id ORDER BY FechaFin DESC LIMIT 1");
  if($state==='VIGENTE'&&$active)throw new RuntimeException('Ya existe un periodo vigente. Prepara un borrador y confirma su renovación.');
  $duplicates=find_by_sql("SELECT IdRenovacion FROM activos_servicios_renovaciones WHERE IdServicio=$sid AND FechaInicio=".$q($m['inicio'])." AND FechaFin=".$q($m['fin'])." AND Estado<>'CANCELADO' AND IdRenovacion<>$id");if($duplicates)throw new RuntimeException('Ya existe un periodo con esas fechas.');
  $db->query('START TRANSACTION');$tx=true;
  if($id)$db->query("UPDATE activos_servicios_renovaciones SET Etiqueta=".$q($m['etiqueta']).",FechaInicio=".$q($m['inicio']).",FechaFin=".$q($m['fin']).",FechaAviso=".$q($m['aviso']).",Proveedor=".$q($m['proveedor']).",Importe=".$q($m['importe']).",Moneda=".$q($m['moneda']).",Notas=".$q($m['notas']).",ActualizadoEn=NOW() WHERE IdRenovacion=$id");
  else{$previous=$active?(int)$active[0]['IdRenovacion']:0;$db->query("INSERT INTO activos_servicios_renovaciones (IdServicio,Etiqueta,FechaInicio,FechaFin,FechaAviso,Estado,IdAnterior,Proveedor,Importe,Moneda,Notas,CreadoPor) VALUES ($sid,".$q($m['etiqueta']).",".$q($m['inicio']).",".$q($m['fin']).",".$q($m['aviso']).",".$q($state).",".($previous?:'NULL').",".$q($m['proveedor']).",".$q($m['importe']).",".$q($m['moneda']).",".$q($m['notas']).",$uid)");$id=(int)$db->insert_id();}
  if($state==='VIGENTE')$db->query("UPDATE servicios_ti SET FechaInicio=".$q($m['inicio']).",FechaFin=".$q($m['fin'])." WHERE IdServicio=$sid");
 }elseif($action==='activar'){
  if(!$current||$current[0]['Estado']!=='BORRADOR')throw new RuntimeException('Sólo se confirma un periodo en borrador.');$r=$current[0];$active=find_by_sql("SELECT * FROM activos_servicios_renovaciones WHERE IdServicio=$sid AND Estado='VIGENTE' LIMIT 1");if($active&&$r['FechaInicio']<=$active[0]['FechaFin'])throw new RuntimeException('El nuevo periodo debe empezar después del fin del periodo vigente.');
  $db->query('START TRANSACTION');$tx=true;$db->query("UPDATE activos_servicios_renovaciones SET Estado='RENOVADO',ActualizadoEn=NOW() WHERE IdServicio=$sid AND Estado='VIGENTE'");$db->query("UPDATE activos_servicios_renovaciones SET Estado='VIGENTE',ActualizadoEn=NOW() WHERE IdRenovacion=$id AND IdServicio=$sid");$db->query("UPDATE servicios_ti SET FechaInicio=".$q($r['FechaInicio']).",FechaFin=".$q($r['FechaFin'])." WHERE IdServicio=$sid");
 }elseif($action==='cancelar'){
  if(!$current||$current[0]['Estado']!=='BORRADOR')throw new RuntimeException('Sólo se descarta un borrador.');$db->query('START TRANSACTION');$tx=true;$db->query("UPDATE activos_servicios_renovaciones SET Estado='CANCELADO',ActualizadoEn=NOW() WHERE IdRenovacion=$id AND IdServicio=$sid");
 }else throw new RuntimeException('Operación no válida.');
 if($tx){$db->query('COMMIT');$tx=false;}flock($lock,LOCK_UN);fclose($lock);$lock=null;ob_end_clean();echo json_encode(['success'=>true,'id_renovacion'=>$id,'message'=>'Periodo actualizado correctamente.'],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){if($tx)$db->query('ROLLBACK');if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}if(ob_get_level())ob_end_clean();if(http_response_code()<400)http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getCode()===1062?'El periodo ya está registrado. Actualiza el historial.':$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}