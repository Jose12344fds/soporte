<?php
$page_title = 'Indicadores de Mantenimiento TI';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'ver');

$year = (int)($_GET['anio'] ?? date('Y'));
if ($year < 2020 || $year > 2100) $year = (int)date('Y');
$start = sprintf('%04d-01-01', $year);
$end = sprintf('%04d-12-31', $year);
$periodLabel = "Plan anual {$year}";
function mti_ind_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function mti_ind_pct($part, $total): float { return (float)$total > 0 ? round((float)$part * 100 / (float)$total, 1) : 0.0; }
function mti_ind_scalar(string $sql): array {
    global $db;
    return $db->fetch_assoc($db->query($sql)) ?: [];
}
function mti_ind_tone(float $value, float $target): string {
    if ($value >= $target) return 'success';
    if ($value >= max(0, $target - 10)) return 'warning';
    return 'danger';
}

// Cada fecha del plan cuenta aun cuando todavia no se haya generado su OT.
// Las OT independientes se incluyen una sola vez.
$scheduleSql = "SELECT o.fecha_programada,"
    . " CASE WHEN m.estado='COMPLETADO' THEN 'COMPLETADO'"
    . " WHEN m.id IS NULL AND o.estado='EJECUTADA' THEN 'COMPLETADO'"
    . " ELSE COALESCE(m.estado,o.estado) END estado,m.fecha_ejecucion"
    . " FROM activos_mantenimiento_ocurrencias o"
    . " LEFT JOIN activos_mantenimientos m ON m.id=o.mantenimiento_id"
    . " WHERE o.estado<>'CANCELADA' AND (m.id IS NULL OR m.estado<>'CANCELADO')"
    . " UNION ALL SELECT m.fecha_programada,m.estado,m.fecha_ejecucion"
    . " FROM activos_mantenimientos m WHERE m.estado<>'CANCELADO'"
    . " AND NOT EXISTS (SELECT 1 FROM activos_mantenimiento_ocurrencias o WHERE o.mantenimiento_id=m.id)";
$orders = mti_ind_scalar(
    "SELECT COUNT(*) programados,SUM(estado='COMPLETADO') completados,"
    . " SUM(estado='COMPLETADO' AND fecha_ejecucion IS NOT NULL AND DATE(fecha_ejecucion)<=fecha_programada) a_tiempo,"
    . " SUM(estado<>'COMPLETADO' AND fecha_programada<CURDATE()) vencidos,"
    . " SUM(estado='EN_PROCESO') en_proceso FROM ({$scheduleSql}) agenda"
    . " WHERE fecha_programada BETWEEN '{$start}' AND '{$end}'"
);
$programmed = (int)($orders['programados'] ?? 0);
$completed = (int)($orders['completados'] ?? 0);
$onTime = (int)($orders['a_tiempo'] ?? 0);
$overdue = (int)($orders['vencidos'] ?? 0);
$maintenancePct = mti_ind_pct($completed, $programmed);
$onTimePct = mti_ind_pct($onTime, $completed);

$schemaReady = mti_chk_schema_ready();
$checklists = ['asignados' => 0, 'completados' => 0];
$findings = ['total' => 0, 'cerrados' => 0, 'abiertos' => 0, 'criticos_abiertos' => 0, 'dias_cierre' => 0];
$actions = ['total' => 0, 'ejecutadas' => 0, 'vencidas' => 0];
if ($schemaReady) {
    $checklists = mti_ind_scalar(
        "SELECT COUNT(*) asignados,SUM(c.estado='COMPLETADO') completados"
        . " FROM activos_mantenimiento_checklists c"
        . " INNER JOIN activos_mantenimientos m ON m.id=c.mantenimiento_id"
        . " WHERE m.fecha_programada BETWEEN '{$start}' AND '{$end}' AND c.estado<>'ANULADO'"
    );
    $findings = mti_ind_scalar(
        "SELECT COUNT(*) total,SUM(estado='CERRADO') cerrados,"
        . " SUM(estado NOT IN ('CERRADO','ANULADO')) abiertos,"
        . " SUM(criticidad='CRITICA' AND estado NOT IN ('CERRADO','ANULADO')) criticos_abiertos,"
        . " ROUND(AVG(CASE WHEN estado='CERRADO' THEN DATEDIFF(fecha_cierre,fecha_deteccion) END),1) dias_cierre"
        . " FROM activos_mantenimiento_hallazgos"
        . " WHERE estado<>'ANULADO' AND DATE(fecha_deteccion) BETWEEN '{$start}' AND '{$end}'"
    );
    $actions = mti_ind_scalar(
        "SELECT COUNT(*) total,SUM(a.estado='EJECUTADA') ejecutadas,"
        . " SUM(a.estado IN ('PENDIENTE','EN_PROCESO') AND a.fecha_compromiso<CURDATE()) vencidas"
        . " FROM activos_mantenimiento_hallazgo_acciones a"
        . " INNER JOIN activos_mantenimiento_hallazgos h ON h.id=a.hallazgo_id"
        . " WHERE h.estado<>'ANULADO' AND DATE(h.fecha_deteccion) BETWEEN '{$start}' AND '{$end}'"
    );
}
$checklistPct = mti_ind_pct($checklists['completados'] ?? 0, $checklists['asignados'] ?? 0);
$findingPct = mti_ind_pct($findings['cerrados'] ?? 0, $findings['total'] ?? 0);
$actionPct = mti_ind_pct($actions['ejecutadas'] ?? 0, $actions['total'] ?? 0);

$planRows = find_by_sql(
    "SELECT p.id,p.codigo,p.nombre,p.frecuencia,p.intervalo,p.unidad_intervalo,p.activo,q.Host,q.Tipo_Activo,"
    . " o.id ocurrencia_id,o.fecha_programada,o.estado ocurrencia_estado,m.id orden_id,m.estado orden_estado,m.fecha_ejecucion"
    . " FROM activos_mantenimiento_planes p INNER JOIN maquina q ON q.IdMaquina=p.IdMaquina"
    . " LEFT JOIN activos_mantenimiento_ocurrencias o ON o.plan_id=p.id"
    . " AND o.fecha_programada BETWEEN '{$start}' AND '{$end}' AND o.estado<>'CANCELADA'"
    . " LEFT JOIN activos_mantenimientos m ON m.id=o.mantenimiento_id"
    . " WHERE p.fecha_inicio<='{$end}' AND (p.fecha_fin IS NULL OR p.fecha_fin>='{$start}')"
    . " ORDER BY q.Host,p.id,o.fecha_programada,o.id"
);
$planMap = [];
$today = date('Y-m-d');
foreach ($planRows as $row) {
    $id = (int)$row['id'];
    if (!isset($planMap[$id])) $planMap[$id] = ['plan'=>$row,'dates'=>[],'total'=>0,'done'=>0,'overdue'=>0,'future'=>0];
    if (empty($row['ocurrencia_id']) || $row['orden_estado']==='CANCELADO') continue;
    $done = $row['orden_estado']==='COMPLETADO' || (empty($row['orden_id']) && $row['ocurrencia_estado']==='EJECUTADA');
    $late = !$done && $row['fecha_programada']<$today;
    $planMap[$id]['total']++;
    $planMap[$id]['done'] += (int)$done;
    $planMap[$id]['overdue'] += (int)$late;
    $planMap[$id]['future'] += (int)(!$done && !$late);
    $row['visual'] = $done ? 'Completado' : ($late ? 'Vencido' : 'Programado');
    $row['tone'] = $done ? 'success' : ($late ? 'danger' : 'info');
    $planMap[$id]['dates'][] = $row;
}
$overdueRows = find_by_sql(
    "SELECT m.id,m.folio,m.fecha_programada,m.estado,m.prioridad,q.Host"
    . " FROM activos_mantenimientos m INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina"
    . " WHERE m.estado NOT IN ('COMPLETADO','CANCELADO') AND m.fecha_programada<CURDATE()"
    . " AND m.fecha_programada BETWEEN '{$start}' AND '{$end}'"
    . " ORDER BY m.fecha_programada LIMIT 12"
);
$criticalRows = $schemaReady ? find_by_sql(
    "SELECT h.id,h.codigo,h.titulo,h.criticidad,h.estado,h.fecha_deteccion,m.folio,q.Host"
    . " FROM activos_mantenimiento_hallazgos h"
    . " INNER JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id"
    . " INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina"
    . " WHERE h.estado<>'ANULADO'"
    . " AND DATE(h.fecha_deteccion) BETWEEN '{$start}' AND '{$end}'"
    . " ORDER BY CASE WHEN h.estado='CERRADO' THEN 1 ELSE 0 END,FIELD(h.criticidad,'CRITICA','ALTA','MEDIA','BAJA'),h.fecha_deteccion DESC LIMIT 20"
) : [];

$cards = [
    ['total'=>$programmed,'title'=>'Avance anual del mantenimiento','value'=>$maintenancePct,'target'=>100,'detail'=>"{$completed} de {$programmed} programados",'icon'=>'fa-screwdriver-wrench'],
    ['total'=>$completed,'title'=>'Ejecución dentro de fecha','value'=>$onTimePct,'target'=>95,'detail'=>"{$onTime} de {$completed} completados",'icon'=>'fa-clock'],
    ['total'=>(int)($checklists['asignados']??0),'title'=>'Checklists completados','value'=>$checklistPct,'target'=>100,'detail'=>(int)($checklists['completados']??0).' de '.(int)($checklists['asignados']??0).' asignados','icon'=>'fa-list-check'],
    ['total'=>(int)($findings['total']??0),'title'=>'Hallazgos cerrados','value'=>$findingPct,'target'=>90,'detail'=>(int)($findings['cerrados']??0).' de '.(int)($findings['total']??0).' detectados','icon'=>'fa-triangle-exclamation'],
];

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
:root{--ind-ink:#123746;--ind-teal:#137c8b;--ind-orange:#e58a32;--ind-paper:#f3f8f7}.ind-hero{border:0;border-radius:16px;color:#fff;background:linear-gradient(115deg,#102f3b,#126f7c 67%,#d78030);box-shadow:0 16px 38px rgba(18,55,70,.2)}.ind-card{height:100%;padding:18px;border:1px solid #dce8e6;border-radius:14px;background:#fff;box-shadow:0 6px 18px rgba(18,55,70,.07)}.ind-icon{display:flex;width:40px;height:40px;align-items:center;justify-content:center;border-radius:11px;background:#e8f4f2;color:var(--ind-teal)}.ind-value{font-size:31px;font-weight:900;color:var(--ind-ink)}.ind-meta{font-size:11px;color:#71838a}.ind-track{height:9px;border-radius:20px;background:#e6eceb;overflow:hidden}.ind-fill{height:100%;border-radius:20px}.ind-fill.success{background:#2a9d63}.ind-fill.warning{background:#e3a128}.ind-fill.danger{background:#d95656}.ind-table thead th{border:0;background:var(--ind-ink);color:#fff;font-size:10px;text-transform:uppercase}.ind-quarter{padding:15px;border:1px solid #dce8e6;border-radius:12px;background:var(--ind-paper)}.ind-quarter strong{font-size:24px;color:var(--ind-ink)}.ind-note{border-left:4px solid var(--ind-orange);background:#fff8ed}.ind-section{border:1px solid #dce8e6;border-radius:13px;overflow:hidden;background:#fff}.ind-section-title{padding:13px 16px;background:var(--ind-paper);color:var(--ind-ink);font-weight:800}
.mti-report-modal .modal-dialog{max-width:95vw;margin:2.5vh auto}.mti-report-modal .modal-content{height:92vh;border:0;border-radius:18px;overflow:hidden}.mti-report-modal .modal-header{border:0;color:#fff;background:linear-gradient(110deg,#102f3b,#117b88 70%,#df8835)}.mti-report-modal .modal-body{overflow:auto;background:#f1f6f5}.mti-report-filter{padding:14px;border:1px solid #d6e4e2;border-radius:13px;background:#fff}.mti-report-kpi{height:100%;padding:15px;border:1px solid #d6e4e2;border-radius:13px;background:#fff;box-shadow:0 6px 16px rgba(18,55,70,.06)}.mti-report-kpi strong{display:block;font-size:27px;color:var(--ind-ink)}.mti-report-kpi small{font-weight:700;color:#71838a;text-transform:uppercase}.mti-report-table{font-size:12px}.mti-report-table thead th{position:sticky;top:0;z-index:2;white-space:nowrap;background:#123746;color:#fff}.mti-report-summary td,.mti-report-summary th{vertical-align:middle}.mti-state{display:inline-block;padding:4px 9px;border-radius:20px;font-size:10px;font-weight:800}.mti-state-COMPLETADO{color:#166c46;background:#dff4e9}.mti-state-PENDIENTE{color:#a32f2f;background:#fde3e1}.mti-state-FUTURO{color:#17627a;background:#dff2f7}
.mti-round-table tbody td{vertical-align:middle;padding:14px}.mti-round-name{font-weight:900;color:var(--ind-ink)}.mti-round-pct{font-size:25px;font-weight:900;color:var(--ind-teal)}.mti-round-bar{height:8px;min-width:130px;border-radius:10px;background:#e2ecea;overflow:hidden}.mti-round-bar span{display:block;height:100%;border-radius:10px;background:linear-gradient(90deg,#14927d,#e59a31)}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0"><i class="fas fa-chart-line mr-2 text-info"></i>Indicadores de Mantenimiento TI</h1><div><button type="button" id="btnReporteCumplimiento" class="btn btn-sm btn-info mr-2"><i class="fas fa-clipboard-check mr-1"></i>Reporte de cumplimiento</button><a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div></div></section>
<section class="content"><div class="container-fluid">
 <div class="card ind-hero mb-3"><div class="card-body d-lg-flex justify-content-between align-items-center"><div><div class="small text-uppercase font-weight-bold opacity-75">Control de gestión</div><h2 class="h4 font-weight-bold mb-1"><?=mti_ind_h($periodLabel)?></h2><div>Programación, ejecución, verificaciones y cierre de hallazgos.</div></div><form method="get" class="form-inline mt-3 mt-lg-0"><select name="anio" class="form-control mr-2"><?php for($y=(int)date('Y')+1;$y>=2023;$y--):?><option value="<?=$y?>" <?=$y===$year?'selected':''?>><?=$y?></option><?php endfor;?></select><button class="btn btn-light font-weight-bold"><i class="fas fa-filter mr-1"></i>Aplicar</button></form></div></div>
 <?php if(!$schemaReady):?><div class="alert alert-warning">Los indicadores de checklist y hallazgos estarán disponibles cuando la migración esté completa.</div><?php endif;?>
 <div class="row mb-3"><?php foreach($cards as $card):$tone=$card['total']>0?mti_ind_tone($card['value'],$card['target']):'secondary';?><div class="col-md-6 col-xl-3 mb-3"><div class="ind-card"><div class="d-flex justify-content-between"><div class="ind-icon"><i class="fas <?=$card['icon']?>"></i></div><span class="badge badge-<?=$tone?> align-self-start">Meta <?=$card['target']?>%</span></div><div class="ind-value mt-2"><?=$card['total']>0?number_format($card['value'],1).'%':'Sin datos'?></div><div class="font-weight-bold"><?=mti_ind_h($card['title'])?></div><div class="ind-meta mb-2"><?=mti_ind_h($card['detail'])?></div><div class="ind-track"><div class="ind-fill <?=$tone?>" style="width:<?=min(100,$card['value'])?>%"></div></div></div></div><?php endforeach;?></div>
 <div class="alert ind-note small"><strong>Según tu plan:</strong> cuatrimestral = cada 4 meses (3 intervenciones en un año completo); anual = 1 intervención en un año completo, por ejemplo para servidores. Las fechas dependen de la primera fecha programada y de la vigencia del plan. El avance anual incluye fechas futuras; solo se consideran vencidas las pendientes cuya fecha ya pasó. <a href="planes.php">Ver o configurar planes</a>.</div>
 <div class="row mb-3"><div class="col-lg-8"><div class="ind-section h-100"><div class="ind-section-title"><i class="fas fa-calendar-alt mr-2 text-info"></i>Cumplimiento por plan y equipo <?=$year?></div><div class="table-responsive"><table class="table table-sm table-hover ind-table mb-0"><thead><tr><th>Plan / equipo</th><th>Frecuencia</th><th>Fechas del año</th><th>Avance anual</th></tr></thead><tbody>
 <?php foreach($planMap as $item):$p=$item['plan'];$pct=mti_ind_pct($item['done'],$item['total']);?>
 <tr><td><strong><?=mti_ind_h($p['Host'])?></strong><div class="small"><?=mti_ind_h($p['nombre'])?></div><div class="small text-muted"><?=mti_ind_h($p['codigo'].' / '.$p['Tipo_Activo'])?><?=$p['activo']?'':' (inactivo)'?></div></td>
 <td><?=mti_ind_h(ucfirst(strtolower($p['frecuencia'])))?><div class="small text-muted">Cada <?=(int)$p['intervalo']?> <?=mti_ind_h(strtolower($p['unidad_intervalo']))?></div></td>
 <td><?php foreach($item['dates'] as $d):?><div class="mb-1"><span class="badge badge-<?=mti_ind_h($d['tone'])?>"><?=date('d/m',strtotime($d['fecha_programada']))?> · <?=mti_ind_h($d['visual'])?></span><?php if($d['orden_id']):?> <a class="small" href="detalle.php?id=<?=(int)$d['orden_id']?>">Ver OT</a><?php else:?> <span class="small text-muted">Sin OT</span><?php endif;?></div><?php endforeach;?><?php if(!$item['total']):?><span class="text-muted small">Sin fechas programadas en este año. Revisar vigencia y generación de ocurrencias en Planes.</span><?php endif;?></td>
 <td class="text-nowrap"><strong><?=$item['total']?number_format($pct,1).'%':'Sin programación'?></strong><div class="small"><?=$item['done']?> / <?=$item['total']?> completados</div><div class="small text-danger"><?=$item['overdue']?> vencidos</div><div class="small text-muted"><?=$item['future']?> pendientes en fecha</div></td></tr>
 <?php endforeach;?><?php if(!$planMap):?><tr><td colspan="4" class="text-center text-muted py-4">No hay planes vigentes para este año. <a href="planes.php">Ver planes</a></td></tr><?php endif;?></tbody></table></div></div></div><div class="col-lg-4 mt-3 mt-lg-0"><div class="ind-section h-100"><div class="ind-section-title"><i class="fas fa-gauge-high mr-2 text-warning"></i>Control operativo</div><div class="p-3"><div class="d-flex justify-content-between border-bottom py-2"><span>Fechas de mantenimiento vencidas</span><strong class="text-danger"><?=$overdue?></strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>Hallazgos abiertos</span><strong><?= (int)($findings['abiertos']??0) ?></strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>Críticos abiertos</span><strong class="text-danger"><?= (int)($findings['criticos_abiertos']??0) ?></strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>Acciones ejecutadas</span><strong><?=number_format($actionPct,1)?>%</strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>Acciones vencidas</span><strong class="text-danger"><?= (int)($actions['vencidas']??0) ?></strong></div><div class="d-flex justify-content-between py-2"><span>Promedio cierre hallazgo</span><strong><?=number_format((float)($findings['dias_cierre']??0),1)?> días</strong></div><div class="alert ind-note mt-3 mb-0 small"><strong>Lectura:</strong> avance anual = fechas completadas / fechas programadas no canceladas del año. Incluye ocurrencias del plan sin OT y órdenes independientes, sin duplicarlas. Las fechas futuras forman parte del avance, pero no son vencidas.</div></div></div></div></div>
 <div class="row"><div class="col-lg-6 mb-3"><div class="ind-section"><div class="ind-section-title"><i class="fas fa-hourglass-end mr-2 text-danger"></i>Órdenes vencidas del periodo</div><div class="table-responsive"><table class="table table-sm table-hover ind-table mb-0"><thead><tr><th>Orden</th><th>Equipo</th><th>Fecha</th><th>Prioridad</th><th></th></tr></thead><tbody><?php foreach($overdueRows as $row):?><tr><td><?=mti_ind_h($row['folio'])?></td><td><?=mti_ind_h($row['Host'])?></td><td><?=date('d/m/Y',strtotime($row['fecha_programada']))?></td><td><?=mti_ind_h($row['prioridad'])?></td><td><a href="detalle.php?id=<?=(int)$row['id']?>" class="btn btn-xs btn-outline-info">Ver</a></td></tr><?php endforeach;?><?php if(!$overdueRows):?><tr><td colspan="5" class="text-center text-muted py-4">Sin órdenes vencidas.</td></tr><?php endif;?></tbody></table></div></div></div><div class="col-lg-6 mb-3"><div class="ind-section"><div class="ind-section-title"><i class="fas fa-triangle-exclamation mr-2 text-warning"></i>Todos los hallazgos del periodo</div><div class="table-responsive"><table class="table table-sm table-hover ind-table mb-0"><thead><tr><th>Código</th><th>Equipo</th><th>Hallazgo</th><th>Criticidad</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($criticalRows as $row): $closed=$row['estado']==='CERRADO';$critTone=$row['criticidad']==='CRITICA'?'dark':($row['criticidad']==='ALTA'?'danger':($row['criticidad']==='MEDIA'?'warning':'info'));?><tr><td><?=mti_ind_h($row['codigo'])?></td><td><?=mti_ind_h($row['Host'])?></td><td><?=mti_ind_h($row['titulo'])?></td><td><span class="badge badge-<?=$critTone?>"><?=mti_ind_h($row['criticidad'])?></span></td><td><span class="badge badge-<?=$closed?'success':'warning'?>"><?=mti_ind_h(str_replace('_',' ',$row['estado']))?></span></td><td><a href="hallazgo.php?id=<?=(int)$row['id']?>" class="btn btn-xs btn-outline-info">Ver</a></td></tr><?php endforeach;?><?php if(!$criticalRows):?><tr><td colspan="6" class="text-center text-muted py-4">Sin hallazgos registrados en el periodo.</td></tr><?php endif;?></tbody></table></div></div></div></div>
</div></section>
<div class="modal fade mti-report-modal" id="modalReporteCumplimiento" tabindex="-1" role="dialog" aria-labelledby="tituloReporteCumplimiento" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-header"><div><div class="small text-uppercase font-weight-bold opacity-75">Control anual preventivo</div><h5 class="modal-title font-weight-bold" id="tituloReporteCumplimiento"><i class="fas fa-clipboard-check mr-2"></i>Reporte de cumplimiento</h5><div class="small">Programados, ejecutados, pendientes y futuros sin alterar la operación.</div></div><button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><div id="mtiReportLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x text-info"></i><div class="mt-2">Preparando reporte...</div></div><div id="mtiReportContent" class="d-none"><div class="mti-report-filter mb-3"><div class="form-row"><div class="col-md-3 mb-2 mb-md-0"><label class="small font-weight-bold">Año</label><select id="mtiRepYear" class="form-control"></select></div><div class="col-md-3 mb-2 mb-md-0"><label class="small font-weight-bold">Tipo de equipo</label><select id="mtiRepType" class="form-control"><option value="">Todos</option></select></div><div class="col-md-3 mb-2 mb-md-0"><label class="small font-weight-bold">Frecuencia</label><select id="mtiRepFrequency" class="form-control"><option value="">Todas</option></select></div><div class="col-md-3"><label class="small font-weight-bold">Sede o ubicación</label><select id="mtiRepLocation" class="form-control"><option value="">Todas</option></select></div></div></div><div class="row mb-3" id="mtiRepKpis"></div><div class="ind-section mb-3"><div class="ind-section-title d-flex justify-content-between"><span><i class="fas fa-layer-group mr-2 text-info"></i>Resumen por tipo de equipo</span><small id="mtiRepScope" class="text-muted"></small></div><div class="table-responsive"><table class="table table-sm table-hover mti-report-table mti-report-summary mb-0"><thead><tr><th>Tipo de equipo</th><th>Programados</th><th>Ejecutados</th><th>Pendientes</th><th>Futuros</th><th>Cumplimiento</th><th>Avance anual</th></tr></thead><tbody id="mtiRepSummary"></tbody></table></div></div><div class="ind-section"><div class="ind-section-title"><i class="fas fa-list-check mr-2 text-warning"></i>Detalle de ocurrencias preventivas</div><div class="table-responsive" style="max-height:43vh"><table class="table table-sm table-bordered table-hover mti-report-table mb-0"><thead><tr><th>Año</th><th>Tipo</th><th>Equipo</th><th>Ubicación</th><th>Ocurrencia</th><th>Programada</th><th>Ejecutada</th><th>OT</th><th>Técnico</th><th>Estado</th><th>Observaciones</th></tr></thead><tbody id="mtiRepDetail"></tbody></table></div></div></div><div id="mtiReportError" class="alert alert-danger d-none"></div></div><div class="modal-footer"><span class="mr-auto small text-muted">Cumplimiento = ejecutados / (ejecutados + pendientes vencidos).</span><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div></div></div></div>
<script>
document.addEventListener('DOMContentLoaded', function(){
 if(!window.jQuery){console.error('Reporte de cumplimiento: jQuery no está disponible.');return;}
 (function($){
 var allRows=[], loadedYear=null, initialYear=<?=$year?>;
 function esc(v){return $('<div>').text(v==null?'':String(v)).html();}
 function dateEs(v){if(!v)return '-';var p=String(v).substring(0,10).split('-');return p.length===3?p[2]+'/'+p[1]+'/'+p[0]:esc(v);}
 function pct(n,d){return d>0?(n*100/d):0;}
 function renderQuarterly(rows){var q=rows.filter(function(r){return String(r.frecuencia||'').toUpperCase().indexOf('CUATRIMESTRAL')!==-1;}),rounds={1:{total:0,done:0,pending:0,future:0},2:{total:0,done:0,pending:0,future:0},3:{total:0,done:0,pending:0,future:0}};q.forEach(function(r){var n=parseInt(r.numero_ocurrencia,10);if(!rounds[n])return;rounds[n].total++;if(r.estado_reporte==='COMPLETADO')rounds[n].done++;else if(r.estado_reporte==='PENDIENTE')rounds[n].pending++;else rounds[n].future++;});var names={1:'1.er mantenimiento',2:'2.º mantenimiento',3:'3.er mantenimiento'},html=[1,2,3].map(function(n){var g=rounds[n],p=pct(g.done,g.total);return '<tr><td><span class="mti-round-name">'+names[n]+'</span></td><td><div class="mti-round-pct">'+p.toFixed(1)+'%</div><div class="mti-round-bar"><span style="width:'+Math.min(100,p)+'%"></span></div></td><td><strong>'+g.done+' / '+g.total+'</strong></td><td class="text-success"><strong>'+g.done+'</strong> realizadas</td><td class="text-danger"><strong>'+g.pending+'</strong> pendientes</td><td class="text-info"><strong>'+g.future+'</strong> por atender</td></tr>';}).join('');var block=$('#mtiRepQuarterlyBlock');if(!block.length){$('#mtiRepKpis').after('<div class="ind-section mb-3" id="mtiRepQuarterlyBlock"><div class="ind-section-title d-flex justify-content-between align-items-center"><span><i class="fas fa-repeat mr-2 text-warning"></i>Cumplimiento por intervención cuatrimestral</span><small class="text-muted">Tres mantenimientos por equipo durante el año</small></div><div class="table-responsive"><table class="table table-hover mti-report-table mti-round-table mb-0"><thead><tr><th>Intervención</th><th>Cumplimiento</th><th>Realizadas / programadas</th><th>Realizadas</th><th>Pendientes vencidas</th><th>Por atender</th></tr></thead><tbody id="mtiRepQuarterly"></tbody></table></div></div>');block=$('#mtiRepQuarterlyBlock');}block.toggle(q.length>0);$('#mtiRepQuarterly').html(html);}
 function fillSelect(id,items,label){var el=$(id).empty().append($('<option>',{value:'',text:label}));items.forEach(function(v){el.append($('<option>',{value:v,text:v}));});}
 function filteredRows(){var type=$('#mtiRepType').val(),freq=$('#mtiRepFrequency').val(),loc=$('#mtiRepLocation').val(),rows=allRows.filter(function(r){return(!type||r.Tipo_Activo===type)&&(!freq||r.frecuencia===freq)&&(!loc||(r.ubicacion||'Sin ubicación')===loc);});renderQuarterly(rows);return rows;}
 function render(){var rows=filteredRows(),done=0,pending=0,future=0,groups={};rows.forEach(function(r){var s=r.estado_reporte;done+=s==='COMPLETADO';pending+=s==='PENDIENTE';future+=s==='FUTURO';var k=r.Tipo_Activo||'Sin tipo';if(!groups[k])groups[k]={total:0,done:0,pending:0,future:0};groups[k].total++;groups[k][s==='COMPLETADO'?'done':s==='PENDIENTE'?'pending':'future']++;});var exigible=done+pending,compliance=pct(done,exigible),annual=pct(done,rows.length);var kpis=[['Programados',rows.length,'fa-calendar-days','info'],['Ejecutados',done,'fa-circle-check','success'],['Pendientes vencidos',pending,'fa-clock','danger'],['Futuros',future,'fa-forward','primary'],['Cumplimiento',compliance.toFixed(1)+'%','fa-bullseye','warning'],['Avance anual',annual.toFixed(1)+'%','fa-chart-line','secondary']];$('#mtiRepKpis').html(kpis.map(function(k){return '<div class="col-6 col-md-4 col-xl-2 mb-2"><div class="mti-report-kpi"><i class="fas '+k[2]+' text-'+k[3]+' float-right"></i><strong>'+esc(k[1])+'</strong><small>'+esc(k[0])+'</small></div></div>';}).join(''));var summary=Object.keys(groups).sort().map(function(k){var g=groups[k],c=pct(g.done,g.done+g.pending),a=pct(g.done,g.total);return '<tr><td><strong>'+esc(k)+'</strong></td><td>'+g.total+'</td><td class="text-success font-weight-bold">'+g.done+'</td><td class="text-danger font-weight-bold">'+g.pending+'</td><td>'+g.future+'</td><td><strong>'+c.toFixed(1)+'%</strong></td><td>'+a.toFixed(1)+'%</td></tr>';}).join('');$('#mtiRepSummary').html(summary||'<tr><td colspan="7" class="text-center text-muted py-4">No existen ocurrencias con estos filtros.</td></tr>');$('#mtiRepDetail').html(rows.map(function(r){var ot=r.orden_id?'<a href="detalle.php?id='+parseInt(r.orden_id,10)+'" class="font-weight-bold">'+esc(r.orden_folio||('#'+r.orden_id))+'</a>':'-';return '<tr><td>'+loadedYear+'</td><td>'+esc(r.Tipo_Activo||'Sin tipo')+'<div class="small text-muted">'+esc(r.frecuencia||'-')+'</div></td><td><strong>'+esc(r.Host)+'</strong><div class="small text-muted">'+esc(r.plan_codigo||'')+'</div></td><td>'+esc(r.ubicacion||'Sin ubicación')+'</td><td class="text-center"><strong>#'+parseInt(r.numero_ocurrencia,10)+'</strong><div class="small text-muted">'+esc(r.ocurrencia_codigo||'')+'</div></td><td>'+dateEs(r.fecha_programada)+'</td><td>'+dateEs(r.fecha_ejecucion)+'</td><td>'+ot+'</td><td>'+esc(r.tecnico_responsable||'Sin asignar')+'</td><td><span class="mti-state mti-state-'+esc(r.estado_reporte)+'">'+esc(r.estado_reporte)+'</span></td><td style="min-width:220px">'+esc(r.observaciones||'-')+'</td></tr>';}).join('')||'<tr><td colspan="11" class="text-center text-muted py-4">No existen ocurrencias con estos filtros.</td></tr>');$('#mtiRepScope').text(rows.length+' ocurrencia(s) visible(s)');}
 function load(year){loadedYear=parseInt(year,10);$('#mtiReportLoading').removeClass('d-none');$('#mtiReportContent,#mtiReportError').addClass('d-none');$.getJSON('reporte_cumplimiento_data.php',{anio:loadedYear}).done(function(res){if(!res.ok)throw new Error(res.message||'No se pudo cargar.');allRows=res.rows||[];fillSelect('#mtiRepType',res.filters.types||[],'Todos');fillSelect('#mtiRepFrequency',res.filters.frequencies||[],'Todas');fillSelect('#mtiRepLocation',res.filters.locations||[],'Todas');render();$('#mtiReportContent').removeClass('d-none');}).fail(function(xhr){var msg=(xhr.responseJSON&&xhr.responseJSON.message)||'No se pudo cargar el reporte.';$('#mtiReportError').text(msg).removeClass('d-none');}).always(function(){$('#mtiReportLoading').addClass('d-none');});}
 function exportExcel(){var params=new URLSearchParams({anio:String($('#mtiRepYear').val()||loadedYear||initialYear)}),type=$('#mtiRepType').val(),frequency=$('#mtiRepFrequency').val(),location=$('#mtiRepLocation').val();if(type)params.set('tipo',type);if(frequency)params.set('frecuencia',frequency);if(location)params.set('ubicacion',location);window.location.href='exportar_cumplimiento.php?'+params.toString();}
 $(function(){var y=$('#mtiRepYear');for(var i=new Date().getFullYear()+1;i>=2023;i--)y.append($('<option>',{value:i,text:i,selected:i===initialYear}));$('#modalReporteCumplimiento .modal-footer').prepend('<button type="button" id="mtiRepExcel" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i>Exportar Excel</button>');$('#btnReporteCumplimiento').on('click',function(){$('#modalReporteCumplimiento').modal('show');if(loadedYear===null)load(initialYear);});y.on('change',function(){load(this.value);});$('#mtiRepType,#mtiRepFrequency,#mtiRepLocation').on('change',render);$(document).on('click','#mtiRepExcel',exportExcel);});
 })(window.jQuery);
});
</script>
<?php include_once(__DIR__ . '/../../layouts/footer.php');?>
