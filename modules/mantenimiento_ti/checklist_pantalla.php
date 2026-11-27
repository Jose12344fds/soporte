<?php
$page_title = 'Checklist de escritorio y pantalla limpia';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist_pantalla.php');

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'ver');

$rows = find_by_sql(
    "SELECT c.*,m.folio,q.Host,u.name AS registrado_por_nombre,COUNT(f.id) AS total_fotos "
    . "FROM mti_checklist_pantalla c "
    . "INNER JOIN activos_mantenimientos m ON m.id=c.mantenimiento_id "
    . "INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina "
    . "LEFT JOIN users u ON u.id=c.creado_por "
    . "LEFT JOIN mti_checklist_pantalla_fotos f ON f.checklist_id=c.id "
    . "GROUP BY c.id ORDER BY c.fecha_inspeccion DESC,c.id DESC"
);

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
.cp-hero{border:0;border-radius:16px;color:#fff;background:linear-gradient(120deg,#123746,#117f88 68%,#e18b35);box-shadow:0 14px 32px rgba(18,55,70,.18)}
.cp-kicker{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;opacity:.8}
.cp-card{border:1px solid #dce8e7;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(18,55,70,.06)}
.cp-card thead th{border:0;background:#123746;color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.03em}
.cp-card td{vertical-align:middle}.cp-equipo{color:#117f88;font-weight:800}.cp-meta{color:#72868e;font-size:11px}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center">
 <h1 class="h4 mb-0"><i class="fas fa-desktop mr-2 text-info"></i>Escritorio y pantalla limpia</h1>
 <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
</div></section>
<section class="content"><div class="container-fluid">
 <?= display_msg($msg) ?>
 <div class="card cp-hero mb-3"><div class="card-body d-md-flex justify-content-between align-items-center">
  <div><div class="cp-kicker">Procedimiento T-SI-PT-14</div><h2 class="h4 font-weight-bold mb-1">Checklist de verificación de escritorio y pantalla limpia - PC</h2><div class="small">Inspecciones digitales vinculadas a las órdenes de Mantenimiento TI.</div></div>
  <div class="mt-3 mt-md-0"><a href="exportar_checklist_pantalla.php" class="btn btn-success font-weight-bold mr-2"><i class="fas fa-file-excel mr-1"></i>Descargar Excel</a><?php if(tiene_permiso('mantenimiento_ti','crear')): ?><a href="checklist_pantalla_form.php" class="btn btn-light font-weight-bold"><i class="fas fa-plus-circle mr-1 text-warning"></i>Nueva inspección</a><?php endif; ?></div>
 </div></div>
 <div class="card cp-card"><div class="card-header bg-white"><div class="row align-items-center"><div class="col-md-7"><strong>Inspecciones guardadas</strong><div class="cp-meta">Consulte las respuestas y evidencias sin generar procesos adicionales.</div></div><div class="col-md-5"><input id="cpBuscar" class="form-control form-control-sm" placeholder="Buscar fecha, usuario, área, PC, responsable u OT"></div></div></div>
 <div class="table-responsive"><table class="table table-hover mb-0" id="tblChecklistPantalla"><thead><tr><th>Fecha</th><th>Usuario inspeccionado</th><th>Área</th><th>PC / OT</th><th>Responsable</th><th>Evidencias</th><th></th></tr></thead><tbody>
 <?php foreach($rows as $row): ?><tr>
  <td data-order="<?= mti_cp_h($row['fecha_inspeccion']) ?>"><strong><?= date('d/m/Y',strtotime($row['fecha_inspeccion'])) ?></strong><div class="cp-meta"><?= date('H:i',strtotime($row['fecha_inspeccion'])) ?></div></td>
  <td><?= mti_cp_h($row['usuario_inspeccionado']) ?></td><td><?= mti_cp_h($row['area']) ?></td>
  <td><span class="cp-equipo"><?= mti_cp_h($row['equipo_nombre']) ?></span><div class="cp-meta"><?= mti_cp_h($row['folio']) ?></div></td>
  <td><?= mti_cp_h($row['responsable_nombre']) ?><div class="cp-meta">Registrado por <?= mti_cp_h($row['registrado_por_nombre'] ?: 'Sistema') ?></div></td>
  <td><span class="badge badge-info"><?= (int)$row['total_fotos'] ?> foto(s)</span></td>
  <td><a href="checklist_pantalla_detalle.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-info" title="Ver detalle"><i class="fas fa-eye"></i></a></td>
 </tr><?php endforeach; ?>
 </tbody></table></div></div>
</div></section>
<?php
$inlineJs = <<<'JS'
$(function(){
  var table=$('#tblChecklistPantalla').DataTable({pageLength:15,order:[[0,'desc']],language:DATATABLES_SPANISH,dom:'rt<"row p-3"<"col-md-5"i><"col-md-7"p>>'});
  $('#cpBuscar').on('input',function(){table.search(this.value).draw();});
});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
