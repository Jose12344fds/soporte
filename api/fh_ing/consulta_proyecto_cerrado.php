<?php
/** Consulta de expediente cerrado. Sólo GET; no modifica datos del proyecto. */
ob_start();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{
 require_once dirname(dirname(__DIR__)).'/includes/load.php';
 require_once __DIR__.'/modulo2_helpers.php';
 if($_SERVER['REQUEST_METHOD']!=='GET')throw new RuntimeException('Método no permitido.');
 if(!current_user()||(!tiene_permiso('hh-meta','ver')&&!tiene_permiso('ots_ingeniera','ver'))){http_response_code(403);throw new RuntimeException('No autorizado para consultar el expediente.');}
 $ot=trim((string)($_GET['ot']??''));if($ot==='')throw new RuntimeException('OT requerida.');$oe=$db->escape($ot);
 $cab=find_by_sql("SELECT * FROM fh_ing_planos_cab WHERE ot='$oe' AND estado=3 ORDER BY id DESC LIMIT 1");
 if(!$cab){http_response_code(404);throw new RuntimeException('Esta OT no tiene un proyecto de Ingeniería cerrado.');}
 $det=find_by_sql("SELECT * FROM fh_ing_ot_detalle WHERE ot='$oe' ORDER BY id DESC LIMIT 1");
 $planos=find_by_sql("SELECT p.*,a.nombre AS proceso,a.orden AS orden_proceso FROM pln_dtll_Ing p INNER JOIN fh_ing_actividades a ON a.id=p.actividad_id WHERE p.ot='$oe' ORDER BY a.orden,a.id,p.orden,p.id_planos");
 $grupos=[];
 foreach($planos as $p){$aid=(int)$p['actividad_id'];if(!isset($grupos[$aid]))$grupos[$aid]=['actividad_id'=>$aid,'nombre'=>$p['proceso'],'planos'=>[]];
 $grupos[$aid]['planos'][]=['id_planos'=>(int)$p['id_planos'],'cod_plano'=>$p['cod_plano'],'desc_plano'=>$p['desc_plano'],'dias'=>(float)$p['dias'],'archivo'=>$p['arch_plano'],'nombre_archivo'=>$p['arch_original']?:$p['arch_plano'],'modulo'=>1];}
 $inicio=$cab[0]['fecha_inicio']?:($det[0]['fecha_inicio']??substr($det[0]['created_at']??date('Y-m-d'),0,10));
 $procesos=fh_schedule_activities(array_values($grupos),$inicio);
 $detalle=find_by_sql("SELECT m.*,p.cod_plano FROM pln_mod3_Ing m INNER JOIN pln_dtll_Ing p ON p.id_planos=m.id_planos INNER JOIN fh_ing_actividades a ON a.id=p.actividad_id WHERE m.ot='$oe' AND m.id_mod3=(SELECT MAX(v.id_mod3) FROM pln_mod3_Ing v WHERE v.id_planos=m.id_planos) ORDER BY a.orden,a.id,p.orden,p.id_planos");
 $filas=[];foreach($detalle as $p)$filas[]=['id_planos'=>(int)$p['id_planos'],'cod_plano'=>$p['cod_plano'],'desc_plano'=>$p['desc_mod3'],'dias'=>(float)$p['dias']/8,'archivo'=>$p['arch_plano'],'nombre_archivo'=>$p['arch_original']?:$p['arch_plano'],'modulo'=>2];
 $filas=fh_modulo2_fechas($filas,fh_modulo2_inicio($ot));
 $antecedentes=[];$t=$db->query("SHOW TABLES LIKE 'fh_ing_ant_archivos'");if($db->num_rows($t))$antecedentes=find_by_sql("SELECT id,actividad_id,nombre_original,nombre_guardado,tipo_mime,descripcion FROM fh_ing_ant_archivos WHERE ot='$oe' ORDER BY actividad_id,created_at,id");
 $column=$db->query("SHOW COLUMNS FROM fh_ing_plano_obs LIKE 'modulo'");$mod=$db->num_rows($column)?'o.modulo':'1';
 $notas=find_by_sql("SELECT o.id,o.texto,o.usuario,o.created_at,$mod AS modulo,p.cod_plano FROM fh_ing_plano_obs o INNER JOIN pln_dtll_Ing p ON p.id_planos=o.id_planos WHERE p.ot='$oe' ORDER BY modulo,o.created_at,o.id");
 foreach($notas as &$nota){$nid=(int)$nota['id'];$nota['imagenes']=find_by_sql("SELECT id,nombre_original AS nombre FROM fh_ing_obs_imagenes WHERE id_obs=$nid ORDER BY id");}unset($nota);
 $total1=array_sum(array_column($procesos,'total_dias'));$total2=array_sum(array_column($filas,'dias'));
 ob_end_clean();echo json_encode(['success'=>true,'solo_lectura'=>true,'ot'=>$cab[0]['ot'],'proyecto'=>$det[0]['proyecto']??$cab[0]['proyecto'],'elaborado_por'=>$det[0]['elaborado_por']??'','fecha_cierre'=>$cab[0]['fecha_cierre']??$cab[0]['updated_at'],'modulo1'=>$procesos,'modulo2'=>$filas,'antecedentes'=>$antecedentes,'observaciones'=>$notas,'total_dias'=>$total1+$total2,'total_horas'=>($total1+$total2)*8],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
}catch(Throwable $e){if(http_response_code()<400)http_response_code(400);if(ob_get_level())ob_end_clean();echo json_encode(['success'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}