<?php
$page_title='OTs cerradas — FH ING';
require_once dirname(dirname(__DIR__)).'/includes/load.php';
require_permiso('ots_ingeniera','ver');
$rows=find_by_sql("SELECT d.*,c.*,c.updated_at AS ultimo_estado FROM fh_ing_ot_detalle d
 INNER JOIN (SELECT ot,MAX(id) AS id FROM fh_ing_ot_detalle GROUP BY ot) u ON u.id=d.id
 INNER JOIN fh_ing_planos_cab c ON c.ot=d.ot WHERE c.estado=3 ORDER BY c.updated_at DESC,d.ot");
function cx($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
include_once dirname(dirname(__DIR__)).'/layouts/header.php';
?>
<style>
.ot-cerradas{padding:24px}.ot-cerradas table,.ot-resumen table{width:100%;border-collapse:collapse;font-size:.85rem}.ot-cerradas th,.ot-cerradas td,.ot-resumen th,.ot-resumen td{padding:9px 12px;border-bottom:1px solid #e2e8f0;text-align:left}.ot-cerradas th,.ot-resumen th{background:#eef4fa}.ot-resumen{position:fixed;inset:0;z-index:1050;background:#101b2bbd;padding:18px;display:none;align-items:center;justify-content:center}.ot-resumen-panel{background:#fff;max-width:1150px;width:100%;max-height:94vh;overflow:auto;border-radius:12px}.ot-resumen header{position:sticky;top:0;background:#17365a;color:#fff;padding:14px 20px;display:flex;justify-content:space-between;gap:15px;z-index:2}.ot-resumen-body{padding:20px}.rpt-kpi-card{background:#f1f5f9;border:1px solid #dbe3ef;border-radius:10px;padding:14px}.rpt-kpi-tag{font-size:.75rem;color:#475569}.rpt-kpi-unit{font-size:.8rem;color:#64748b}.rpt-summary-footer{display:flex;gap:16px;flex-wrap:wrap;margin:15px 0}.ot-cerradas-actions{display:flex;gap:6px;flex-wrap:wrap}
</style>
<div class="ot-cerradas"><div class="card"><div class="card-header bg-success"><h4 class="m-0">OTs cerradas <span class="badge badge-light"><?= count($rows) ?></span></h4></div><div class="card-body">
<div style="display:flex;gap:14px;flex-wrap:wrap;justify-content:space-between;margin-bottom:18px;"><a href="ot_detalle.php" class="btn btn-outline-primary">Volver a OTs activas</a><input class="form-control" id="filtroCerradas" style="max-width:380px;" placeholder="Buscar OT, proyecto o elaborador" aria-label="Buscar OT cerrada"></div>
<div class="table-responsive"><table><thead><tr><th>OT</th><th>Proyecto</th><th>Elaborado por</th><th>Fecha de cierre</th><th>Consulta</th></tr></thead><tbody id="listaCerradas">
<?php foreach($rows as $r): ?><tr><td><strong><?= cx($r['ot']) ?></strong></td><td><?= cx($r['proyecto']) ?></td><td><?= cx($r['elaborado_por']) ?></td><td><?= cx($r['fecha_cierre']??$r['ultimo_estado']) ?></td><td><div class="ot-cerradas-actions"><button type="button" class="btn btn-sm btn-primary ver-resumen" data-ot="<?= cx($r['ot']) ?>">Ver resumen</button><a class="btn btn-sm btn-outline-secondary" href="proyecto_finalizado.php?ot=<?= rawurlencode($r['ot']) ?>">Expediente y documentos</a><a class="btn btn-sm btn-outline-success" href="../../api/fh_ing/exportar_gantt.php?ot=<?= rawurlencode($r['ot']) ?>">Reporte</a></div></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5">Aún no hay proyectos cerrados.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
<div class="ot-resumen" id="resumenCerrada" role="dialog" aria-modal="true" aria-labelledby="rptOT"><div class="ot-resumen-panel"><header><div><strong id="rptOT"></strong><div id="rptProy"></div></div><span id="rptEstadoPill"></span><button type="button" id="cerrarResumen" class="btn btn-sm btn-light" aria-label="Cerrar resumen">×</button></header><div class="ot-resumen-body"><div id="rptPipeline"></div><div id="rptKpiRow"></div><div id="rptSummaryFooter"></div><h5>Cronograma de los dos módulos</h5><div id="rptGanttBody"></div></div></div></div>
<script src="../../assets/js/fh_calendario.js?v=20261027"></script><script src="../../assets/js/fh_resumen_ot.js?v=20261027"></script>
<script>
(function(){var modal=document.getElementById('resumenCerrada'),previous=null;
function close(){modal.style.display='none';document.body.style.overflow='';if(previous)previous.focus();}
document.getElementById('cerrarResumen').addEventListener('click',close);modal.addEventListener('click',function(e){if(e.target===modal)close();});document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
document.querySelectorAll('.ver-resumen').forEach(function(btn){btn.addEventListener('click',function(){previous=btn;modal.style.display='flex';document.body.style.overflow='hidden';document.getElementById('rptOT').textContent=btn.dataset.ot;document.getElementById('rptKpiRow').textContent='Cargando resumen…';document.getElementById('rptGanttBody').textContent='';document.getElementById('cerrarResumen').focus();fetch('../../api/fh_ing/get_proyecto_resumen.php?ot='+encodeURIComponent(btn.dataset.ot)).then(function(r){return r.json();}).then(function(d){FHResumenOT.render(d);}).catch(function(){document.getElementById('rptKpiRow').textContent='No se pudo cargar el resumen.';});});});
document.getElementById('filtroCerradas').addEventListener('input',function(){var q=this.value.toLocaleLowerCase();document.querySelectorAll('#listaCerradas tr').forEach(function(tr){tr.style.display=tr.textContent.toLocaleLowerCase().includes(q)?'':'none';});});})();
</script>
<?php include_once dirname(dirname(__DIR__)).'/layouts/footer.php'; ?>