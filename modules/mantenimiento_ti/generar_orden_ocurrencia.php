<?php
require_once(__DIR__.'/../../includes/load.php');
require_once(__DIR__.'/../../config/app.php');
require_once(__DIR__.'/funciones_plan.php');
require_permiso('mantenimiento_ti','crear');

$occurrenceId=(int)($_POST['ocurrencia_id']??0);
if($occurrenceId<=0){$session->msg('d','Ocurrencia invalida.');redirect('ocurrencias.php',false);}
$result=$db->query(
 "SELECT o.*,p.IdMaquina,p.tipo,p.prioridad,p.instruccion"
 ." FROM activos_mantenimiento_ocurrencias o INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
 ." WHERE o.id={$occurrenceId} LIMIT 1"
);
$row=$db->fetch_assoc($result);
if(!$row){$session->msg('d','La ocurrencia no existe.');redirect('ocurrencias.php',false);}
if(!empty($row['mantenimiento_id'])){redirect('detalle.php?id='.(int)$row['mantenimiento_id'],false);}
if(in_array($row['estado'],['CANCELADA','EJECUTADA'],true)){$session->msg('d','Esta ocurrencia ya no permite generar una orden.');redirect('ocurrencias.php',false);}

// Reclamo atomico: evita dos OT por doble clic o usuarios simultaneos.
$db->query(
 "UPDATE activos_mantenimiento_ocurrencias SET estado='GENERANDO_OT'"
 ." WHERE id={$occurrenceId} AND mantenimiento_id IS NULL AND estado NOT IN ('GENERANDO_OT','CANCELADA','EJECUTADA') LIMIT 1"
);
if($db->affected_rows()!==1){
 $check=$db->query("SELECT mantenimiento_id FROM activos_mantenimiento_ocurrencias WHERE id={$occurrenceId} LIMIT 1");$current=$db->fetch_assoc($check);
 if(!empty($current['mantenimiento_id']))redirect('detalle.php?id='.(int)$current['mantenimiento_id'],false);
 $session->msg('d','La ocurrencia esta siendo procesada por otro usuario.');redirect('ocurrencias.php',false);
}

$user=current_user();$userId=(int)($user['id']??0);
$q=function($v)use($db){return "'".$db->escape((string)$v)."'";};
$reason='Mantenimiento programado.';
$assignedResult=$db->query(
 "SELECT a.id AS user_id,u.name FROM asig_maquina a LEFT JOIN users u ON u.id=a.id"
 ." WHERE a.IdMaquina=".(int)$row['IdMaquina']." AND a.Estado=1 ORDER BY a.IdAsMaq DESC LIMIT 1"
);
$assigned=$db->fetch_assoc($assignedResult);
$assignedId=$assigned?(int)$assigned['user_id']:null;
$assignedName=$assigned?trim((string)($assigned['name']??'')):'';
$assignedIdSql=$assignedId===null?'NULL':(string)$assignedId;
$assignedNameSql=$assignedName===''?'NULL':$q($assignedName);
$db->query(
 "INSERT INTO activos_mantenimientos (IdMaquina,tipo,prioridad,estado,fecha_programada,motivo,tecnico_responsable,usuario_asignado_id,usuario_asignado_nombre,creado_por,actualizado_por) VALUES ("
 .(int)$row['IdMaquina'].','.$q($row['tipo']).','.$q($row['prioridad']).",'PROGRAMADO',".$q($row['fecha_programada']).','.$q($reason).','
 ."NULL,{$assignedIdSql},{$assignedNameSql},{$userId},{$userId})"
);
$orderId=(int)$db->insert_id();$folio='MTI-'.date('Y').'-'.str_pad((string)$orderId,6,'0',STR_PAD_LEFT);
$db->query("UPDATE activos_mantenimientos SET folio=".$q($folio)." WHERE id={$orderId} LIMIT 1");
$db->query("UPDATE activos_mantenimiento_ocurrencias SET mantenimiento_id={$orderId},estado='OT_GENERADA',actualizado_por={$userId} WHERE id={$occurrenceId} LIMIT 1");
$db->query("INSERT INTO activos_mantenimiento_historial (mantenimiento_id,accion,estado_nuevo,comentario,registrado_por) VALUES ({$orderId},'CREACION','PROGRAMADO','Orden generada desde una ocurrencia del plan',{$userId})");
$db->query("INSERT INTO activos_mantenimiento_ocurrencia_historial (ocurrencia_id,accion,fecha_nueva,estado_anterior,estado_nuevo,comentario,registrado_por) VALUES ({$occurrenceId},'GENERAR_OT',".$q($row['fecha_programada']).",'PENDIENTE','OT_GENERADA',".$q('Orden '.$folio.' generada').",{$userId})");
$session->msg('s','Orden '.$folio.' generada y vinculada correctamente.');redirect('detalle.php?id='.$orderId,false);
