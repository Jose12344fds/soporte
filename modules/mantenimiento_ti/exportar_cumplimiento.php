<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_once dirname(__DIR__, 2) . '/Reportes/_xlsx_corporativo.php';

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'ver');

function mti_xls_filter(string $name): string { return trim((string)($_GET[$name] ?? '')); }
function mti_xls_pct(int $part, int $total): float { return $total > 0 ? round($part * 100 / $total, 2) : 0.0; }
function mti_xls_pct_text(int $part, int $total): string { return number_format(mti_xls_pct($part, $total), 2) . '%'; }

$year = (int)($_GET['anio'] ?? date('Y'));
if ($year < 2020 || $year > 2100) $year = (int)date('Y');
$typeFilter = mti_xls_filter('tipo');
$frequencyFilter = mti_xls_filter('frecuencia');
$locationFilter = mti_xls_filter('ubicacion');
$start = sprintf('%04d-01-01', $year);
$end = sprintf('%04d-12-31', $year);

try {
    $machineColumns = [];
    $columnResult = $db->query('SHOW COLUMNS FROM maquina');
    while ($column = $db->fetch_assoc($columnResult)) $machineColumns[strtolower((string)$column['Field'])] = (string)$column['Field'];
    $locationColumn = '';
    foreach (['ubicacion', 'sede', 'area', 'local', 'location'] as $candidate) {
        if (isset($machineColumns[$candidate])) { $locationColumn = $machineColumns[$candidate]; break; }
    }
    $locationSql = $locationColumn !== '' ? "q.`{$locationColumn}`" : "''";
    $where = [
        "o.fecha_programada BETWEEN '{$start}' AND '{$end}'",
        "o.estado<>'CANCELADA'",
        "(m.id IS NULL OR m.estado<>'CANCELADO')",
        "UPPER(COALESCE(p.tipo,'PREVENTIVO'))='PREVENTIVO'",
    ];
    if ($typeFilter !== '') $where[] = "q.Tipo_Activo='" . $db->escape($typeFilter) . "'";
    if ($frequencyFilter !== '') $where[] = "p.frecuencia='" . $db->escape($frequencyFilter) . "'";
    if ($locationFilter !== '' && $locationColumn !== '') $where[] = "q.`{$locationColumn}`='" . $db->escape($locationFilter) . "'";

    $rows = find_by_sql(
        "SELECT o.id ocurrencia_id,o.codigo ocurrencia_codigo,o.fecha_programada,o.estado ocurrencia_estado,"
        . " o.observacion ocurrencia_observacion,p.id plan_id,p.codigo plan_codigo,p.nombre plan_nombre,"
        . " p.frecuencia,p.IdMaquina,q.Host,q.Tipo_Activo,{$locationSql} ubicacion,"
        . " m.id orden_id,m.folio orden_folio,m.estado orden_estado,m.fecha_ejecucion,"
        . " m.tecnico_responsable,m.trabajo_realizado,m.diagnostico"
        . " FROM activos_mantenimiento_ocurrencias o"
        . " INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
        . " INNER JOIN maquina q ON q.IdMaquina=p.IdMaquina"
        . " LEFT JOIN activos_mantenimientos m ON m.id=o.mantenimiento_id"
        . " WHERE " . implode(' AND ', $where)
        . " ORDER BY q.Tipo_Activo,q.Host,p.id,o.fecha_programada,o.id"
    );

    $today = date('Y-m-d');
    $byPlan = [];
    $totals = ['programados'=>0,'ejecutados'=>0,'pendientes'=>0,'futuros'=>0];
    $byType = [];
    $quarterly = [1=>['programados'=>0,'ejecutados'=>0,'pendientes'=>0,'futuros'=>0],2=>['programados'=>0,'ejecutados'=>0,'pendientes'=>0,'futuros'=>0],3=>['programados'=>0,'ejecutados'=>0,'pendientes'=>0,'futuros'=>0]];
    foreach ($rows as &$row) {
        $planId = (int)$row['plan_id'];
        $byPlan[$planId] = ($byPlan[$planId] ?? 0) + 1;
        $number = $byPlan[$planId];
        $completed = ($row['orden_estado'] ?? '') === 'COMPLETADO' || (empty($row['orden_id']) && ($row['ocurrencia_estado'] ?? '') === 'EJECUTADA');
        $status = $completed ? 'COMPLETADO' : (($row['fecha_programada'] < $today) ? 'PENDIENTE' : 'FUTURO');
        $key = $completed ? 'ejecutados' : ($status === 'PENDIENTE' ? 'pendientes' : 'futuros');
        $type = (string)($row['Tipo_Activo'] ?: 'Sin tipo');
        if (!isset($byType[$type])) $byType[$type] = ['programados'=>0,'ejecutados'=>0,'pendientes'=>0,'futuros'=>0];
        $totals['programados']++; $totals[$key]++;
        $byType[$type]['programados']++; $byType[$type][$key]++;
        if (stripos((string)$row['frecuencia'], 'CUATRIMESTRAL') !== false && isset($quarterly[$number])) {
            $quarterly[$number]['programados']++; $quarterly[$number][$key]++;
        }
        $observation = trim((string)($row['ocurrencia_observacion'] ?? ''));
        if ($observation === '') $observation = trim((string)($row['trabajo_realizado'] ?? ''));
        if ($observation === '') $observation = trim((string)($row['diagnostico'] ?? ''));
        $row = [
            'Anio'=>$year,'TipoEquipo'=>$type,'Equipo'=>$row['Host'],'Ubicacion'=>$row['ubicacion'] ?: 'Sin ubicación',
            'Frecuencia'=>$row['frecuencia'],'Plan'=>$row['plan_codigo'],'NumeroOcurrencia'=>$number,
            'CodigoOcurrencia'=>$row['ocurrencia_codigo'],'FechaProgramada'=>$row['fecha_programada'],
            'FechaEjecucion'=>$completed ? ($row['fecha_ejecucion'] ?? '') : '',
            'OT'=>$row['orden_folio'] ?: 'Sin OT','Tecnico'=>$row['tecnico_responsable'] ?: 'Sin asignar',
            'Estado'=>$status,'Observaciones'=>$observation,
        ];
    }
    unset($row);

    $exigible = $totals['ejecutados'] + $totals['pendientes'];
    $scope = ['Año: '.$year];
    if ($typeFilter !== '') $scope[] = 'Tipo: '.$typeFilter;
    if ($frequencyFilter !== '') $scope[] = 'Frecuencia: '.$frequencyFilter;
    if ($locationFilter !== '') $scope[] = 'Ubicación: '.$locationFilter;
    $summary = [
        ['Seccion'=>'KPI','Concepto'=>'Filtros aplicados','Programados'=>'','Ejecutados'=>'','Pendientes'=>'','Futuros'=>'','Cumplimiento'=>'','AvanceAnual'=>'','Detalle'=>implode(' / ',$scope)],
        ['Seccion'=>'KPI','Concepto'=>'Total general','Programados'=>$totals['programados'],'Ejecutados'=>$totals['ejecutados'],'Pendientes'=>$totals['pendientes'],'Futuros'=>$totals['futuros'],'Cumplimiento'=>mti_xls_pct_text($totals['ejecutados'],$exigible),'AvanceAnual'=>mti_xls_pct_text($totals['ejecutados'],$totals['programados']),'Detalle'=>'Cumplimiento = ejecutados / (ejecutados + pendientes vencidos)'],
    ];
    ksort($byType, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($byType as $type => $values) {
        $required = $values['ejecutados'] + $values['pendientes'];
        $summary[] = ['Seccion'=>'TIPO DE EQUIPO','Concepto'=>$type,'Programados'=>$values['programados'],'Ejecutados'=>$values['ejecutados'],'Pendientes'=>$values['pendientes'],'Futuros'=>$values['futuros'],'Cumplimiento'=>mti_xls_pct_text($values['ejecutados'],$required),'AvanceAnual'=>mti_xls_pct_text($values['ejecutados'],$values['programados']),'Detalle'=>''];
    }
    $quarterRows = [];
    $names = [1=>'1.er mantenimiento',2=>'2.º mantenimiento',3=>'3.er mantenimiento'];
    foreach ($quarterly as $number => $values) {
        $quarterRows[] = ['Intervencion'=>$names[$number],'Cumplimiento'=>mti_xls_pct_text($values['ejecutados'],$values['programados']),'RealizadasProgramadas'=>$values['ejecutados'].' / '.$values['programados'],'Realizadas'=>$values['ejecutados'],'PendientesVencidas'=>$values['pendientes'],'PorAtender'=>$values['futuros']];
    }

    $summaryCols = [
        ['key'=>'Seccion','label'=>'SECCIÓN','width'=>18],['key'=>'Concepto','label'=>'INDICADOR / TIPO DE EQUIPO','width'=>34],
        ['key'=>'Programados','label'=>'PROGRAMADOS','type'=>'number','width'=>16],['key'=>'Ejecutados','label'=>'EJECUTADOS','type'=>'number','width'=>15],
        ['key'=>'Pendientes','label'=>'PENDIENTES VENCIDOS','type'=>'number','width'=>21],['key'=>'Futuros','label'=>'FUTUROS','type'=>'number','width'=>13],
        ['key'=>'Cumplimiento','label'=>'CUMPLIMIENTO','width'=>17],['key'=>'AvanceAnual','label'=>'AVANCE ANUAL','width'=>16],['key'=>'Detalle','label'=>'CRITERIO / ALCANCE','width'=>54],
    ];
    $quarterCols = [
        ['key'=>'Intervencion','label'=>'INTERVENCIÓN','width'=>24],['key'=>'Cumplimiento','label'=>'CUMPLIMIENTO','width'=>17],
        ['key'=>'RealizadasProgramadas','label'=>'REALIZADAS / PROGRAMADAS','width'=>26],['key'=>'Realizadas','label'=>'REALIZADAS','type'=>'number','width'=>14],
        ['key'=>'PendientesVencidas','label'=>'PENDIENTES VENCIDAS','type'=>'number','width'=>22],['key'=>'PorAtender','label'=>'POR ATENDER','type'=>'number','width'=>15],
    ];
    $detailCols = [
        ['key'=>'Anio','label'=>'AÑO','type'=>'number','width'=>10],['key'=>'TipoEquipo','label'=>'TIPO DE EQUIPO','width'=>24],['key'=>'Equipo','label'=>'EQUIPO / CÓDIGO','width'=>24],
        ['key'=>'Ubicacion','label'=>'SEDE / UBICACIÓN','width'=>24],['key'=>'Frecuencia','label'=>'FRECUENCIA','width'=>17],['key'=>'Plan','label'=>'PLAN','width'=>16],
        ['key'=>'NumeroOcurrencia','label'=>'N.º OCURRENCIA','type'=>'number','width'=>16],['key'=>'CodigoOcurrencia','label'=>'CÓDIGO OCURRENCIA','width'=>22],
        ['key'=>'FechaProgramada','label'=>'FECHA PROGRAMADA','type'=>'date','width'=>20],['key'=>'FechaEjecucion','label'=>'FECHA EJECUCIÓN','type'=>'date','width'=>19],
        ['key'=>'OT','label'=>'ORDEN DE TRABAJO','width'=>20],['key'=>'Tecnico','label'=>'TÉCNICO RESPONSABLE','width'=>27],['key'=>'Estado','label'=>'ESTADO','width'=>15],['key'=>'Observaciones','label'=>'OBSERVACIONES','width'=>55],
    ];

    tx_export([
        ['name'=>'Resumen','title'=>'REPORTE DE CUMPLIMIENTO DE MANTENIMIENTO PREVENTIVO '.$year,'code'=>'T-TI-IND-01','columns'=>$summaryCols,'rows'=>$summary],
        ['name'=>'Cuatrimestral','title'=>'CUMPLIMIENTO POR INTERVENCIÓN CUATRIMESTRAL '.$year,'code'=>'T-TI-IND-01','columns'=>$quarterCols,'rows'=>$quarterRows],
        ['name'=>'Detalle','title'=>'DETALLE DE OCURRENCIAS PREVENTIVAS '.$year,'code'=>'T-TI-IND-01','columns'=>$detailCols,'rows'=>$rows],
    ], 'Reporte_Cumplimiento_Mantenimiento_TI_'.$year.'_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__,2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo 'No se pudo generar el Excel: ' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
}
