<?php
$page_title = 'Detalle de pantalla limpia';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist_pantalla.php');

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'ver');

$id = (int)($_GET['id'] ?? 0);
$rows = find_by_sql(
    "SELECT c.*,m.folio,m.id mantenimiento_id,q.Host,q.Tipo_Activo,u.name registrado_por_nombre "
    . "FROM mti_checklist_pantalla c INNER JOIN activos_mantenimientos m ON m.id=c.mantenimiento_id "
    . "INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina LEFT JOIN users u ON u.id=c.creado_por "
    . "WHERE c.id={$id} LIMIT 1"
);
if (!$rows) { $session->msg('d','El checklist solicitado no existe.'); redirect('checklist_pantalla.php',false); }
$checklist = $rows[0];
$responses = find_by_sql("SELECT * FROM mti_checklist_pantalla_respuestas WHERE checklist_id={$id} ORDER BY numero");
$photos = find_by_sql("SELECT * FROM mti_checklist_pantalla_fotos WHERE checklist_id={$id} ORDER BY id");

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
:root{--cpd-ink:#123746;--cpd-teal:#117f88;--cpd-soft:#f2f8f7}.cpd-hero{border:0;border-radius:16px;color:#fff;background:linear-gradient(120deg,var(--cpd-ink),var(--cpd-teal));box-shadow:0 14px 34px rgba(18,55,70,.18)}.cpd-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.cpd-meta{padding:12px 14px;border:1px solid rgba(255,255,255,.22);border-radius:11px;background:rgba(255,255,255,.08)}.cpd-meta small{display:block;font-size:9px;font-weight:800;text-transform:uppercase;opacity:.75}.cpd-item{border:1px solid #d9e6e5;border-radius:13px;background:#fff;overflow:hidden}.cpd-head{display:flex;align-items:flex-start;gap:12px;padding:15px 17px;background:var(--cpd-soft);border-bottom:1px solid #d9e6e5}.cpd-num{display:flex;flex:0 0 36px;height:36px;align-items:center;justify-content:center;border-radius:9px;background:var(--cpd-ink);color:#fff;font-weight:900}.cpd-head strong{color:var(--cpd-ink);line-height:1.4}.cpd-state{display:inline-block;padding:6px 11px;border-radius:20px;font-size:11px;font-weight:900}.cpd-state.CUMPLE{color:#16704a;background:#dff4e8}.cpd-state.NO_CUMPLE{color:#a3212f;background:#fde3e5}.cpd-state.NO_APLICA{color:#57666d;background:#e9eef0}.cpd-gallery{border:1px solid #d9e6e5;border-radius:14px;background:#fff;overflow:hidden}.cpd-gallery-head{padding:13px 17px;background:var(--cpd-soft);border-bottom:1px solid #d9e6e5;color:var(--cpd-ink);font-weight:800}.cpd-photos{display:flex;flex-wrap:wrap;gap:12px}.cpd-photo{width:180px;height:125px;padding:0;border:0;border-radius:11px;overflow:hidden;background:#edf3f2;box-shadow:0 4px 12px rgba(18,55,70,.09)}.cpd-photo img{width:100%;height:100%;object-fit:cover}.cpd-empty{color:#82949b;font-size:12px}.cpd-observation{padding:12px;border-left:3px solid var(--cpd-teal);border-radius:7px;background:#f7faf9;white-space:pre-wrap}@media(max-width:991px){.cpd-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.cpd-grid{grid-template-columns:1fr}.cpd-photo{width:calc(50% - 6px);height:105px}}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0"><i class="fas fa-shield-screen mr-2 text-info"></i>Detalle de inspección</h1><div><a href="detalle.php?id=<?= (int)$checklist['mantenimiento_id'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-screwdriver-wrench mr-1"></i>Ver OT</a> <a href="checklist_pantalla.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div></div></section>
<section class="content"><div class="container-fluid"><?= display_msg($msg) ?>
 <div class="card cpd-hero mb-3"><div class="card-body"><div class="small text-uppercase font-weight-bold mb-2">Procedimiento T-SI-PT-14</div><h2 class="h4 font-weight-bold mb-3">Checklist de escritorio y pantalla limpia - PC</h2><div class="cpd-grid"><div class="cpd-meta"><small>Fecha</small><strong><?= date('d/m/Y H:i',strtotime($checklist['fecha_inspeccion'])) ?></strong></div><div class="cpd-meta"><small>Usuario inspeccionado</small><strong><?= mti_cp_h($checklist['usuario_inspeccionado']) ?></strong></div><div class="cpd-meta"><small>Área</small><strong><?= mti_cp_h($checklist['area']) ?></strong></div><div class="cpd-meta"><small>PC / OT</small><strong><?= mti_cp_h($checklist['equipo_nombre'].' / '.$checklist['folio']) ?></strong></div><div class="cpd-meta"><small>Responsable</small><strong><?= mti_cp_h($checklist['responsable_nombre']) ?></strong></div></div></div></div>
 <div class="cpd-gallery mb-3"><div class="cpd-gallery-head"><i class="fas fa-images mr-2 text-info"></i>Evidencia fotográfica general <span class="badge badge-info ml-1"><?=count($photos)?> foto(s)</span></div><div class="p-3 cpd-photos"><?php foreach($photos as $photo): ?><button type="button" class="cpd-photo btnFotoCP" data-toggle="modal" data-target="#modalFotoCP" data-src="checklist_pantalla_foto.php?id=<?= (int)$photo['id'] ?>" data-title="Evidencia general de la inspección"><img src="checklist_pantalla_foto.php?id=<?= (int)$photo['id'] ?>" alt="Evidencia general"></button><?php endforeach; ?><?php if(!$photos): ?><span class="cpd-empty"><i class="far fa-image mr-1"></i>La inspección no tiene fotografías adjuntas.</span><?php endif; ?></div></div>
 <div class="row"><?php foreach($responses as $response): ?><div class="col-xl-6 mb-3"><article class="cpd-item h-100"><div class="cpd-head"><span class="cpd-num"><?= str_pad((string)$response['numero'],2,'0',STR_PAD_LEFT) ?></span><strong><?= mti_cp_h($response['criterio_snapshot']) ?></strong></div><div class="p-3"><div class="mb-3"><span class="cpd-state <?= mti_cp_h($response['estado']) ?>"><?= mti_cp_h(str_replace('_',' ',$response['estado'])) ?></span></div><div class="small text-uppercase font-weight-bold text-muted mb-2">Observación</div><div class="cpd-observation"><?= $response['observacion']!==null&&$response['observacion']!==''?mti_cp_h($response['observacion']):'<span class="text-muted">Sin observación</span>' ?></div></div></article></div><?php endforeach; ?></div>
</div></section>
<div class="modal fade" id="modalFotoCP" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content bg-dark"><div class="modal-header border-0 text-white"><h5 class="modal-title" id="fotoCPTitulo">Evidencia</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body text-center"><img id="fotoCPImagen" style="max-width:100%;max-height:76vh;object-fit:contain" alt="Evidencia ampliada"></div></div></div></div>
<?php
$inlineJs = <<<'JS'
$(function(){$('.btnFotoCP').on('click',function(){$('#fotoCPTitulo').text(this.dataset.title||'Evidencia');$('#fotoCPImagen').attr('src',this.dataset.src||'');});$('#modalFotoCP').on('hidden.bs.modal',function(){$('#fotoCPImagen').removeAttr('src');});});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
