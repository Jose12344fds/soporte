<?php
$page_title='Programacion de Mantenimiento TI';
require_once(__DIR__.'/../../includes/load.php');
require_once(__DIR__.'/../../config/app.php');
require_permiso('mantenimiento_ti','ver');
function mto_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$planId=(int)($_GET['plan_id']??0);
$where=$planId>0?' WHERE p.id='.$planId:'';
$rows=find_by_sql(
 "SELECT o.*,p.codigo AS plan_codigo,p.nombre AS plan_nombre,p.tipo,p.prioridad,p.instruccion,p.IdMaquina,"
 ." m.Host,m.Tipo_Activo,ot.folio AS ot_folio,ot.estado AS ot_estado"
 ." FROM activos_mantenimiento_ocurrencias o"
 ." INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
 ." INNER JOIN maquina m ON m.IdMaquina=p.IdMaquina"
 ." LEFT JOIN activos_mantenimientos ot ON ot.id=o.mantenimiento_id{$where}"
 ." ORDER BY o.fecha_programada ASC,o.id ASC"
);
$today=date('Y-m-d');$stats=['TOTAL'=>0,'PENDIENTE'=>0,'VENCIDA'=>0,'CON_OT'=>0,'CANCELADA'=>0];
foreach($rows as &$r){$display=$r['estado'];if($display==='PENDIENTE'&&!$r['mantenimiento_id']&&$r['fecha_programada']<$today)$display='VENCIDA';$r['estado_visual']=$display;$stats['TOTAL']++;if(isset($stats[$display]))$stats[$display]++;if($r['mantenimiento_id'])$stats['CON_OT']++;}unset($r);
include_once(__DIR__.'/../../layouts/header.php');
?>
<style>
:root{--mto-ink:#153b46;--mto-teal:#16818b;--mto-soft:#f2f8f7}.mto-hero{border:0;border-radius:14px;color:#fff;background:linear-gradient(120deg,var(--mto-ink),var(--mto-teal));box-shadow:0 12px 28px rgba(20,60,70,.16)}
.mto-stat{background:#fff;border:1px solid #dbe7e7;border-radius:10px;padding:12px}.mto-stat strong{display:block;font-size:24px;color:var(--mto-ink)}.mto-stat span{font-size:10px;font-weight:800;color:#71848a;text-transform:uppercase}
.mto-table thead th{background:var(--mto-ink);color:#fff;border:0;font-size:10px;text-transform:uppercase}.mto-table td{vertical-align:middle}.mto-filter{background:var(--mto-soft);border:1px solid #dce8e7;border-radius:10px;padding:12px}.mto-status{display:inline-block;padding:4px 9px;border-radius:15px;font-size:10px;font-weight:800}.mto-status.PENDIENTE{background:#fff1c7;color:#8a6400}.mto-status.VENCIDA{background:#f9d8dc;color:#a52332}.mto-status.OT_GENERADA{background:#d9ecff;color:#205e99}.mto-status.EJECUTADA{background:#dff3e8;color:#237149}.mto-status.CANCELADA{background:#e8ecee;color:#617078}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0"><i class="fas fa-calendar-alt mr-2 text-info"></i>Programacion TI</h1><div><a href="planes.php" class="btn btn-sm btn-outline-info mr-1"><i class="fas fa-layer-group mr-1"></i>Planes</a><a href="index.php" class="btn btn-sm btn-outline-secondary">Ordenes TI</a></div></div></section>
<section class="content"><div class="container-fluid"><?=display_msg($msg)?>
<div class="card mto-hero mb-3"><div class="card-body"><div class="small text-uppercase font-weight-bold">Ocurrencias de mantenimiento</div><h2 class="h4 font-weight-bold mb-1"><?= $planId?'Calendario del plan seleccionado':'Calendario general de equipos TI' ?></h2><div class="small">Cada fecha puede generar una sola orden de trabajo.</div></div></div>
<div class="row mb-3"><?php foreach(['TOTAL'=>'Total','PENDIENTE'=>'Pendientes','VENCIDA'=>'Vencidas','CON_OT'=>'Con OT','CANCELADA'=>'Canceladas'] as $k=>$label):?><div class="col-6 col-lg mb-2"><div class="mto-stat"><strong><?=$stats[$k]?></strong><span><?=$label?></span></div></div><?php endforeach;?></div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="mto-filter row mb-3"><div class="col-md-5"><label class="small font-weight-bold">Buscar</label><input id="mtoBuscar" class="form-control form-control-sm" placeholder="Equipo, plan, codigo..."></div><div class="col-md-3"><label class="small font-weight-bold">Estado</label><select id="mtoEstado" class="form-control form-control-sm"><option value="">Todos</option><option>PENDIENTE</option><option>VENCIDA</option><option>OT GENERADA</option><option>EJECUTADA</option><option>CANCELADA</option></select></div><div class="col-md-4 d-flex align-items-end"><a href="ocurrencias.php" class="btn btn-sm btn-outline-secondary">Limpiar seleccion de plan</a></div></div>
<div class="table-responsive"><table id="tblOcurrenciasTI" class="table table-bordered table-hover mto-table w-100"><thead><tr><th>Ocurrencia</th><th>Plan</th><th>Equipo</th><th>Programada</th><th>Tipo</th><th>Prioridad</th><th>Estado</th><th>Orden</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><strong><?=mto_h($r['codigo'])?></strong></td><td><span class="text-info font-weight-bold"><?=mto_h($r['plan_codigo'])?></span><div class="small"><?=mto_h($r['plan_nombre'])?></div></td><td><strong><?=mto_h($r['Host'])?></strong><div class="small text-muted"><?=mto_h($r['Tipo_Activo'])?></div></td><td data-order="<?=mto_h($r['fecha_programada'])?>"><?=date('d/m/Y',strtotime($r['fecha_programada']))?><?= $r['fecha_original']!==$r['fecha_programada']?'<div class="small text-warning">Reprogramada</div>':''?></td><td><?=mto_h($r['tipo'])?></td><td><?=mto_h($r['prioridad'])?></td><td><span class="mto-status <?=mto_h($r['estado_visual'])?>"><?=mto_h(str_replace('_',' ',$r['estado_visual']))?></span></td><td><?= $r['mantenimiento_id']?'<a href="detalle.php?id='.(int)$r['mantenimiento_id'].'" class="font-weight-bold">'.mto_h($r['ot_folio']).'</a>':'-'?></td><td class="text-center text-nowrap">
<?php if($r['mantenimiento_id']):?><a href="detalle.php?id=<?=(int)$r['mantenimiento_id']?>" class="btn btn-sm btn-info" title="Ver orden"><i class="fas fa-eye"></i></a>
<?php elseif(in_array($r['estado_visual'],['PENDIENTE','VENCIDA'],true)&&tiene_permiso('mantenimiento_ti','crear')):?><form method="post" action="generar_orden_ocurrencia.php" class="d-inline formGenerarOT"><?=csrf_input()?><input type="hidden" name="ocurrencia_id" value="<?=(int)$r['id']?>"><button class="btn btn-sm btn-success" title="Generar orden"><i class="fas fa-plus-circle mr-1"></i>OT</button></form><?php else:?><span class="text-muted">-</span><?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div></div></div>
</div></section>
<?php
$inlineJs=<<<'JS'
$(function(){var t=$('#tblOcurrenciasTI').DataTable({pageLength:20,order:[[3,'asc']],language:DATATABLES_SPANISH,dom:'rt<"row mt-3"<"col-md-5"i><"col-md-7"p>>'});$('#mtoBuscar').on('input',function(){t.search(this.value).draw()});$('#mtoEstado').on('change',function(){t.column(6).search(this.value).draw()});$('.formGenerarOT').on('submit',function(e){if($(this).data('confirmed'))return;e.preventDefault();var form=this;Swal.fire({title:'Generar orden de trabajo?',text:'La ocurrencia quedara vinculada y no podra generar otra OT.',icon:'question',showCancelButton:true,confirmButtonText:'Si, generar OT',cancelButtonText:'Cancelar'}).then(function(r){if(r.isConfirmed){$(form).data('confirmed',true);form.submit()}})});});
JS;
include_once(__DIR__.'/../../layouts/footer.php');
?>
