<?php
$page_title = 'Copias de seguridad';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_permiso('activos', 'ver');
require_once __DIR__ . '/../../api/activos/_interno/copias/servicio.php';
$inlineJs = '';
$extraJs[] = ASSETS_URL . '/js/activos/modulos-formularios.js?v=20261126b';
extract(activos_copias_listar(), EXTR_SKIP);
include_once __DIR__.'/../../layouts/header.php';
echo iso_styles();
?>
<section class="content-header pb-1"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><h1 class="h5 mb-0"><i class="fas fa-database mr-2 text-info"></i>Copias de seguridad</h1><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li><li class="breadcrumb-item"><a href="index.php">Activos</a></li><li class="breadcrumb-item active">Copias de seguridad</li></ol></div></div></section>
<section class="content"><div class="container-fluid">
<?=display_msg($msg)?>
<div class="iso-actionbar mb-3"><div class="iso-search"><i class="fas fa-search"></i><input id="isoSearch" type="search" class="form-control" placeholder="Buscar copia, herramienta, responsable..."></div>
<div><?php if (tiene_permiso('activos', 'crear')): ?><button id="btnNewBackup" class="btn btn-info btn-sm"><i class="fas fa-plus mr-1"></i>Nueva copia</button> <button id="btnNewRecord" class="btn btn-outline-info btn-sm"><i class="fas fa-clipboard-check mr-1"></i>Registrar ejecución / prueba</button><?php endif; ?> <a href="inventario_iso.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-clipboard-list mr-1"></i>Inventario ISO</a></div></div>
<div class="row mb-3">
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong><?=count($activas)?></strong><span>Copias activas</span></div></div>
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-danger"><?=$sinPrueba?></strong><span>Sin prueba de restauración (<?=COPIA_PRUEBA_MAX_DIAS?> d)</span></div></div>
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-warning"><?=$sinExterna?></strong><span>Sin copia externa (offsite)</span></div></div>
<div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-warning"><?=$sinCifrar?></strong><span>Sin cifrado</span><?php if ($ultimaFallida): ?><small class="d-block text-danger"><?=$ultimaFallida?> con última ejecución fallida</small><?php endif; ?></div></div>
</div>
<ul class="nav nav-pills iso-tabs mb-3"><li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tabCopias"><i class="fas fa-database"></i>Copias <span class="badge badge-light"><?=count($copias)?></span></a></li><li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tabRegistros"><i class="fas fa-history"></i>Ejecuciones y pruebas <span class="badge badge-light"><?=count($registros)?></span></a></li></ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="tabCopias"><div class="card iso-panel"><div class="table-responsive"><table id="tblCopias" class="table table-hover iso-table mb-0"><thead><tr><th>Código / copia</th><th>Qué respalda</th><th>Tipo / frecuencia</th><th>Destino</th><th>Seguridad</th><th>RPO / RTO</th><th>Última ejecución</th><th>Última prueba restauración</th><th>C-I-D</th><th>Criticidad</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($copias as $c): $pruebaVencida = empty($c['FechaUltimaPrueba']) || $c['FechaUltimaPrueba'] < $limite; ?>
<tr><td><span class="iso-name"><?=iso_h($c['NombreCopia'])?></span><small class="text-muted"><?=iso_h($c['CodigoCopia'])?> · <?=iso_h($c['Herramienta'] ?: 'Sin herramienta')?></small></td>
<td style="min-width:200px"><?=iso_rel_labels($relMap[(int)$c['IdCopia']] ?? [])?></td>
<td><?=iso_h($c['TipoCopia'])?><small class="d-block text-muted"><?=iso_h($c['Frecuencia'] ?: '-')?><?=$c['HoraEjecucion'] ? ' · '.iso_h(substr($c['HoraEjecucion'], 0, 5)) : ''?><?=$c['RetencionDias'] ? ' · ret. '.(int)$c['RetencionDias'].' d' : ''?></small></td>
<td><?=iso_h($c['MedioAlmacenamiento'] ?: '-')?><small class="d-block text-muted"><?=iso_h($c['UbicacionDestino'] ?: 'Sin ubicación')?></small></td>
<td class="text-nowrap"><span title="Copia externa (offsite)"><i class="fas fa-cloud-upload-alt text-muted"></i> <?=iso_bool_icon($c['CopiaExterna'])?></span> <span title="Cifrada"><i class="fas fa-key text-muted"></i> <?=iso_bool_icon($c['Cifrado'])?></span></td>
<td><?=$c['RPOHoras'] !== null ? (int)$c['RPOHoras'].' h' : '-'?> / <?=$c['RTOHoras'] !== null ? (int)$c['RTOHoras'].' h' : '-'?></td>
<td><?php if ($c['FechaUltimaEjecucion']): ?><?=iso_h(substr($c['FechaUltimaEjecucion'], 0, 16))?><small class="d-block"><span class="badge badge-<?=$c['ResultadoUltimaEjecucion']==='EXITOSO'?'success':($c['ResultadoUltimaEjecucion']==='PARCIAL'?'warning':'danger')?>"><?=iso_h($c['ResultadoUltimaEjecucion'])?></span></small><?php else: ?><span class="text-muted">Sin registro</span><?php endif; ?></td>
<td><?php if ($c['FechaUltimaPrueba']): ?><span class="<?=$pruebaVencida ? 'text-danger font-weight-bold' : ''?>"><?=iso_h($c['FechaUltimaPrueba'])?></span><small class="d-block"><span class="badge badge-<?=$c['ResultadoUltimaPrueba']==='EXITOSO'?'success':($c['ResultadoUltimaPrueba']==='PARCIAL'?'warning':'danger')?>"><?=iso_h($c['ResultadoUltimaPrueba'])?></span></small><?php else: ?><span class="badge badge-danger">Nunca probada</span><?php endif; ?></td>
<td><?=iso_cia_chips($c)?></td><td><?=iso_crit_badge($c['Criticidad'])?></td>
<td><span class="badge badge-<?=$c['EstadoCopia']==='ACTIVA'?'success':'secondary'?>"><?=iso_h($c['EstadoCopia'])?></span></td>
<td class="text-nowrap"><?php if (tiene_permiso('activos', 'editar')):
  $rec = array_filter($c, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY); $rec['Relaciones'] = $relMap[(int)$c['IdCopia']] ?? []; ?>
<button class="btn btn-outline-info btn-sm btn-edit-backup" data-record="<?=iso_h(json_encode($rec, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP))?>"><i class="fas fa-edit"></i></button><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>

<div class="tab-pane fade" id="tabRegistros"><div class="card iso-panel"><div class="table-responsive"><table id="tblRegistros" class="table table-hover iso-table mb-0"><thead><tr><th>Fecha</th><th>Copia</th><th>Tipo</th><th>Resultado</th><th>Tamaño</th><th>Duración</th><th>Ejecutado por</th><th>Evidencia</th><th>Observaciones</th></tr></thead><tbody>
<?php foreach ($registros as $r): ?>
<tr><td class="text-nowrap"><?=iso_h(substr($r['FechaEvento'], 0, 16))?></td><td><span class="iso-name"><?=iso_h($r['NombreCopia'])?></span><small class="text-muted"><?=iso_h($r['CodigoCopia'])?></small></td><td><?=$r['TipoRegistro']==='EJECUCION' ? 'Ejecución' : '<strong>Prueba de restauración</strong>'?></td><td><span class="badge badge-<?=$r['Resultado']==='EXITOSO'?'success':($r['Resultado']==='PARCIAL'?'warning':'danger')?>"><?=iso_h($r['Resultado'])?></span></td><td><?=$r['TamanoGB'] !== null ? iso_h($r['TamanoGB']).' GB' : '-'?></td><td><?=$r['DuracionMin'] !== null ? (int)$r['DuracionMin'].' min' : '-'?></td><td><?=iso_h($r['EjecutadoPor'] ?: '-')?></td><td><?=iso_h($r['Evidencia'] ?: '-')?></td><td><?=iso_h($r['Observaciones'])?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div>
</div></section>

<div class="modal fade iso-modal" id="modalCopia" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><form method="post" data-activos-api="copias" action="copias.php" id="formCopia" data-review="<?=date('Y-m-d', strtotime('+1 year'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_backup"><input type="hidden" name="IdCopia" value="0">
<div class="modal-header"><div><small class="text-white-50 text-uppercase">A.8.13 · Respaldo de la información</small><h5 class="modal-title font-weight-bold">Copia de seguridad</h5></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
<div class="modal-body">
<div class="iso-section"><div class="iso-section-title"><i class="fas fa-fingerprint mr-1 text-info"></i>Identidad</div><div class="row">
<div class="col-md-3 form-group"><label>Código *</label><input name="CodigoCopia" maxlength="30" class="form-control" required placeholder="BKP-001"></div>
<div class="col-md-5 form-group"><label>Nombre *</label><input name="NombreCopia" maxlength="150" class="form-control" required placeholder="Ej. Respaldo diario BD SAP HANA"></div>
<div class="col-md-2 form-group"><label>Tipo *</label><select name="TipoCopia" class="form-control"><?php foreach ($tiposCopia as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-2 form-group"><label>Estado *</label><select name="EstadoCopia" class="form-control"><?php foreach ($estadosCopia as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-12 form-group mb-0"><label>Alcance (qué incluye y qué no)</label><textarea name="Alcance" class="form-control" rows="2" placeholder="Ej. Base HANA completa + carpetas compartidas de Ingeniería. No incluye PCs de usuario."></textarea></div></div></div>
<div class="iso-section"><div class="iso-section-title"><i class="fas fa-link mr-1 text-info"></i>Activos que respalda</div><?=iso_checklist('Respalda', $tiposRespaldables)?><small class="text-muted">Marque servidores, aplicaciones, servicios y activos de información cubiertos. Así se ve qué activo crítico NO tiene respaldo.</small></div>
<div class="iso-section"><div class="iso-section-title"><i class="fas fa-cogs mr-1 text-info"></i>Ejecución y destino</div><div class="row">
<div class="col-md-4 form-group"><label>Herramienta / software</label><input name="Herramienta" maxlength="100" class="form-control" placeholder="Veeam, Synology, script, HANA Studio..."></div>
<div class="col-md-2 form-group"><label>Medio</label><select name="MedioAlmacenamiento" class="form-control"><option value="">-</option><?php foreach ($medios as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-2 form-group"><label>Frecuencia</label><select name="Frecuencia" class="form-control"><option value="">-</option><?php foreach ($frecuencias as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-2 form-group"><label>Hora</label><input type="time" name="HoraEjecucion" class="form-control"></div>
<div class="col-md-2 form-group"><label>Retención (días)</label><input type="number" min="1" name="RetencionDias" class="form-control"></div>
<div class="col-md-5 form-group"><label>Ubicación física del destino</label><select name="IdUbicacionDestino" class="form-control"><option value="">Seleccionar...</option><?php foreach ($ubicaciones as $u): ?><option value="<?=(int)$u['IdUbicacion']?>"><?=iso_h($u['CodigoUbicacion'].' · '.$u['NombreUbicacion'])?></option><?php endforeach; ?></select></div>
<div class="col-md-7 form-group"><label>Detalle del destino</label><input name="DetalleDestino" maxlength="255" class="form-control" placeholder="Equipo, carpeta, bucket, número de cinta..."></div>
<div class="col-md-2 form-group"><label>RPO (horas)</label><input type="number" min="0" name="RPOHoras" class="form-control" title="Máxima pérdida de datos tolerable"></div>
<div class="col-md-2 form-group"><label>RTO (horas)</label><input type="number" min="0" name="RTOHoras" class="form-control" title="Tiempo máximo para restaurar"></div>
<div class="col-md-4 form-group"><label>Responsable</label><input name="Responsable" maxlength="120" class="form-control"></div>
<div class="col-md-4 form-group"><label>Proveedor</label><input name="Proveedor" maxlength="100" class="form-control"></div>
<div class="col-md-12 d-flex flex-wrap pb-2">
<div class="custom-control custom-switch mr-4"><input type="checkbox" class="custom-control-input" id="swExterna" name="CopiaExterna" value="1"><label class="custom-control-label" for="swExterna" style="text-transform:none;font-size:12px">Hay copia fuera de la sede (offsite / nube)</label></div>
<div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="swCifrado" name="Cifrado" value="1"><label class="custom-control-label" for="swCifrado" style="text-transform:none;font-size:12px">La copia está cifrada</label></div></div>
<div class="col-12 form-group mb-0"><label>Observaciones</label><textarea name="Observaciones" class="form-control" rows="2"></textarea></div></div></div>
<?=iso_cia_form()?>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar copia</button></div></form></div></div></div>

<div class="modal fade iso-modal" id="modalRegistro" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="post" data-activos-api="copias" action="copias.php" id="formRegistro"><?=csrf_input()?><input type="hidden" name="action" value="save_record">
<div class="modal-header"><div><small class="text-white-50 text-uppercase">Evidencia para auditoría</small><h5 class="modal-title font-weight-bold">Registrar ejecución o prueba de restauración</h5></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><div class="iso-section mb-0"><div class="row">
<div class="col-md-7 form-group"><label>Copia *</label><select name="IdCopia" class="form-control" required><option value="">Seleccionar...</option><?=$copiaOptions?></select></div>
<div class="col-md-5 form-group"><label>Tipo de evento *</label><select name="TipoRegistro" class="form-control"><option value="EJECUCION">Ejecución de la copia</option><option value="PRUEBA_RESTAURACION">Prueba de restauración</option></select></div>
<div class="col-md-4 form-group"><label>Fecha y hora *</label><input type="datetime-local" name="FechaEvento" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Resultado *</label><select name="Resultado" class="form-control"><?php foreach ($resultados as $t): ?><option><?=iso_h($t)?></option><?php endforeach; ?></select></div>
<div class="col-md-2 form-group"><label>Tamaño (GB)</label><input type="number" min="0" step="0.01" name="TamanoGB" class="form-control"></div>
<div class="col-md-2 form-group"><label>Duración (min)</label><input type="number" min="0" name="DuracionMin" class="form-control"></div>
<div class="col-md-5 form-group"><label>Ejecutado por</label><input name="EjecutadoPor" maxlength="120" class="form-control" placeholder="Si se deja vacío, usa su usuario"></div>
<div class="col-md-7 form-group"><label>Evidencia</label><input name="Evidencia" maxlength="255" class="form-control" placeholder="N.º de ticket, ruta del log o captura"></div>
<div class="col-12 form-group mb-0"><label>Observaciones</label><textarea name="Observaciones" class="form-control" rows="2" placeholder="En pruebas: qué se restauró, en qué equipo y cuánto demoró"></textarea></div></div></div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar evento</button></div></form></div></div></div>

<?php
$inlineJs = iso_js();
$inlineJs .= <<<'JS'
$(function(){
  var t1=$('#tblCopias').DataTable({language:DATATABLES_SPANISH,pageLength:10,order:[],autoWidth:false,dom:'rt<"d-flex justify-content-between align-items-center flex-wrap p-3"lip>'});
  var t2=$('#tblRegistros').DataTable({language:DATATABLES_SPANISH,pageLength:10,order:[[0,'desc']],autoWidth:false,dom:'rt<"d-flex justify-content-between align-items-center flex-wrap p-3"lip>'});
  $('#isoSearch').on('input',function(){t1.search(this.value).draw();t2.search(this.value).draw()});
  var f=$('#formCopia')[0];
  $('#btnNewBackup').on('click',function(){isoNew(f);f.elements.IdCopia.value=0;$('#modalCopia .modal-title').text('Nueva copia de seguridad');$('#modalCopia').modal('show')});
  $('.btn-edit-backup').on('click',function(){var d=$(this).data('record');if(typeof d==='string')d=JSON.parse(d);isoFill(f,d);f.elements.IdCopia.value=d.IdCopia;isoChecks(f,'Respalda',d.Relaciones);if(d.HoraEjecucion)f.elements.HoraEjecucion.value=String(d.HoraEjecucion).substring(0,5);$('#modalCopia .modal-title').text('Editar copia de seguridad');$('#modalCopia').modal('show')});
  var g=$('#formRegistro')[0];
  $('#btnNewRecord').on('click',function(){g.reset();isoCsrf(g);var n=new Date(),p=function(x){return String(x).padStart(2,'0')};g.elements.FechaEvento.value=n.getFullYear()+'-'+p(n.getMonth()+1)+'-'+p(n.getDate())+'T'+p(n.getHours())+':'+p(n.getMinutes());$('#modalRegistro').modal('show')});
  if(location.hash==='#tabRegistros')$('.iso-tabs a[href="#tabRegistros"]').tab('show');
  $('.iso-tabs a').on('shown.bs.tab',function(){$.fn.dataTable.tables({visible:true,api:true}).columns.adjust()});
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
