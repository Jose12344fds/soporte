<?php
$page_title = 'Planes de Mantenimiento TI';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_plan.php');
require_permiso('mantenimiento_ti', 'ver');

function mtp_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$equipos = find_by_sql("SELECT IdMaquina,Host,Tipo_Activo,EstadoMaquina FROM maquina ORDER BY Host");
$planes = find_by_sql(
    "SELECT p.*,m.Host,m.Tipo_Activo,m.EstadoMaquina,"
    . " COUNT(o.id) AS ocurrencias,"
    . " SUM(CASE WHEN o.mantenimiento_id IS NULL AND o.estado='PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,"
    . " SUM(CASE WHEN o.mantenimiento_id IS NOT NULL THEN 1 ELSE 0 END) AS con_orden"
    . " FROM activos_mantenimiento_planes p"
    . " INNER JOIN maquina m ON m.IdMaquina=p.IdMaquina"
    . " LEFT JOIN activos_mantenimiento_ocurrencias o ON o.plan_id=p.id"
    . " GROUP BY p.id ORDER BY p.activo DESC,p.id DESC"
);
include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
:root{--mtp-ink:#123746;--mtp-teal:#137c8b;--mtp-gold:#e5a12c;--mtp-soft:#f2f8f7}
.mtp-hero{border:0;border-radius:14px;color:#fff;background:linear-gradient(120deg,var(--mtp-ink),var(--mtp-teal));box-shadow:0 12px 28px rgba(18,55,70,.16)}
.mtp-card{border:0;border-radius:12px;box-shadow:0 6px 18px rgba(18,55,70,.08)}
.mtp-table thead th{background:var(--mtp-ink);color:#fff;border:0;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
.mtp-table td{vertical-align:middle}.mtp-code{font-weight:800;color:var(--mtp-teal)}
.mtp-table tbody tr[data-plan-row]{cursor:pointer}.mtp-table tbody tr[data-plan-row]:hover{background:#edf8f7}
.mtp-count{display:inline-flex;min-width:28px;height:28px;align-items:center;justify-content:center;border-radius:50%;font-weight:800;background:#e8f3f3;color:#176d78}
.mtp-plan-link{padding:0;border:0;background:none;text-align:left;color:inherit}.mtp-plan-link:hover .mtp-code,.mtp-plan-link:focus .mtp-code{text-decoration:underline;color:#0b5964}
.mtp-occ-card{display:none;border:0;border-radius:12px;box-shadow:0 8px 24px rgba(18,55,70,.12);overflow:hidden}.mtp-occ-card .card-header{background:linear-gradient(120deg,var(--mtp-ink),var(--mtp-teal));color:#fff}
.mtp-occ-table thead th{background:#e9f2f2;color:#254752;border-color:#d6e3e3;font-size:10px;text-transform:uppercase}.mtp-occ-table td{vertical-align:middle}
.mtp-occ-status{display:inline-block;padding:4px 9px;border-radius:14px;font-size:10px;font-weight:800}.mtp-occ-status.PENDIENTE{background:#fff1c7;color:#8a6400}.mtp-occ-status.VENCIDA{background:#f9d8dc;color:#a52332}.mtp-occ-status.OT_GENERADA{background:#d9ecff;color:#205e99}.mtp-occ-status.EJECUTADA{background:#dff3e8;color:#237149}.mtp-occ-status.CANCELADA{background:#e8ecee;color:#617078}
#modalPlanTI .modal-content{border:0;border-radius:13px;overflow:hidden}#modalPlanTI .modal-header{color:#fff;background:linear-gradient(120deg,var(--mtp-ink),var(--mtp-teal))}
#modalPlanTI label{font-size:10px;text-transform:uppercase;color:#506b74;font-weight:800}#modalPlanTI .form-section{padding:14px;border:1px solid #dbe7e7;border-radius:10px;background:#fff}
.mtp-date-box{height:100%;padding:10px;border:1px solid #d8e5e5;border-radius:9px;background:#f8fbfb}.mtp-date-box small{display:block;line-height:1.25;margin-top:6px;color:#71858c}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center">
 <h1 class="h4 mb-0"><i class="fas fa-calendar-check mr-2 text-info"></i>Planes de Mantenimiento TI</h1>
 <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Ordenes TI</a>
</div></section>
<section class="content"><div class="container-fluid">
 <?= display_msg($msg) ?>
 <div class="card mtp-hero mb-3"><div class="card-body d-md-flex justify-content-between align-items-center">
  <div><div class="small text-uppercase font-weight-bold opacity-75">Plan maestro y recurrencia</div><h2 class="h4 font-weight-bold mb-1">Mantenimiento preventivo de equipos TI</h2><div class="small">Define una sola vez el trabajo y genera todas sus fechas.</div></div>
  <div class="mt-3 mt-md-0">
   <button type="button" id="btnVerTodasOcurrencias" class="btn btn-light mr-2"><i class="fas fa-calendar-alt mr-1 text-info"></i>Ver programacion</button>
   <?php if(tiene_permiso('mantenimiento_ti','crear')): ?><button id="btnNuevoPlan" class="btn btn-warning font-weight-bold"><i class="fas fa-plus mr-1"></i>Nuevo plan</button><?php endif; ?>
  </div>
 </div></div>
 <div class="card mtp-card"><div class="card-body"><div class="table-responsive">
  <table id="tblPlanesTI" class="table table-hover table-bordered mtp-table w-100"><thead><tr><th>Plan</th><th>Equipo</th><th>Frecuencia</th><th>Vigencia / primera fecha</th><th>Ocurrencias</th><th>Pendientes</th><th>OT</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
  <?php foreach($planes as $p): $record=[
   'id'=>(int)$p['id'],'IdMaquina'=>(int)$p['IdMaquina'],'codigo'=>$p['codigo'],'nombre'=>$p['nombre'],'tipo'=>$p['tipo'],'prioridad'=>$p['prioridad'],
   'frecuencia'=>$p['frecuencia'],'intervalo'=>(int)$p['intervalo'],'unidad_intervalo'=>$p['unidad_intervalo'],'fecha_inicio'=>$p['fecha_inicio'],'primera_fecha_programada'=>$p['primera_fecha_programada'],'fecha_fin'=>$p['fecha_fin'],
   'instruccion'=>$p['instruccion'],'generar_automatico'=>(int)$p['generar_automatico'],'activo'=>(int)$p['activo'],'ocurrencias'=>(int)$p['ocurrencias']
  ]; ?>
   <tr data-plan-row="<?=(int)$p['id']?>" data-plan-code="<?=mtp_h($p['codigo'])?>" data-plan-name="<?=mtp_h($p['nombre'])?>"><td><button type="button" class="mtp-plan-link btnVerOcurrencias" data-plan="<?=(int)$p['id']?>" data-code="<?=mtp_h($p['codigo'])?>" data-name="<?=mtp_h($p['nombre'])?>"><span class="mtp-code"><?=mtp_h($p['codigo'])?></span><div class="small font-weight-bold"><?=mtp_h($p['nombre'])?></div></button></td>
   <td><strong><?=mtp_h($p['Host'])?></strong><div class="small text-muted"><?=mtp_h($p['Tipo_Activo'])?></div></td>
   <td><strong><?=mtp_h(ucfirst(strtolower($p['frecuencia'])))?></strong></td>
   <td><div><span class="small text-muted">Vigencia:</span> <?=date('d/m/Y',strtotime($p['fecha_inicio']))?> - <?= $p['fecha_fin']?date('d/m/Y',strtotime($p['fecha_fin'])):'Sin fecha final'?></div><div class="small font-weight-bold text-info"><i class="fas fa-calendar-day mr-1"></i>Primera: <?=date('d/m/Y',strtotime($p['primera_fecha_programada'] ?: $p['fecha_inicio']))?></div></td>
   <td class="text-center"><span class="mtp-count"><?=(int)$p['ocurrencias']?></span></td><td class="text-center"><?=(int)$p['pendientes']?></td><td class="text-center"><?=(int)$p['con_orden']?></td>
   <td><span class="badge badge-<?= $p['activo']?'success':'secondary' ?>"><?= $p['activo']?'ACTIVO':'INACTIVO' ?></span></td>
   <td class="text-nowrap text-center">
    <button type="button" class="btn btn-sm btn-info btnVerOcurrencias" data-plan="<?=(int)$p['id']?>" data-code="<?=mtp_h($p['codigo'])?>" data-name="<?=mtp_h($p['nombre'])?>" title="Ver ocurrencias debajo"><i class="fas fa-calendar-alt"></i></button>
    <?php if(tiene_permiso('mantenimiento_ti','editar')): ?>
    <button type="button" class="btn btn-sm btn-warning btnEditarPlan" data-record="<?=mtp_h(json_encode($record,JSON_UNESCAPED_UNICODE))?>" title="Editar"><i class="fas fa-edit"></i></button>
    <?php if($p['activo']): ?><form method="post" action="generar_ocurrencias.php" class="d-inline"><?=csrf_input()?><input type="hidden" name="plan_id" value="<?=(int)$p['id']?>"><button class="btn btn-sm btn-outline-success" title="Generar siguientes 12 meses"><i class="fas fa-calendar-plus"></i></button></form><?php endif; ?>
    <?php endif; ?>
   </td></tr>
  <?php endforeach; ?>
  </tbody></table>
 </div></div></div>
 <div class="card mtp-occ-card mt-3" id="panelOcurrenciasTI">
  <div class="card-header d-flex justify-content-between align-items-center"><div><div class="small text-uppercase">Calendario del mantenimiento</div><h3 class="h5 font-weight-bold mb-0" id="occPlanTitle">Ocurrencias</h3></div><button type="button" class="btn btn-sm btn-outline-light" id="btnCerrarOcurrencias"><i class="fas fa-times mr-1"></i>Cerrar</button></div>
  <div class="card-body"><div class="table-responsive"><table id="tblOcurrenciasPlan" class="table table-bordered table-hover mtp-occ-table w-100"><thead><tr><th>Ocurrencia</th><th>Fecha original</th><th>Fecha programada</th><th>Estado</th><th>Orden</th><th>Accion</th><th>Plan ID</th></tr></thead><tbody>
  <?php
  $occRows=find_by_sql("SELECT o.*,ot.folio AS ot_folio FROM activos_mantenimiento_ocurrencias o LEFT JOIN activos_mantenimientos ot ON ot.id=o.mantenimiento_id ORDER BY o.fecha_programada,o.id");
  $today=date('Y-m-d');
  foreach($occRows as $o):
   $visual=($o['estado']==='PENDIENTE' && empty($o['mantenimiento_id']) && $o['fecha_programada']<$today)?'VENCIDA':$o['estado'];
  ?><tr><td><strong><?=mtp_h($o['codigo'])?></strong></td><td data-order="<?=mtp_h($o['fecha_original'])?>"><?=date('d/m/Y',strtotime($o['fecha_original']))?></td><td data-order="<?=mtp_h($o['fecha_programada'])?>"><?=date('d/m/Y',strtotime($o['fecha_programada']))?><?= $o['fecha_original']!==$o['fecha_programada']?'<div class="small text-warning">Reprogramada</div>':''?></td><td><span class="mtp-occ-status <?=mtp_h($visual)?>"><?=mtp_h(str_replace('_',' ',$visual))?></span></td><td><?=!empty($o['mantenimiento_id'])?'<strong>'.mtp_h($o['ot_folio']).'</strong>':'-'?></td><td class="text-center text-nowrap"><?php if(!empty($o['mantenimiento_id'])):?><a href="detalle.php?id=<?=(int)$o['mantenimiento_id']?>" class="btn btn-sm btn-info" title="Ver orden"><i class="fas fa-eye mr-1"></i>Ver OT</a><?php elseif(in_array($visual,['PENDIENTE','VENCIDA'],true)&&tiene_permiso('mantenimiento_ti','crear')):?><form method="post" action="generar_orden_ocurrencia.php" class="d-inline formGenerarOT"><?=csrf_input()?><input type="hidden" name="ocurrencia_id" value="<?=(int)$o['id']?>"><button class="btn btn-sm btn-success"><i class="fas fa-plus-circle mr-1"></i>Generar OT</button></form><?php else:?><span class="text-muted">Sin accion</span><?php endif;?><?php if(empty($o['mantenimiento_id'])&&$o['estado']==='PENDIENTE'&&(int)$o['id']>0&&tiene_permiso('mantenimiento_ti','editar')):?><form method="post" action="eliminar_ocurrencia.php" class="d-inline formEliminarOcurrencia"><?=csrf_input()?><input type="hidden" name="ocurrencia_id" value="<?=(int)$o['id']?>"><button class="btn btn-sm btn-outline-danger" title="Eliminar esta ocurrencia"><i class="fas fa-trash-alt"></i></button></form><?php endif;?></td><td><?=(int)$o['plan_id']?></td></tr><?php endforeach;?>
  </tbody></table></div></div>
 </div>
</div></section>

<div class="modal fade" id="modalPlanTI" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
 <form method="post" action="guardar_plan.php" id="formPlanTI"><?=csrf_input()?><input type="hidden" name="id" id="planId" value="0">
 <div class="modal-header"><div><small class="text-uppercase">Configuracion recurrente</small><h5 class="modal-title font-weight-bold" id="planModalTitle">Nuevo plan TI</h5></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
 <div class="modal-body bg-light"><div class="row">
  <div class="col-lg-5"><div class="form-section h-100">
   <div class="form-group"><label>Equipo *</label><select name="IdMaquina" id="planEquipo" class="form-control" required><option value="">Seleccionar...</option><?php foreach($equipos as $e):?><option value="<?=(int)$e['IdMaquina']?>"><?=mtp_h($e['Host'].' / '.$e['Tipo_Activo'])?></option><?php endforeach;?></select></div>
   <div class="form-group"><label>Nombre del plan *</label><input name="nombre" id="planNombre" class="form-control" maxlength="150" required></div>
   <div class="row"><div class="col-6 form-group"><label>Tipo</label><select name="tipo" id="planTipo" class="form-control"><option>PREVENTIVO</option><option>CORRECTIVO</option><option>DIAGNOSTICO</option><option>MEJORA</option></select></div><div class="col-6 form-group"><label>Prioridad</label><select name="prioridad" id="planPrioridad" class="form-control"><option>BAJA</option><option selected>MEDIA</option><option>ALTA</option><option>CRITICA</option></select></div></div>
   <div class="alert alert-light border mb-0 small"><i class="fas fa-user-cog text-info mr-1"></i>El tecnico responsable se asigna posteriormente en la orden de mantenimiento.</div>
  </div></div>
  <div class="col-lg-7 mt-3 mt-lg-0"><div class="form-section">
   <div class="form-group"><label>Frecuencia *</label><select name="frecuencia" id="planFrecuencia" class="form-control"><option>DIARIO</option><option>SEMANAL</option><option selected>MENSUAL</option><option>TRIMESTRAL</option><option>CUATRIMESTRAL</option><option>SEMESTRAL</option><option>ANUAL</option></select><small class="text-muted">Trimestral: 4 veces al año. Cuatrimestral: 3 veces al año.</small></div>
   <div class="row mb-3">
    <div class="col-md-4 mb-2 mb-md-0"><div class="mtp-date-box"><label>Vigencia desde *</label><input type="date" name="fecha_inicio" id="planInicio" class="form-control" required><small>Desde cuando el plan esta vigente.</small></div></div>
    <div class="col-md-4 mb-2 mb-md-0"><div class="mtp-date-box"><label>Primera fecha programada *</label><input type="date" name="primera_fecha_programada" id="planPrimeraFecha" class="form-control" required><small>Fecha ancla desde la que se calcula la frecuencia.</small></div></div>
    <div class="col-md-4"><div class="mtp-date-box"><label>Vigencia hasta</label><input type="date" name="fecha_fin" id="planFin" class="form-control"><small>Vacio: genera un horizonte de 12 meses.</small></div></div>
   </div>
   <div class="form-group"><label>Trabajo general del plan *</label><textarea name="instruccion" id="planInstruccion" class="form-control" rows="5" maxlength="20000" required></textarea></div>
   <div class="d-flex"><div class="custom-control custom-switch mr-4"><input type="checkbox" class="custom-control-input" name="generar_automatico" id="planAutomatico" checked><label class="custom-control-label" for="planAutomatico">Generacion automatica</label></div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" name="activo" id="planActivo" checked><label class="custom-control-label" for="planActivo">Plan activo</label></div></div>
   <div id="planProtectedHelp" class="alert alert-info py-2 mt-3 mb-0 small" style="display:none;">La frecuencia, el equipo, la vigencia y la primera fecha quedan protegidos porque este plan ya tiene ocurrencias.</div>
  </div></div>
 </div></div>
 <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar y generar</button></div>
 </form>
</div></div></div>
<?php
$inlineJs=<<<'JS'
$(function(){
 var table=$('#tblPlanesTI').DataTable({pageLength:15,order:[[0,'desc']],language:DATATABLES_SPANISH});
 var occTable=$('#tblOcurrenciasPlan').DataTable({pageLength:10,order:[[2,'asc']],columnDefs:[{targets:6,visible:false,searchable:true}],language:DATATABLES_SPANISH});
 function showOccurrences(planId,code,name){
  occTable.column(6).search(planId?('^'+String(planId)+'$'):'',true,false).draw();
  $('#occPlanTitle').text(planId?(code+' - '+name):'Todas las ocurrencias');
  $('#panelOcurrenciasTI').stop(true,true).slideDown(180,function(){occTable.columns.adjust().draw(false)});
  $('#tblPlanesTI tbody tr').removeClass('table-info');
  if(planId)$('[data-plan-row="'+planId+'"]').addClass('table-info');
  $('html,body').animate({scrollTop:$('#panelOcurrenciasTI').offset().top-75},250);
 }
 $(document).on('click','.btnVerOcurrencias',function(){showOccurrences($(this).data('plan'),$(this).data('code'),$(this).data('name'))});
 $(document).on('click','#tblPlanesTI tbody tr[data-plan-row]',function(event){
  if($(event.target).closest('button,a,form,input,select,textarea').length)return;
  var row=$(this);
  showOccurrences(row.attr('data-plan-row'),row.attr('data-plan-code')||'',row.attr('data-plan-name')||'');
 });
 $('#btnVerTodasOcurrencias').on('click',function(){showOccurrences(0,'','')});
 $('#btnCerrarOcurrencias').on('click',function(){$('#panelOcurrenciasTI').slideUp(150);$('#tblPlanesTI tbody tr').removeClass('table-info')});
 function today(){return new Date().toISOString().slice(0,10)}
 function syncDateLimits(){var start=$('#planInicio').val();var finish=$('#planFin').val();$('#planPrimeraFecha').attr('min',start||null).attr('max',finish||null);$('#planFin').attr('min',start||null)}
 $('#planInicio,#planFin').on('change',syncDateLimits);
 $('#btnNuevoPlan').on('click',function(){var current=today();document.getElementById('formPlanTI').reset();$('#planId').val(0);$('#planInicio,#planPrimeraFecha').val(current);$('#planModalTitle').text('Nuevo plan TI');$('#planProtectedHelp').hide();$('#planEquipo,#planFrecuencia,#planInicio,#planPrimeraFecha,#planFin').prop('disabled',false);syncDateLimits();$('#modalPlanTI').modal('show')});
 $(document).on('click','.btnEditarPlan',function(event){
  event.preventDefault();
  event.stopPropagation();
  var r=$(this).data('record');
  if(typeof r==='string'){
   try{r=JSON.parse(r)}catch(error){r=null}
  }
  if(!r||!r.id){Swal.fire('No se pudo editar','No fue posible leer los datos del plan. Recargue la pagina e intente nuevamente.','error');return}
  document.getElementById('formPlanTI').reset();
  $('#planId').val(r.id);$('#planEquipo').val(r.IdMaquina);$('#planNombre').val(r.nombre);$('#planTipo').val(r.tipo);$('#planPrioridad').val(r.prioridad);$('#planFrecuencia').val(r.frecuencia);$('#planInicio').val(r.fecha_inicio);$('#planPrimeraFecha').val(r.primera_fecha_programada||r.fecha_inicio);$('#planFin').val(r.fecha_fin||'');$('#planInstruccion').val(r.instruccion);$('#planAutomatico').prop('checked',!!r.generar_automatico);$('#planActivo').prop('checked',!!r.activo);syncDateLimits();
  var locked=r.ocurrencias>0;$('#planProtectedHelp').toggle(locked);$('#planEquipo,#planFrecuencia,#planInicio,#planPrimeraFecha,#planFin').prop('disabled',locked);$('#planModalTitle').text('Editar '+r.codigo);$('#modalPlanTI').modal('show');
 });
 $('#formPlanTI').on('submit',function(){$('#planEquipo,#planFrecuencia,#planInicio,#planPrimeraFecha,#planFin').prop('disabled',false)});
 $(document).on('submit','.formGenerarOT',function(e){if($(this).data('confirmed'))return;e.preventDefault();var form=this;Swal.fire({title:'Generar orden de trabajo?',text:'Esta ocurrencia quedara vinculada a una sola OT.',icon:'question',showCancelButton:true,confirmButtonText:'Si, generar OT',cancelButtonText:'Cancelar'}).then(function(r){if(r.isConfirmed){$(form).data('confirmed',true);form.submit()}})});
 $(document).on('submit','.formEliminarOcurrencia',function(e){if($(this).data('confirmed'))return;e.preventDefault();var form=this;Swal.fire({title:'Eliminar esta ocurrencia?',text:'Se quitara esta fecha del calendario. El plan y las demas ocurrencias no cambiaran.',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc3545',confirmButtonText:'Si, eliminar',cancelButtonText:'Conservar'}).then(function(r){if(r.isConfirmed){$(form).data('confirmed',true);form.submit()}})});
});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
