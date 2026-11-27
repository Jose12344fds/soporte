<?php
$page_title = 'Activos de información';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_permiso('activos', 'ver');
require_once __DIR__ . '/../../api/activos/_interno/informacion/servicio.php';
$inlineJs = '';
$extraJs[] = ASSETS_URL . '/js/activos/modulos-formularios.js?v=20261126b';
extract(activos_informacion_listar(), EXTR_SKIP);
include_once __DIR__.'/../../layouts/header.php';
echo iso_styles();
?>
<section class="content-header pb-1"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><h1 class="h5 mb-0"><i class="fas fa-file-alt mr-2 text-info"></i>Activos de información</h1><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li><li class="breadcrumb-item"><a href="index.php">Activos</a></li><li class="breadcrumb-item active">Información</li></ol></div></div></section>
<section class="content"><div class="container-fluid">
<?=display_msg($msg)?>
<div class="iso-actionbar mb-3"><div class="iso-search"><i class="fas fa-search"></i><input id="isoSearch" type="search" class="form-control" placeholder="Buscar código, nombre, propietario, proceso..."></div>
<div><?php if (tiene_permiso('activos', 'crear')): ?><button id="btnNewInfo" class="btn btn-info btn-sm"><i class="fas fa-plus mr-1"></i>Nuevo activo de información</button><?php endif; ?> <a href="inventario_iso.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-clipboard-list mr-1"></i>Inventario ISO</a></div></div>
<div class="row mb-3">
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong><?=count($activos)?></strong><span>Activos de información</span></div></div>
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-danger"><?=$altaCrit?></strong><span>Alta criticidad</span></div></div>
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-warning"><?=$sinRespaldo?></strong><span>Activos sin copia de seguridad</span></div></div>
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-info"><?=$datosPers?></strong><span>Con datos personales</span><?php if ($sinEvaluar): ?><small class="d-block text-warning"><?=$sinEvaluar?> sin evaluar</small><?php endif; ?></div></div>
</div>
<div class="card iso-panel"><div class="table-responsive"><table id="tblInfo" class="table table-hover iso-table mb-0"><thead><tr><th>Código / activo</th><th>Tipo</th><th>Propietario / custodio</th><th>Proceso</th><th>Dónde se almacena</th><th>Respaldos</th><th>C-I-D</th><th>Criticidad</th><th>Clasificación</th><th>Revisión</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($activos as $a): ?>
<tr><td><span class="iso-name"><?=iso_h($a['NombreActivo'])?></span><small class="text-muted"><?=iso_h($a['CodigoActivo'])?><?=$a['DatosPersonales'] ? ' · <span class="text-info">Datos personales</span>' : ''?></small></td>
<td><?=iso_h($a['TipoInformacion'])?><small class="d-block text-muted"><?=iso_h($a['FormatoSoporte'] ?: '')?></small></td>
<td><?=iso_h($a['PropietarioInformacion'] ?: 'Sin asignar')?><small class="d-block text-muted"><?=iso_h($a['CustodioTecnico'] ?: 'Sin custodio')?></small></td>
<td><?=iso_h($a['Proceso'] ?: '-')?><small class="d-block text-muted"><?=iso_h($a['AreaResponsable'] ?: '')?></small></td>
<td style="min-width:190px"><?=iso_rel_labels($relMap[(int)$a['IdActivoInformacion']] ?? [], 2)?><?php if ($a['UbicacionLogica']): ?><small class="d-block text-muted"><?=iso_h($a['UbicacionLogica'])?></small><?php endif; ?></td>
<td><?php if ((int)$a['TotalRespaldos'] > 0): ?><span class="badge badge-success"><?=(int)$a['TotalRespaldos']?> copia(s)</span><?php else: ?><span class="badge badge-warning">Sin respaldo</span><?php endif; ?></td>
<td><?=iso_cia_chips($a)?></td><td><?=iso_crit_badge($a['Criticidad'])?></td><td><?=iso_h($a['Clasificacion'] ?: '-')?></td><td><?=iso_review_cell($a['ProximaRevision'])?></td>
<td><span class="badge badge-<?=$a['EstadoActivo']==='ACTIVO'?'success':'secondary'?>"><?=iso_h($a['EstadoActivo'])?></span></td>
<td><?php if (tiene_permiso('activos', 'editar')):
  $rec = array_filter($a, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY); $rec['Relaciones'] = $relMap[(int)$a['IdActivoInformacion']] ?? []; ?>
<button class="btn btn-outline-info btn-sm btn-edit-info" data-record="<?=iso_h(json_encode($rec, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP))?>"><i class="fas fa-edit"></i></button><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
</div></section>

<div class="modal fade iso-modal" id="modalInfo" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><form method="post" data-activos-api="informacion" action="informacion.php" id="formInfo" data-review="<?=date('Y-m-d', strtotime('+1 year'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_info"><input type="hidden" name="IdActivoInformacion" value="0">
<div class="modal-header"><div><small class="text-white-50 text-uppercase">A.5.9 · Inventario de información y otros activos asociados</small><h5 class="modal-title font-weight-bold">Activo de información</h5></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
<div class="modal-body">
<div class="iso-section"><div class="iso-section-title"><i class="fas fa-fingerprint mr-1 text-info"></i>Identidad</div><div class="row">
<div class="col-md-3 form-group"><label>Código *</label><input name="CodigoActivo" maxlength="30" class="form-control" required placeholder="INF-001"></div>
<div class="col-md-5 form-group"><label>Nombre *</label><input name="NombreActivo" maxlength="150" class="form-control" required placeholder="Ej. Base de datos SAP Business One"></div>
<div class="col-md-4 form-group"><label>Tipo de información *</label><select name="TipoInformacion" class="form-control" required><option value="">Seleccionar...</option><?php foreach ($tiposInfo as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-12 form-group"><label>Descripción</label><textarea name="Descripcion" class="form-control" rows="2" placeholder="Qué información contiene y para qué se usa"></textarea></div>
<div class="col-md-4 form-group"><label>Proceso de negocio</label><input name="Proceso" maxlength="120" class="form-control" placeholder="Ventas, producción, tareo..."></div>
<div class="col-md-4 form-group"><label>Área responsable</label><input name="AreaResponsable" maxlength="120" class="form-control"></div>
<div class="col-md-4 form-group"><label>Estado *</label><select name="EstadoActivo" class="form-control"><?php foreach ($estados as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div></div></div>
<div class="iso-section"><div class="iso-section-title"><i class="fas fa-user-shield mr-1 text-info"></i>Propiedad, soporte y cumplimiento</div><div class="row">
<div class="col-md-4 form-group"><label>Propietario de la información</label><input name="PropietarioInformacion" maxlength="120" class="form-control" placeholder="Quien decide quién accede (no TI)"></div>
<div class="col-md-4 form-group"><label>Custodio técnico</label><input name="CustodioTecnico" maxlength="120" class="form-control" placeholder="Quien la administra (TI)"></div>
<div class="col-md-4 form-group"><label>Formato / soporte</label><select name="FormatoSoporte" class="form-control"><option value="">-</option><?php foreach ($formatos as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-12 form-group"><label>Ubicación lógica</label><input name="UbicacionLogica" maxlength="255" class="form-control" placeholder="Ruta, esquema, servidor, carpeta compartida..."></div>
<div class="col-md-4 form-group"><label>Requisito legal / contractual</label><input name="RequisitoLegal" maxlength="150" class="form-control" placeholder="Ej. Ley 29733, SUNAT, contrato con cliente"></div>
<div class="col-md-3 form-group"><label>Plazo de retención</label><input name="PlazoRetencion" maxlength="60" class="form-control" placeholder="Ej. 5 años"></div>
<div class="col-md-5 form-group d-flex align-items-end"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="swDP" name="DatosPersonales" value="1"><label class="custom-control-label" for="swDP" style="text-transform:none;font-size:12px">Contiene datos personales</label></div></div>
<div class="col-12 form-group mb-0"><label>Observaciones</label><textarea name="Observaciones" class="form-control" rows="2"></textarea></div></div></div>
<div class="iso-section"><div class="iso-section-title"><i class="fas fa-link mr-1 text-info"></i>Dónde se almacena o procesa</div><?=iso_checklist('AlmacenadaEn', $tiposAlmacen)?><small class="text-muted">Servidores, aplicaciones, servicios o ubicaciones que la contienen. Los respaldos se vinculan desde Copias de seguridad.</small></div>
<?=iso_cia_form()?>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar activo</button></div></form></div></div></div>

<?php
$inlineJs = iso_js();
$inlineJs .= <<<'JS'
$(function(){
  var table=$('#tblInfo').DataTable({language:DATATABLES_SPANISH,pageLength:10,order:[],autoWidth:false,dom:'rt<"d-flex justify-content-between align-items-center flex-wrap p-3"lip>'});
  $('#isoSearch').on('input',function(){table.search(this.value).draw()});
  var f=$('#formInfo')[0];
  $('#btnNewInfo').on('click',function(){isoNew(f);f.elements.IdActivoInformacion.value=0;$('#modalInfo .modal-title').text('Nuevo activo de información');$('#modalInfo').modal('show')});
  $('.btn-edit-info').on('click',function(){var d=$(this).data('record');if(typeof d==='string')d=JSON.parse(d);isoFill(f,d);f.elements.IdActivoInformacion.value=d.IdActivoInformacion;isoChecks(f,'AlmacenadaEn',d.Relaciones);$('#modalInfo .modal-title').text('Editar activo de información');$('#modalInfo').modal('show')});
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
