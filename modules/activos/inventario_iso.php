<?php
$page_title = 'Inventario maestro ISO 27001';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__ . '/../../api/activos/_interno/compartido.php';
require_once __DIR__ . '/../../api/activos/_interno/inventario/repositorio.php';
require_permiso('activos', 'ver');

require_once __DIR__ . '/../../api/activos/_interno/inventario/servicio.php';
$rows = activos_inventario_listar()['rows'];
$typeCounts = [];
foreach ($rows as $row) $typeCounts[$row['_Tipo']] = ($typeCounts[$row['_Tipo']] ?? 0) + 1;
$unevaluated = count(array_filter($rows, fn($r) => empty($r['Confidencialidad'])));
$critical = count(array_filter($rows, fn($r) => in_array($r['Criticidad'], ['ALTO','MUY ALTO'], true)));
$withoutOwner = count(array_filter($rows, fn($r) => trim($r['Propietario']) === ''));

include_once __DIR__.'/../../layouts/header.php';
echo iso_styles();
?>
<style>
.inv-hero{border:0;border-radius:17px;color:#fff;background:linear-gradient(118deg,#123746,#078591 68%,#d79636);box-shadow:0 15px 35px rgba(18,55,70,.18)}.inv-hero h1{font-weight:900}.inv-kicker{font-size:10px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;opacity:.78}.inv-table-wrap{max-height:68vh;overflow:auto}.inv-table{min-width:2600px}.inv-table thead th{position:sticky;top:0;z-index:3;padding:10px 8px!important;background:#123746!important;font-size:9px!important}.inv-table td{max-width:260px;font-size:10.5px!important;vertical-align:top!important}.inv-table .inv-id{color:#078591;font-weight:900}.inv-table .inv-name{font-weight:800;color:#203f4d}.inv-toolbar{gap:7px}.inv-toolbar select{min-width:145px}.inv-empty{color:#93a1a7}.inv-cia{white-space:nowrap}.inv-cia span{display:inline-grid;width:23px;height:23px;margin-right:2px;place-items:center;border-radius:6px;background:#eaf2f2;font-weight:800}.inv-desc{white-space:normal;line-height:1.35}.inv-type{font-weight:800;color:#345664}
</style>
<section class="content-header pb-1"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><h1 class="h5 mb-0"><i class="fas fa-clipboard-list mr-2 text-info"></i>Inventario maestro de activos</h1><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li><li class="breadcrumb-item"><a href="index.php">Activos</a></li><li class="breadcrumb-item active">Inventario ISO</li></ol></div></div></section>
<section class="content"><div class="container-fluid"><?=display_msg($msg)?>
<div class="card inv-hero mb-3"><div class="card-body d-md-flex justify-content-between align-items-center"><div><div class="inv-kicker">ISO/IEC 27001:2022 · Control A.5.9</div><h1 class="h4 mb-1">Registro unificado de activos de información</h1><div class="small opacity-75">Equipos, aplicaciones, servicios, información, copias, ubicaciones y roles clave en una sola vista auditable.</div></div><div class="mt-3 mt-md-0"><a href="../../api/activos/inventario/exportar.php" class="btn btn-light font-weight-bold"><i class="fas fa-file-excel mr-1 text-success"></i>Descargar Excel</a></div></div></div>
<div class="row mb-3"><div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong><?=count($rows)?></strong><span>Total de activos</span></div></div><div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-danger"><?=$critical?></strong><span>Criticidad alta / muy alta</span></div></div><div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-warning"><?=$unevaluated?></strong><span>Sin evaluación C-I-D</span></div></div><div class="col-6 col-lg-3 mb-2"><div class="iso-stat"><strong class="text-info"><?=$withoutOwner?></strong><span>Sin propietario</span></div></div></div>
<div class="iso-actionbar mb-3"><div class="iso-search"><i class="fas fa-search"></i><input id="isoSearch" type="search" class="form-control" placeholder="Buscar activo, propietario, ubicación, sistema..."></div><div class="d-flex flex-wrap inv-toolbar"><select id="fTipo" class="form-control form-control-sm"><option value="">Todos los tipos</option><?php foreach (ISO_TIPOS_ACTIVO as $key=>$label): if(empty($typeCounts[$key])) continue; ?><option value="<?=iso_h($label)?>"><?=iso_h($label)?> (<?=$typeCounts[$key]?>)</option><?php endforeach; ?></select><select id="fCrit" class="form-control form-control-sm"><option value="">Toda criticidad</option><option>MUY ALTO</option><option>ALTO</option><option>MEDIO</option><option>BAJO</option><option>SIN EVALUAR</option></select><select id="fClas" class="form-control form-control-sm"><option value="">Toda clasificación</option><option>PUBLICO</option><option>INTERNO</option><option>CONFIDENCIAL</option><option>RESTRINGIDO</option></select><button id="clearFilters" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eraser mr-1"></i>Limpiar</button></div></div>
<div class="card iso-panel"><div class="inv-table-wrap"><table id="tblInventario" class="table table-hover table-bordered iso-table inv-table mb-0"><thead><tr><th>ID activo</th><th>Estado</th><th>Proceso</th><th>Propietario del activo</th><th>Nombre del activo</th><th>Descripción</th><th>Tipo de activo</th><th>Ubicación física / lógica</th><th>Sistema / repositorio</th><th>Datos personales / sensibles</th><th>Requisito legal / contractual</th><th>Confidencialidad</th><th>Integridad</th><th>Disponibilidad</th><th>Criticidad</th><th>Clasificación</th><th>Última revisión</th><th>Observaciones</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td class="inv-id"><?=iso_h($row['IdActivo'])?></td><td><?=iso_h($row['Estado'])?></td><td><?=iso_h($row['Proceso'] ?: '-')?></td><td><?=iso_h($row['Propietario'] ?: '-')?></td><td class="inv-name"><?=iso_h($row['NombreActivo'])?></td><td class="inv-desc"><?=iso_h($row['Descripcion'] ?: '-')?></td><td class="inv-type"><?=iso_h($row['TipoActivo'])?></td><td class="inv-desc"><?=iso_h($row['Ubicacion'] ?: '-')?></td><td class="inv-desc"><?=iso_h($row['SistemaRepositorio'] ?: '-')?></td><td><?=iso_h($row['DatosPersonales'] ?: '-')?></td><td class="inv-desc"><?=iso_h($row['RequisitoLegal'] ?: '-')?></td><td><?=is_numeric($row['Confidencialidad'])?(int)$row['Confidencialidad']:'-'?></td><td><?=is_numeric($row['Integridad'])?(int)$row['Integridad']:'-'?></td><td><?=is_numeric($row['Disponibilidad'])?(int)$row['Disponibilidad']:'-'?></td><td data-order="<?=iso_h($row['Criticidad'])?>"><?=iso_crit_badge($row['Criticidad'])?></td><td><?=iso_h($row['Clasificacion'] ?: '-')?></td><td><?=iso_h($row['UltimaRevision'] ? substr($row['UltimaRevision'],0,10) : '-')?></td><td class="inv-desc"><?=iso_h($row['Observaciones'] ?: '-')?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
</div></section>
<?php
$inlineJs = <<<'JS'
$(function(){
  var table=$('#tblInventario').DataTable({language:DATATABLES_SPANISH,pageLength:25,lengthMenu:[10,25,50,100],order:[[6,'asc'],[0,'asc']],autoWidth:false,scrollX:true,dom:'rt<"d-flex justify-content-between align-items-center flex-wrap p-3"lip>'});
  $('#isoSearch').on('input',function(){table.search(this.value).draw()});
  $('#fTipo').on('change',function(){table.column(6).search(this.value?'^'+$.fn.dataTable.util.escapeRegex(this.value)+'$':'',true,false).draw()});
  $('#fCrit').on('change',function(){table.column(14).search(this.value?'^'+$.fn.dataTable.util.escapeRegex(this.value)+'$':'',true,false).draw()});
  $('#fClas').on('change',function(){table.column(15).search(this.value?'^'+$.fn.dataTable.util.escapeRegex(this.value)+'$':'',true,false).draw()});
  $('#clearFilters').on('click',function(){$('#isoSearch,#fTipo,#fCrit,#fClas').val('');table.search('').columns().search('').draw()});
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
