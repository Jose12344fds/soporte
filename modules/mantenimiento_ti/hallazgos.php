<?php
$page_title='Hallazgos de mantenimiento TI';
require_once(__DIR__.'/../../includes/load.php');
require_once(__DIR__.'/../../config/app.php');
require_once(__DIR__.'/funciones_checklist.php');
ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti','ver');
$ready=mti_chk_schema_ready();$rows=[];
if($ready)$rows=find_by_sql(
 "SELECT h.*,m.folio,q.Host,u.name responsable_nombre,COUNT(a.id) total_acciones,"
 ." SUM(CASE WHEN a.estado IN ('PENDIENTE','EN_PROCESO') THEN 1 ELSE 0 END) pendientes,"
 ." MIN(CASE WHEN a.estado IN ('PENDIENTE','EN_PROCESO') THEN a.fecha_compromiso END) proximo_vencimiento"
 ." FROM activos_mantenimiento_hallazgos h INNER JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id"
 ." INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina"
 ." LEFT JOIN activos_mantenimiento_hallazgo_acciones a ON a.hallazgo_id=h.id"
 ." LEFT JOIN users u ON u.id=a.responsable_id GROUP BY h.id ORDER BY h.id DESC");
$stats=['ABIERTOS'=>0,'VENCIDOS'=>0,'VERIFICACION'=>0,'CERRADOS'=>0];
foreach($rows as $r){if(in_array($r['estado'],['ABIERTO','EN_ATENCION'],true))$stats['ABIERTOS']++;if($r['estado']==='PENDIENTE_VERIFICACION')$stats['VERIFICACION']++;if($r['estado']==='CERRADO')$stats['CERRADOS']++;if(!empty($r['proximo_vencimiento'])&&strtotime($r['proximo_vencimiento'])<time()&&(int)$r['pendientes']>0)$stats['VENCIDOS']++;}
function mth_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
include_once(__DIR__.'/../../layouts/header.php');?>
<style>.hf-hero{border:0;border-radius:14px;color:#fff;background:linear-gradient(120deg,#392f48,#9b3f52)}.hf-kpi{border:0;border-radius:11px}.hf-num{font-size:28px;font-weight:900}.hf-critical{border-left:4px solid #dc3545}</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between"><h1 class="h4"><i class="fas fa-triangle-exclamation mr-2 text-danger"></i>Hallazgos TI</h1><div><a href="checklist_plantillas.php" class="btn btn-sm btn-outline-info">Plantillas</a> <a href="index.php" class="btn btn-sm btn-outline-secondary">Volver</a></div></div></section>
<section class="content"><div class="container-fluid"><?=display_msg($msg)?>
<?php if(!$ready):?><div class="alert alert-warning">Falta ejecutar <code>01_checklists_hallazgos.sql</code>.</div><?php else:?>
<div class="card hf-hero mb-3"><div class="card-body"><div class="small text-uppercase font-weight-bold">Seguimiento y control</div><h2 class="h4 font-weight-bold">Hallazgos, acciones y verificacion</h2></div></div>
<div class="row mb-3"><?php foreach($stats as $label=>$value):?><div class="col-6 col-lg-3"><div class="card hf-kpi shadow-sm"><div class="card-body"><div class="small text-muted"><?=$label?></div><div class="hf-num"><?=$value?></div></div></div></div><?php endforeach;?></div>
<div class="card"><div class="card-header bg-white"><div class="row"><div class="col-md-5"><input id="hfBuscar" class="form-control form-control-sm" placeholder="Buscar codigo, OT, equipo o titulo"></div><div class="col-md-3"><select id="hfEstado" class="form-control form-control-sm"><option value="">Todos los estados</option><option>ABIERTO</option><option>EN ATENCION</option><option>PENDIENTE VERIFICACION</option><option>CERRADO</option><option>ANULADO</option></select></div><div class="col-md-3"><select id="hfCriticidad" class="form-control form-control-sm"><option value="">Toda criticidad</option><option>BAJA</option><option>MEDIA</option><option>ALTA</option><option>CRITICA</option></select></div></div></div>
<div class="table-responsive"><table class="table table-hover mb-0" id="tblHallazgos"><thead><tr><th>Hallazgo</th><th>OT / equipo</th><th>Criticidad</th><th>Estado</th><th>Acciones</th><th>Vencimiento</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):$overdue=!empty($r['proximo_vencimiento'])&&strtotime($r['proximo_vencimiento'])<time()&&(int)$r['pendientes']>0;?><tr class="<?=in_array($r['criticidad'],['ALTA','CRITICA'],true)?'hf-critical':''?>"><td><strong><?=mth_h($r['codigo'])?></strong><div><?=mth_h($r['titulo'])?></div></td><td><a href="detalle.php?id=<?=(int)$r['mantenimiento_id']?>"><?=mth_h($r['folio'])?></a><div class="small text-muted"><?=mth_h($r['Host'])?></div></td><td><span class="badge badge-<?=in_array($r['criticidad'],['ALTA','CRITICA'],true)?'danger':'warning'?>"><?=mth_h($r['criticidad'])?></span></td><td><?=mth_h(str_replace('_',' ',$r['estado']))?></td><td><?=(int)$r['pendientes']?> / <?=(int)$r['total_acciones']?> pendientes</td><td class="<?=$overdue?'text-danger font-weight-bold':''?>"><?=!empty($r['proximo_vencimiento'])?date('d/m/Y',strtotime($r['proximo_vencimiento'])):'-'?></td><td><a class="btn btn-sm btn-info" href="hallazgo.php?id=<?=(int)$r['id']?>"><i class="fas fa-eye"></i></a></td></tr><?php endforeach;?>
</tbody></table></div></div><?php endif;?></div></section>
<?php $inlineJs=<<<'JS'
$(function(){var t=$('#tblHallazgos').DataTable({pageLength:20,order:[[0,'desc']],language:DATATABLES_SPANISH,dom:'rt<"row mt-3"<"col-md-5"i><"col-md-7"p>>'});$('#hfBuscar').on('input',function(){t.search(this.value).draw()});$('#hfEstado').on('change',function(){t.column(3).search(this.value).draw()});$('#hfCriticidad').on('change',function(){t.column(2).search(this.value).draw()});});
JS;include_once(__DIR__.'/../../layouts/footer.php');?>
