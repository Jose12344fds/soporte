<?php
$page_title='Expediente finalizado — FH ING';
require_once dirname(dirname(__DIR__)).'/includes/load.php';
require_permiso('ots_ingeniera','ver');
require_once dirname(dirname(__DIR__)).'/api/fh_ing/modulo2_helpers.php';
$ot=trim((string)($_GET['ot']??''));$oe=$db->escape($ot);
$cab=find_by_sql("SELECT * FROM fh_ing_planos_cab WHERE ot='$oe' AND estado=3 LIMIT 1");
if(!$cab){http_response_code(404);exit('Proyecto finalizado no encontrado.');}
$rows=find_by_sql("SELECT p.id_planos,p.cod_plano,p.desc_plano,p.arch_original AS original_m1,p.arch_plano AS archivo_m1,m.desc_mod3,m.arch_original AS original_m2,m.arch_plano AS archivo_m2,COALESCE(m.dias,0)/8 AS dias FROM pln_dtll_Ing p LEFT JOIN pln_mod3_Ing m ON m.id_planos=p.id_planos AND m.id_mod3=(SELECT MAX(v.id_mod3) FROM pln_mod3_Ing v WHERE v.id_planos=m.id_planos) WHERE p.ot='$oe' ORDER BY p.orden,p.id_planos");
$rows=fh_modulo2_fechas($rows,fh_modulo2_inicio($ot));
require_once dirname(dirname(__DIR__)).'/api/fh_ing/obs_modulo.php';fh_obs_modulo_schema();
$notas=find_by_sql("SELECT o.*,p.cod_plano AS codigo FROM fh_ing_plano_obs o INNER JOIN pln_dtll_Ing p ON p.id_planos=o.id_planos WHERE p.ot='$oe' ORDER BY o.modulo,o.created_at");
function ex($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
include_once dirname(dirname(__DIR__)).'/layouts/header.php';
?>
<link rel="stylesheet" href="../../assets/css/fh_revision.css?v=20261027"><script src="../../assets/js/fh_revision.js?v=20261027"></script>
<div style="padding:24px;"><div class="card"><div class="card-header bg-success"><h4>Expediente finalizado · <?= ex($ot) ?></h4><span>Documentos y observaciones conservados · Sólo consulta</span></div><div class="card-body">
<a href="planos.php" class="btn btn-outline-secondary">Volver a Ingeniería / finalizados</a>
<a href="../../api/fh_ing/exportar_gantt.php?ot=<?= rawurlencode($ot) ?>" class="btn btn-success">Reporte completo de los dos módulos</a>
<div class="table-responsive mt-3"><table class="table table-bordered"><thead><tr><th>Plano</th><th>Módulo 1 · Ingeniería</th><th>Módulo 2 · Detalle</th><th>Días / horas</th><th>Inicio</th><th>Fin</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= ex($r['cod_plano']) ?></td><td><?= ex($r['desc_plano']) ?><br><?php if($r['archivo_m1']): ?><a href="../../api/fh_ing/descargar_plano_dwg.php?id=<?= (int)$r['id_planos'] ?>">Descargar <?= ex($r['original_m1']) ?></a><?php endif; ?></td><td><?= ex($r['desc_mod3']) ?><br><?php if($r['archivo_m2']): ?><a href="../../api/fh_ing/descargar_mod3.php?file=<?= rawurlencode($r['archivo_m2']) ?>">Descargar <?= ex($r['original_m2']) ?></a><?php endif; ?></td><td><?= ex($r['dias']) ?> d / <?= ex($r['horas']) ?> h</td><td><?= ex($r['fecha_inicio']) ?></td><td><?= ex($r['fecha_fin']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><h5>Observaciones por módulo</h5>
<?php foreach($notas as $nota): ?><div class="border rounded p-3 mb-2"><strong>Módulo <?= (int)$nota['modulo'] ?> · <?= ex($nota['codigo']) ?></strong><div class="text-muted"><?= ex($nota['usuario'].' · '.$nota['created_at']) ?></div><p style="white-space:pre-wrap;"><?= ex($nota['texto']) ?></p><?php $nid=(int)$nota['id'];$imgs=find_by_sql("SELECT id,nombre_original FROM fh_ing_obs_imagenes WHERE id_obs=$nid");foreach($imgs as $im): ?><button type="button" class="btn btn-light expediente-imagen" data-id="<?= (int)$im['id'] ?>"><?= ex($im['nombre_original']) ?></button><?php endforeach; ?></div><?php endforeach; ?>
</div></div></div>
<script>document.querySelectorAll('.expediente-imagen').forEach(function(b){b.addEventListener('click',function(){FHReview.openGallery([{url:'../../api/fh_ing/ver_obs_imagen.php?id='+b.dataset.id,title:b.textContent}],0);});});</script>
<?php include_once dirname(dirname(__DIR__)).'/layouts/footer.php'; ?>