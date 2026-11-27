<?php
$page_title = 'Nueva inspección de pantalla limpia';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist_pantalla.php');

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'crear');

$maintenanceId = (int)($_GET['mantenimiento_id'] ?? 0);
$order = $maintenanceId > 0 ? mti_cp_order($maintenanceId) : null;
if ($maintenanceId > 0 && !$order) {
    $session->msg('d', 'La orden de mantenimiento seleccionada no existe.');
    redirect('checklist_pantalla.php', false);
}
if ($order) {
    $existing = $db->fetch_assoc($db->query("SELECT id FROM mti_checklist_pantalla WHERE mantenimiento_id={$maintenanceId} LIMIT 1"));
    if ($existing) redirect('checklist_pantalla_detalle.php?id=' . (int)$existing['id'], false);
}
$orders = !$order ? find_by_sql(
    "SELECT m.id,m.folio,m.fecha_programada,q.Host,q.Tipo_Activo,COALESCE(NULLIF(m.usuario_asignado_nombre,''),u.name,'Sin usuario asignado') usuario "
    . "FROM activos_mantenimientos m INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina "
    . "LEFT JOIN users u ON u.id=m.usuario_asignado_id "
    . "LEFT JOIN mti_checklist_pantalla c ON c.mantenimiento_id=m.id "
    . "WHERE c.id IS NULL AND m.estado<>'CANCELADO' ORDER BY m.id DESC LIMIT 300"
) : [];
$current = current_user();
$criteria = mti_cp_criteria();
$inspectedUser = $order ? trim((string)($order['usuario_asignado_nombre'] ?: $order['usuario_actual'])) : '';
$area = $order ? trim((string)$order['area_usuario']) : '';

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
:root{--cp-ink:#123746;--cp-teal:#117f88;--cp-gold:#e39a33;--cp-soft:#f2f8f7}
.cpf-hero{border:0;border-radius:16px;color:#fff;background:linear-gradient(120deg,var(--cp-ink),var(--cp-teal));box-shadow:0 14px 34px rgba(18,55,70,.18)}
.cpf-panel{border:1px solid #d8e7e5;border-radius:14px;background:#fff;overflow:hidden}.cpf-panel-title{padding:14px 18px;background:var(--cp-soft);border-bottom:1px solid #d8e7e5;color:var(--cp-ink);font-weight:800}
.cpf-general{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;padding:18px}.cpf-field{padding:12px;border:1px solid #dce7e6;border-radius:10px;background:#fff}.cpf-field small{display:block;color:#71858d;font-size:9px;font-weight:800;text-transform:uppercase}.cpf-field strong{display:block;color:var(--cp-ink);margin-top:3px}
.cpf-item{border:1px solid #dce7e6;border-radius:14px;background:#fff;overflow:hidden;box-shadow:0 5px 16px rgba(18,55,70,.05)}.cpf-question{display:flex;gap:13px;align-items:flex-start;padding:16px 18px;background:#f7faf9;border-bottom:1px solid #e0e9e8}.cpf-number{display:flex;flex:0 0 38px;height:38px;align-items:center;justify-content:center;border-radius:10px;background:var(--cp-ink);color:#fff;font-weight:900}.cpf-question strong{color:var(--cp-ink);line-height:1.4}.cpf-body{padding:16px 18px}
.cpf-states{display:flex;flex-wrap:wrap;gap:8px}.cpf-state input{position:absolute;opacity:0}.cpf-state label{margin:0;padding:8px 13px;border:1px solid #cedcdb;border-radius:22px;color:#506a73;background:#fff;cursor:pointer;font-size:12px;font-weight:700}.cpf-state input:checked+label{color:#fff;border-color:var(--cp-teal);background:var(--cp-teal)}.cpf-state.no input:checked+label{border-color:#dc3545;background:#dc3545}.cpf-state.na input:checked+label{border-color:#6c757d;background:#6c757d}
.cpf-upload{display:flex;align-items:center;justify-content:center;gap:12px;min-height:96px;padding:18px;border:2px dashed #9fc8c5;border-radius:12px;background:#f4fbfa;text-align:center}.cpf-upload input{display:none}.cpf-previews{display:flex;flex-wrap:wrap;gap:12px;margin-top:14px}.cpf-preview{position:relative;width:150px;height:110px;border-radius:11px;overflow:hidden;border:1px solid #d3dfde;box-shadow:0 4px 12px rgba(18,55,70,.08)}.cpf-preview img{width:100%;height:100%;object-fit:cover}.cpf-preview button{position:absolute;right:6px;top:6px;width:26px;height:26px;padding:0;border:0;border-radius:50%;color:#fff;background:#dc3545}.cpf-evidence-note{color:#5f7881;font-size:12px}
.cpf-note-required{display:none;color:#c13b48;font-size:11px;font-weight:700;margin-top:5px}.cpf-item.is-noncompliant{border-color:#e6aab0}.cpf-item.is-noncompliant .cpf-note-required{display:block}
@media(max-width:991px){.cpf-general{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:575px){.cpf-general{grid-template-columns:1fr}.cpf-body{padding:14px}.cpf-states{display:grid;grid-template-columns:1fr}.cpf-state label{display:block;text-align:center}}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0"><i class="fas fa-shield-screen mr-2 text-info"></i>Nueva inspección</h1><a href="checklist_pantalla.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div></section>
<section class="content"><div class="container-fluid"><?= display_msg($msg) ?>
<?php if(!$order): ?>
 <div class="card cpf-hero mb-3"><div class="card-body"><div class="small text-uppercase font-weight-bold">Paso 1</div><h2 class="h4 font-weight-bold mb-1">Seleccione la orden de Mantenimiento TI</h2><div>El equipo, la PC y el usuario quedarán vinculados a esta inspección.</div></div></div>
 <div class="card"><div class="card-body"><form method="get"><div class="form-group"><label>Orden / equipo *</label><select name="mantenimiento_id" class="form-control" required><option value="">Seleccione una orden sin checklist...</option><?php foreach($orders as $candidate): ?><option value="<?= (int)$candidate['id'] ?>"><?= mti_cp_h($candidate['folio'].' | '.$candidate['Host'].' | '.$candidate['usuario'].' | '.date('d/m/Y',strtotime($candidate['fecha_programada']))) ?></option><?php endforeach; ?></select></div><button class="btn btn-info font-weight-bold"><i class="fas fa-arrow-right mr-1"></i>Continuar</button></form><?php if(!$orders): ?><div class="alert alert-light border mt-3 mb-0">No hay órdenes disponibles sin una inspección de pantalla limpia.</div><?php endif; ?></div></div>
<?php else: ?>
 <div class="card cpf-hero mb-3"><div class="card-body"><div class="small text-uppercase font-weight-bold">Procedimiento T-SI-PT-14</div><h2 class="h4 font-weight-bold mb-1">Checklist de escritorio y pantalla limpia - PC</h2><div>Complete los 12 criterios. Una respuesta “No cumple” requiere observación.</div></div></div>
 <form method="post" action="guardar_checklist_pantalla.php" enctype="multipart/form-data" id="formChecklistPantalla">
  <?= csrf_input() ?><input type="hidden" name="mantenimiento_id" value="<?= (int)$order['id'] ?>">
  <div class="cpf-panel mb-3"><div class="cpf-panel-title"><i class="fas fa-id-card mr-2 text-info"></i>Datos generales de la inspección</div><div class="cpf-general">
   <div class="cpf-field"><small>Fecha de inspección</small><strong><?= date('d/m/Y H:i') ?></strong></div>
   <div class="cpf-field"><small>Orden vinculada</small><strong><?= mti_cp_h($order['folio']) ?></strong></div>
   <div class="cpf-field"><small>Nombre del equipo (PC)</small><strong><?= mti_cp_h($order['Host']) ?></strong></div>
   <div class="cpf-field"><small>Responsable</small><strong><?= mti_cp_h($current['name'] ?? 'Usuario actual') ?></strong></div>
   <div class="cpf-field"><small>Tipo de activo</small><strong><?= mti_cp_h($order['Tipo_Activo']) ?></strong></div>
  </div><div class="row px-3 pb-3"><div class="col-md-7 form-group mb-md-0"><label>Usuario inspeccionado *</label><input name="usuario_inspeccionado" class="form-control" maxlength="150" required value="<?= mti_cp_h($inspectedUser) ?>" placeholder="Nombre del usuario responsable de la PC"></div><div class="col-md-5 form-group mb-0"><label>Área *</label><input name="area" class="form-control" maxlength="100" required value="<?= mti_cp_h($area) ?>" placeholder="Ej.: Sistemas TI"></div></div></div>
  <div class="row">
  <?php foreach($criteria as $number=>$criterion): ?><div class="col-xl-6 mb-3"><section class="cpf-item" data-item="<?= $number ?>"><div class="cpf-question"><span class="cpf-number"><?= str_pad((string)$number,2,'0',STR_PAD_LEFT) ?></span><strong><?= mti_cp_h($criterion) ?></strong></div><div class="cpf-body">
   <label class="d-block small text-uppercase font-weight-bold text-muted">Estado *</label><div class="cpf-states mb-3">
    <?php foreach(['CUMPLE'=>'Cumple','NO_CUMPLE'=>'No cumple','NO_APLICA'=>'No aplica'] as $value=>$label): ?><span class="cpf-state <?= $value==='NO_CUMPLE'?'no':($value==='NO_APLICA'?'na':'') ?>"><input type="radio" id="estado<?= $number.$value ?>" name="estado[<?= $number ?>]" value="<?= $value ?>" required><label for="estado<?= $number.$value ?>"><?= $label ?></label></span><?php endforeach; ?>
   </div>
   <div class="form-group mt-3 mb-0"><label>Observación</label><textarea name="observacion[<?= $number ?>]" class="form-control" rows="2" maxlength="1000" placeholder="Escribir una nota sobre este criterio..."></textarea><div class="cpf-note-required"><i class="fas fa-circle-exclamation mr-1"></i>La observación es obligatoria cuando el estado es “No cumple”.</div></div>
  </div></section></div><?php endforeach; ?>
  </div>
  <div class="cpf-panel mb-3"><div class="cpf-panel-title"><i class="fas fa-camera mr-2 text-info"></i>Evidencia fotográfica general</div><div class="p-3">
   <div class="cpf-upload"><div><label class="btn btn-info font-weight-bold mb-2" for="fotosGlobales"><i class="fas fa-images mr-2"></i>Seleccionar fotografías</label><div class="cpf-evidence-note">Adjunte hasta 4 fotografías generales de la inspección. Formatos JPG, PNG o WEBP; máximo 8 MB por imagen.</div><input type="file" id="fotosGlobales" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple></div></div>
   <div class="cpf-previews" id="previewsGlobales"></div>
  </div></div>
  <div class="card"><div class="card-body d-md-flex justify-content-between align-items-center"><div><strong>Revise la información antes de guardar.</strong><div class="small text-muted">El checklist quedará vinculado a <?= mti_cp_h($order['folio'].' / '.$order['Host']) ?>.</div></div><button class="btn btn-success btn-lg font-weight-bold mt-3 mt-md-0" id="btnGuardarChecklist"><i class="fas fa-floppy-disk mr-2"></i>Guardar checklist</button></div></div>
 </form>
<?php endif; ?>
</div></section>
<?php
$inlineJs = <<<'JS'
$(function(){
  var selectedFiles=[];
  var photoInput=document.getElementById('fotosGlobales');
  function syncFiles(){var dt=new DataTransfer();selectedFiles.forEach(function(file){dt.items.add(file);});photoInput.files=dt.files;}
  $(photoInput).on('change',function(){
    Array.from(photoInput.files).forEach(function(file){if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>8*1024*1024){Swal.fire('Archivo no válido','Use JPG, PNG o WEBP de máximo 8 MB.','warning');return;}if(selectedFiles.length<4)selectedFiles.push(file);});
    syncFiles();renderPhotos();if(selectedFiles.length>=4){Swal.fire({toast:true,position:'top-end',icon:'info',title:'Se alcanzó el máximo de 4 fotografías',showConfirmButton:false,timer:2200});}
  });
  function renderPhotos(){var box=$('#previewsGlobales').empty();selectedFiles.forEach(function(file,index){var url=URL.createObjectURL(file);var item=$('<div class="cpf-preview"><img alt="Vista previa"><button type="button" title="Quitar">&times;</button></div>');item.find('img').attr('src',url);item.find('button').on('click',function(){URL.revokeObjectURL(url);selectedFiles.splice(index,1);syncFiles();renderPhotos();});box.append(item);});}
  $('input[type=radio][name^="estado"]').on('change',function(){var item=$(this).closest('.cpf-item'),note=item.find('textarea');var required=this.value==='NO_CUMPLE';item.toggleClass('is-noncompliant',required);note.prop('required',required);});
  $('#formChecklistPantalla').on('submit',function(e){var invalid=null;$('.cpf-item').each(function(){var state=$(this).find('input[type=radio]:checked').val();var note=$.trim($(this).find('textarea').val());if(state==='NO_CUMPLE'&&!note){invalid=this;return false;}});if(invalid){e.preventDefault();invalid.scrollIntoView({behavior:'smooth',block:'center'});Swal.fire('Falta una observación','Explique el criterio marcado como No cumple.','warning');return;}$('#btnGuardarChecklist').prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...');});
});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
