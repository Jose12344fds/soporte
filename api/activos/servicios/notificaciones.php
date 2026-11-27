<?php
ob_start();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{
 require_once dirname(__DIR__).'/../../includes/load.php';require_once __DIR__ . '/../../../api/activos/_interno/servicios/renovaciones.php';require_once __DIR__ . '/../../../api/activos/_interno/aplicaciones/renovaciones.php';
 if(!$session->isUserLoggedIn(false)||!tiene_permiso('activos','ver')){http_response_code(403);throw new RuntimeException('No autorizado.');}
 $db->query('SET NAMES utf8mb4');$uid=(int)$_SESSION['user_id'];$rows=sr_alerts($uid);foreach($rows as &$r)$r['origen']='servicio';unset($r);$apps=ar_alerts($uid);foreach($apps as &$r)$r['origen']='aplicacion';unset($r);$rows=array_merge($rows,$apps);usort($rows,fn($a,$b)=>strcmp($a['FechaFin'],$b['FechaFin'])?:strcmp($a['origen'],$b['origen'])?:((int)$a['IdRenovacion']<=>(int)$b['IdRenovacion']));$method=$_SERVER['REQUEST_METHOD']??'GET';
 if($method==='POST'){
  csrf_validate_or_fail();$p=json_decode(file_get_contents('php://input'),true);if(!is_array($p))throw new RuntimeException('Datos no válidos.');$id=(int)($p['id_renovacion']??0);$origin=$p['origen']??'servicio';$all=($p['action']??'')==='leer_todas';if(!$all&&($id<=0||!in_array($origin,['servicio','aplicacion'],true)))throw new RuntimeException('Aviso no válido.');
  foreach($rows as $r)if($all||($id===(int)$r['IdRenovacion']&&$origin===$r['origen'])){$rid=(int)$r['IdRenovacion'];$hash=$db->escape($r['firma']);$table=$r['origen']==='aplicacion'?'activos_aplicaciones_avisos_vistos':'activos_servicios_avisos_vistos';$db->query("INSERT INTO $table (IdUsuario,IdRenovacion,FirmaAviso) VALUES ($uid,$rid,'$hash') ON DUPLICATE KEY UPDATE FirmaAviso='$hash',VistoEn=NOW()");}
  ob_end_clean();echo json_encode(['success'=>true]);exit;
 }
 if($method!=='GET'){http_response_code(405);throw new RuntimeException('Método no permitido.');}
 $unread=count(array_filter($rows,fn($r)=>!$r['leido']));$base=defined('BASE_URL')?rtrim(BASE_URL,'/'):'/soporte';
 $items=array_map(function($r)use($base){$app=$r['origen']==='aplicacion';return ['id'=>(int)$r['IdRenovacion'],'origen'=>$r['origen'],'servicio'=>$r['NombreServicio'],'codigo'=>($app?'Aplicación · ':'Servicio · ').$r['CodigoServicio'],'periodo'=>$r['Etiqueta'],'fin'=>$r['FechaFin'],'aviso'=>$r['FechaAviso'],'dias'=>$r['dias'],'tipo'=>$r['tipo'],'leido'=>$r['leido'],'url'=>$base.'/modules/activos/'.($app?'aplicaciones.php?contrato='.(int)$r['IdContrato']:'servicios.php?servicio='.(int)$r['IdServicio']).'&renovacion='.(int)$r['IdRenovacion']];},$rows);
 ob_end_clean();echo json_encode(['success'=>true,'no_leidas'=>$unread,'total'=>count($items),'avisos'=>array_slice($items,0,30),'hoy'=>sr_today()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
}catch(Throwable $e){if(ob_get_level())ob_end_clean();if(http_response_code()<400)http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}
