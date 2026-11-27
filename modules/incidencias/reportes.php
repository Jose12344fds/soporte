<?php
$page_title = 'Indicadores de Mesa de Ayuda';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';

inc_schema();
require_permiso('incidencias', 'ver');

$scope = inc_ticket_scope_sql('t');
$currentYear = (int)date('Y');
$year = (int)($_GET['anio'] ?? $currentYear);
if ($year < 2020 || $year > $currentYear + 1) $year = $currentYear;
$yearRows = find_by_sql("SELECT DISTINCT YEAR(t.FechaRegistro) Anio FROM inc_tickets t WHERE ".inc_ticket_scope_sql('t')." UNION SELECT {$currentYear} Anio ORDER BY Anio DESC");

$summaryRows = find_by_sql(
    "SELECT COUNT(*) Total,
            SUM(t.Estado IN ('NUEVO','ASIGNADO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO')) Abiertos,
            SUM(t.Estado IN ('RESUELTO','CERRADO')) Finalizados,
            AVG(CASE WHEN t.FechaPrimeraRespuesta IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.FechaRegistro,t.FechaPrimeraRespuesta) END) PromedioRespuesta,
            AVG(CASE WHEN t.FechaResolucion IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.FechaRegistro,t.FechaResolucion) END) PromedioResolucion,
            AVG(t.Satisfaccion) Satisfaccion,
            SUM(t.Reaperturas) Reaperturas
     FROM inc_tickets t WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}"
);
$summary = $summaryRows[0] ?? [];
$total = (int)($summary['Total'] ?? 0);
$finalized = (int)($summary['Finalizados'] ?? 0);

$createdRows = find_by_sql(
    "SELECT MONTH(t.FechaRegistro) Mes,COUNT(*) Total,
            SUM(t.Tipo='INCIDENTE') Incidentes,SUM(t.Tipo='SOLICITUD') Solicitudes,SUM(t.Tipo='EVENTO') Eventos,
            SUM(t.Estado IN ('RESUELTO','CERRADO') AND t.FechaResolucion IS NOT NULL
                AND YEAR(t.FechaResolucion)=YEAR(t.FechaRegistro)
                AND MONTH(t.FechaResolucion)=MONTH(t.FechaRegistro)) Resueltos
     FROM inc_tickets t WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
     GROUP BY MONTH(t.FechaRegistro)"
);
$months = [];
for ($month=1;$month<=12;$month++) $months[$month] = ['Total'=>0,'Incidentes'=>0,'Solicitudes'=>0,'Eventos'=>0,'Resueltos'=>0];
foreach ($createdRows as $row) foreach (['Total','Incidentes','Solicitudes','Eventos','Resueltos'] as $key) $months[(int)$row['Mes']][$key] = (int)$row[$key];

$categoryRows = find_by_sql(
    "SELECT COALESCE(c.Nombre,'Sin categoria') Nombre,COUNT(*) Total
     FROM inc_tickets t LEFT JOIN inc_categorias c ON c.IdCategoria=t.IdCategoria
     WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
     GROUP BY t.IdCategoria,c.Nombre ORDER BY Total DESC LIMIT 8"
);
$agentRows = find_by_sql(
    "SELECT COALESCE(NULLIF(t.AsignadoNombre,''),'Sin asignar') Nombre,COUNT(*) Total,
            SUM(t.Estado IN ('RESUELTO','CERRADO')) Finalizados,
            AVG(CASE WHEN t.FechaResolucion IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.FechaRegistro,t.FechaResolucion) END) PromedioMinutos
     FROM inc_tickets t WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
     GROUP BY t.AsignadoA,t.AsignadoNombre ORDER BY Total DESC"
);
$priorityRows = find_by_sql(
    "SELECT t.Prioridad,COUNT(*) Total FROM inc_tickets t
     WHERE {$scope} AND YEAR(t.FechaRegistro)={$year} GROUP BY t.Prioridad ORDER BY FIELD(t.Prioridad,'CRITICA','ALTA','MEDIA','BAJA')"
);
$agingRows = find_by_sql(
    "SELECT
        SUM(TIMESTAMPDIFF(HOUR,t.FechaRegistro,NOW())<24) Menos24,
        SUM(TIMESTAMPDIFF(HOUR,t.FechaRegistro,NOW()) BETWEEN 24 AND 71) Dias1a3,
        SUM(TIMESTAMPDIFF(HOUR,t.FechaRegistro,NOW()) BETWEEN 72 AND 167) Dias3a7,
        SUM(TIMESTAMPDIFF(HOUR,t.FechaRegistro,NOW())>=168) Mas7
     FROM inc_tickets t WHERE {$scope} AND t.Estado IN ('NUEVO','ASIGNADO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO') AND YEAR(t.FechaRegistro)<={$year}"
);
$aging = $agingRows[0] ?? ['Menos24'=>0,'Dias1a3'=>0,'Dias3a7'=>0,'Mas7'=>0];

$monthNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$createdData = $resolvedData = [];
foreach ($months as $row) {
    $createdData[] = $row['Total'];
    $resolvedData[] = $row['Resueltos'];
}
$categoryLabels = array_column($categoryRows, 'Nombre');
$categoryData = array_map('intval', array_column($categoryRows, 'Total'));
$priorityMap = ['CRITICA'=>0,'ALTA'=>0,'MEDIA'=>0,'BAJA'=>0];
foreach ($priorityRows as $row) $priorityMap[$row['Prioridad']] = (int)$row['Total'];

$extraCss = [BASE_URL.'/assets/css/incidencias.css'];
$extraJs = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js'];
include_once __DIR__.'/../../layouts/header.php';
?>

<section class="content inc-page"><div class="container-fluid pt-3 pb-4">
    <div class="inc-hero mb-3"><div class="d-lg-flex justify-content-between align-items-center"><div><div class="inc-hero-kicker">Control y mejora continua</div><h1><i class="fas fa-chart-line mr-2"></i>Indicadores de servicio <?= $year ?></h1><p>Demanda, oportunidad, cumplimiento y experiencia del usuario en una sola vista.</p></div><div class="inc-hero-actions mt-3 mt-lg-0"><a class="btn btn-success mr-2" href="exportar_bitacora.php?anio=<?= $year ?>"><i class="fas fa-file-excel mr-1"></i>Descargar Excel T-SI-F-13</a><a class="btn btn-outline-light" href="index.php"><i class="fas fa-arrow-left mr-1"></i>Bandeja</a></div></div></div>

    <form class="inc-toolbar mb-3" method="get"><div class="row align-items-end"><div class="col-md-3"><label class="inc-label">Año de análisis</label><select class="form-control inc-control" name="anio"><?php foreach ($yearRows as $row): ?><option value="<?= (int)$row['Anio'] ?>" <?= $year===(int)$row['Anio']?'selected':'' ?>><?= (int)$row['Anio'] ?></option><?php endforeach; ?></select></div><div class="col-md-3 mt-2 mt-md-0"><button class="btn btn-info btn-block"><i class="fas fa-sync-alt mr-1"></i>Actualizar reporte</button></div><div class="col-md-6 mt-2 mt-md-0 text-md-right"><span class="inc-help"><i class="fas fa-info-circle mr-1"></i>Los resueltos permanecen en el mes donde fueron creados y solo cuentan si se cerraron dentro de ese mismo mes.</span></div></div></form>

    <div class="row mb-3">
        <div class="col-sm-6 col-xl mb-3 mb-xl-0"><div class="inc-report-card"><span class="inc-label">Demanda anual</span><strong><?= $total ?></strong><p class="text-muted mb-0">tickets registrados</p></div></div>
        <div class="col-sm-6 col-xl mb-3 mb-xl-0"><div class="inc-report-card"><span class="inc-label">Backlog abierto</span><strong class="text-primary"><?= (int)($summary['Abiertos'] ?? 0) ?></strong><p class="text-muted mb-0">casos por finalizar</p></div></div>
        <div class="col-sm-6 col-xl mb-3 mb-xl-0"><div class="inc-report-card"><span class="inc-label">Tickets finalizados</span><strong class="text-success"><?= $finalized ?></strong><p class="text-muted mb-0">resueltos o cerrados</p></div></div>
        <div class="col-sm-6 col-xl mb-3 mb-xl-0"><div class="inc-report-card"><span class="inc-label">Resolución promedio</span><strong><?= inc_duration($summary['PromedioResolucion'] ?? null) ?></strong><p class="text-muted mb-0">desde el registro</p></div></div>
        <div class="col-sm-6 col-xl"><div class="inc-report-card"><span class="inc-label">Satisfacción</span><strong class="text-warning"><?= $summary['Satisfaccion']!==null?number_format((float)$summary['Satisfaccion'],1):'-' ?></strong><p class="text-muted mb-0">sobre 5 puntos</p></div></div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-8 mb-3 mb-xl-0"><div class="card h-100"><div class="inc-table-head"><div><h2><i class="fas fa-chart-bar mr-2 text-info"></i>Tickets creados y resueltos en el mismo mes</h2><small class="text-muted">Cada ticket permanece en su mes de creación; no se traslada al mes de cierre.</small></div></div><div class="inc-chart-wrap"><canvas id="flowChart"></canvas></div></div></div>
        <div class="col-xl-4"><div class="card h-100"><div class="inc-table-head"><div><h2><i class="fas fa-layer-group mr-2 text-info"></i>Demanda por categoría</h2><small class="text-muted">Principales motivos de contacto.</small></div></div><div class="inc-chart-wrap"><canvas id="categoryChart"></canvas></div></div></div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-5 mb-3 mb-lg-0"><div class="card h-100"><div class="inc-table-head"><div><h2><i class="fas fa-exclamation-circle mr-2 text-info"></i>Prioridad y antigüedad</h2><small class="text-muted">Riesgo operativo y edad del backlog actual.</small></div></div><div class="card-body"><div class="row"><div class="col-md-6"><canvas id="priorityChart"></canvas></div><div class="col-md-6"><span class="inc-label">Edad de tickets abiertos</span><div class="d-flex justify-content-between border-bottom py-2"><span>Menos de 24 h</span><strong><?= (int)$aging['Menos24'] ?></strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>1 a 3 días</span><strong><?= (int)$aging['Dias1a3'] ?></strong></div><div class="d-flex justify-content-between border-bottom py-2"><span>3 a 7 días</span><strong><?= (int)$aging['Dias3a7'] ?></strong></div><div class="d-flex justify-content-between py-2"><span>Más de 7 días</span><strong class="text-danger"><?= (int)$aging['Mas7'] ?></strong></div><hr><span class="inc-label">Primera respuesta promedio</span><strong class="h5"><?= inc_duration($summary['PromedioRespuesta'] ?? null) ?></strong><span class="inc-subtext"><?= (int)($summary['Reaperturas'] ?? 0) ?> reapertura(s) en el año</span></div></div></div></div></div>
        <div class="col-lg-7"><div class="card h-100"><div class="inc-table-head"><div><h2><i class="fas fa-users-cog mr-2 text-info"></i>Carga por responsable</h2><small class="text-muted">Volumen asignado y velocidad media de resolución.</small></div></div><div class="table-responsive"><table class="table inc-table mb-0"><thead><tr><th>Responsable</th><th>Asignados</th><th>Finalizados</th><th>Tasa cierre</th><th>Tiempo medio</th></tr></thead><tbody><?php foreach ($agentRows as $agent): $rate=(int)$agent['Total']>0?round((int)$agent['Finalizados']/(int)$agent['Total']*100,1):0; ?><tr><td><strong><?= inc_h($agent['Nombre']) ?></strong></td><td><?= (int)$agent['Total'] ?></td><td><?= (int)$agent['Finalizados'] ?></td><td><span class="inc-badge inc-status-RESUELTO"><?= number_format($rate,1) ?>%</span></td><td><?= inc_duration($agent['PromedioMinutos']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    </div>

    <div class="card inc-table-card"><div class="inc-table-head"><div><h2><i class="fas fa-calendar-alt mr-2 text-info"></i>Resumen mensual verificable</h2><small class="text-muted">Tickets creados por mes y cuántos de ellos fueron resueltos dentro de ese mismo mes.</small></div></div><div class="table-responsive"><table class="table inc-table mb-0"><thead><tr><th>Mes</th><th>Tickets creados</th><th>Incidentes</th><th>Solicitudes</th><th>Eventos</th><th>Resueltos en el mismo mes</th></tr></thead><tbody><?php foreach ($months as $number=>$row): ?><tr><td><strong><?= inc_h($monthNames[$number-1]) ?></strong></td><td><?= $row['Total'] ?></td><td><?= $row['Incidentes'] ?></td><td><?= $row['Solicitudes'] ?></td><td><?= $row['Eventos'] ?></td><td><strong class="text-success"><?= $row['Resueltos'] ?></strong></td></tr><?php endforeach; ?></tbody></table></div></div>
</div></section>

<?php
$chartPayload = json_encode([
    'labels'=>$monthNames,'created'=>$createdData,'resolved'=>$resolvedData,
    'categoryLabels'=>$categoryLabels,'categoryData'=>$categoryData,'priority'=>array_values($priorityMap),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$pageScripts = <<<JS
$(function () {
    var d={$chartPayload};
    Chart.defaults.font.family='Source Sans Pro'; Chart.defaults.color='#5f737c';
    new Chart(document.getElementById('flowChart'),{type:'bar',data:{labels:d.labels,datasets:[
        {label:'Tickets creados',data:d.created,backgroundColor:'#edae37',borderRadius:7},
        {label:'Resueltos en el mismo mes',data:d.resolved,backgroundColor:'#168b72',borderRadius:7}
    ]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#e8eff0'}}},plugins:{legend:{position:'bottom'}}}});
    new Chart(document.getElementById('categoryChart'),{type:'bar',data:{labels:d.categoryLabels,datasets:[{data:d.categoryData,backgroundColor:['#0f8997','#3376c4','#edae37','#278a62','#ed6a5a','#7455a3','#667b8c','#17a7a0'],borderRadius:7}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0},grid:{color:'#e8eff0'}},y:{grid:{display:false}}}}});
    new Chart(document.getElementById('priorityChart'),{type:'doughnut',data:{labels:['Crítica','Alta','Media','Baja'],datasets:[{data:d.priority,backgroundColor:['#d9485d','#ed7c4b','#edae37','#278a62'],borderWidth:0}]},options:{responsive:true,cutout:'66%',plugins:{legend:{position:'bottom',labels:{boxWidth:10}}}}});
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
