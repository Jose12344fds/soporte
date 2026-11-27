<?php
require_once(__DIR__.'/../../includes/load.php');require_once(__DIR__.'/../../config/app.php');require_once(__DIR__.'/funciones_checklist.php');
ensure_mantenimiento_ti_schema();require_permiso('mantenimiento_ti','editar');csrf_require();mti_chk_require_schema();
$findingId=(int)($_POST['hallazgo_id']??0);
$finding=$db->fetch_assoc($db->query("SELECT h.*,m.IdMaquina FROM activos_mantenimiento_hallazgos h INNER JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id WHERE h.id={$findingId} LIMIT 1"));
if(!$finding){$session->msg('d','Hallazgo no encontrado.');redirect('hallazgos.php',false);}
$type=(string)($_POST['tipo']??'');$description=trim((string)($_POST['descripcion']??''));$responsible=(int)($_POST['responsable_id']??0);$due=trim((string)($_POST['fecha_compromiso']??''));$corrective=(int)($_POST['mantenimiento_correctivo_id']??0);
if(!in_array($type,['ACCION_INMEDIATA','CORRECCION','ACCION_CORRECTIVA'],true)||$description===''){$session->msg('d','Complete el tipo y descripcion de la accion.');redirect('hallazgo.php?id='.$findingId,false);}
if($responsible>0&&!$db->fetch_assoc($db->query("SELECT id FROM users WHERE id={$responsible} AND status=1 LIMIT 1"))){$session->msg('d','Responsable no valido.');redirect('hallazgo.php?id='.$findingId,false);}
if($corrective>0){$ot=$db->fetch_assoc($db->query("SELECT id,IdMaquina FROM activos_mantenimientos WHERE id={$corrective} LIMIT 1"));if(!$ot||(int)$ot['IdMaquina']!==(int)$finding['IdMaquina']||$corrective===(int)$finding['mantenimiento_id']){$session->msg('d','La OT correctiva debe ser otra orden del mismo equipo.');redirect('hallazgo.php?id='.$findingId,false);}}
$user=current_user();$userId=(int)($user['id']??0);$responsibleSql=$responsible?:'NULL';$dueSql=$due===''?'NULL':mti_chk_q($due.' 23:59:59');$correctiveSql=$corrective?:'NULL';
$db->query("INSERT INTO activos_mantenimiento_hallazgo_acciones (hallazgo_id,tipo,descripcion,responsable_id,fecha_compromiso,estado,mantenimiento_correctivo_id,creado_por) VALUES ({$findingId},".mti_chk_q($type).','.mti_chk_q($description).",{$responsibleSql},{$dueSql},'PENDIENTE',{$correctiveSql},{$userId})");
$actionId=(int)$db->insert_id();mti_chk_hallazgo_history($findingId,'ACCION_CREADA',$finding['estado'],'EN_ATENCION',$description,$userId,$actionId);
if($finding['estado']==='ABIERTO')$db->query("UPDATE activos_mantenimiento_hallazgos SET estado='EN_ATENCION' WHERE id={$findingId}");
$session->msg('s','Accion registrada.');redirect('hallazgo.php?id='.$findingId,false);
