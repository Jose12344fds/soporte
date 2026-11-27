<?php
$reportOutputBuffer = ob_get_level();
ob_start();

$page_title = 'Reporte de Gestión de Capacidad TI';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/importar_logs_lib.php';
require_once __DIR__.'/../incidencias/bootstrap.php';
require_once __DIR__.'/../../Reportes/_xlsx_corporativo.php';

inc_schema();
capacidad_ti_schema();
cap_log_schema();
require_permiso('capacidad_ti', 'ver');

function cap_report_xml($value): string {
    $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string)$value);
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function cap_report_column_name(int $index): string {
    $name = '';
    do {
        $name = chr(65 + ($index % 26)).$name;
        $index = intdiv($index, 26) - 1;
    } while ($index >= 0);
    return $name;
}

function cap_report_status_style($value): int {
    $value = strtoupper(trim((string)$value));
    if (in_array($value, ['CRITICO','CRÍTICO','ROJO','VENCIDO','ALTA'], true)) return 6;
    if (in_array($value, ['ADVERTENCIA','AMARILLO','ABIERTA','MEDIA'], true)) return 7;
    if (in_array($value, ['NORMAL','ATENDIDA','CERRADA','ACTIVO','ACTIVA','BAJA'], true)) return 8;
    if (in_array($value, ['SIN MEDICION','SIN MEDICIÓN','SIN VINCULO','SIN VÍNCULO','NO EVALUADO','BRECHA'], true)) return 9;
    return 0;
}

function cap_report_sheet(string $title, string $subtitle, array $headers, array $rows, array $numeric = [], array $percent = []): string {
    $headerRow = 4;
    $lastColumn = cap_report_column_name(max(0, count($headers) - 1));
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';
    $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="20"/><cols>';
    foreach ($headers as $index => $header) {
        $wide = ['Servicio TI','Aplicación','Componente / recurso','Hallazgo','Acción propuesta','Observaciones','Métricas requeridas','Infraestructura','Interpretación','Fuente / evidencia'];
        $width = in_array($header, $wide, true) ? 40 : min(28, max(12, mb_strlen((string)$header, 'UTF-8') + 4));
        $xml .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData><row r="1" ht="31" customHeight="1"><c r="A1" t="inlineStr" s="4"><is><t>'.cap_report_xml($title).'</t></is></c></row>';
    $xml .= '<row r="2" ht="26" customHeight="1"><c r="A2" t="inlineStr" s="5"><is><t>'.cap_report_xml($subtitle).'</t></is></c></row><row r="3" ht="8" customHeight="1"/><row r="'.$headerRow.'" ht="34" customHeight="1">';
    foreach ($headers as $index => $header) {
        $reference = cap_report_column_name($index).$headerRow;
        $xml .= '<c r="'.$reference.'" t="inlineStr" s="1"><is><t>'.cap_report_xml($header).'</t></is></c>';
    }
    $xml .= '</row>';
    foreach ($rows as $rowIndex => $row) {
        $excelRow = $headerRow + $rowIndex + 1;
        $xml .= '<row r="'.$excelRow.'">';
        foreach (array_values($row) as $columnIndex => $value) {
            $reference = cap_report_column_name($columnIndex).$excelRow;
            $isNumeric = in_array($columnIndex, $numeric, true) && $value !== null && $value !== '' && is_numeric($value);
            if ($isNumeric) {
                $style = in_array($columnIndex, $percent, true) ? 3 : 2;
                $xml .= '<c r="'.$reference.'" s="'.$style.'"><v>'.cap_report_xml($value).'</v></c>';
            } else {
                $style = cap_report_status_style($value);
                $xml .= '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.cap_report_xml($value).'</t></is></c>';
            }
        }
        $xml .= '</row>';
    }
    $lastRow = max($headerRow, $headerRow + count($rows));
    // OpenXML exige autoFilter antes de mergeCells; Excel rechaza la hoja si se invierte este orden.
    $xml .= '</sheetData><autoFilter ref="A'.$headerRow.':'.$lastColumn.$lastRow.'"/><mergeCells count="2"><mergeCell ref="A1:'.$lastColumn.'1"/><mergeCell ref="A2:'.$lastColumn.'2"/></mergeCells>';
    $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
    return $xml;
}

function cap_report_metric(string $resource): array {
    $map = ['CPU'=>['Utilización de CPU','MHz'],'RAM'=>['Utilización de memoria RAM','GB'],'DISCO'=>['Utilización de almacenamiento','GB'],'RED'=>['Utilización de red','Mbps']];
    $resource = strtoupper($resource);
    return $map[$resource] ?? ['Utilización de '.$resource,'Unidad'];
}

function cap_report_trend(array $series): array {
    if (count($series) < 2) return ['growth'=>null,'projection'=>null,'frequency'=>'Sin tendencia suficiente'];
    usort($series, fn($a,$b) => strcmp($a['FechaEvaluacion'],$b['FechaEvaluacion']));
    $first = $series[0];
    $last = $series[count($series)-1];
    $days = (strtotime($last['FechaEvaluacion']) - strtotime($first['FechaEvaluacion'])) / 86400;
    if ($days <= 0) return ['growth'=>null,'projection'=>null,'frequency'=>'Sin tendencia suficiente'];
    $growth = ((float)$last['PorcentajeUso'] - (float)$first['PorcentajeUso']) / $days * 30;
    $gap = $days / (count($series)-1);
    $frequency = $gap <= 1.5 ? 'Diaria' : ($gap <= 8 ? 'Semanal' : ($gap <= 35 ? 'Mensual' : 'Eventual'));
    return ['growth'=>round($growth,2),'projection'=>round((float)$last['PorcentajeUso'] + $growth*6,2),'frequency'=>$frequency];
}

function cap_report_action(string $status, string $resource): string {
    $status = strtoupper($status);
    if ($status === 'CRITICO') return 'Atender inmediatamente, identificar el consumidor principal y ejecutar optimización o ampliación aprobada.';
    if ($status === 'ADVERTENCIA') return 'Analizar tendencia, validar picos y programar optimización antes de alcanzar el umbral crítico.';
    if ($status === 'SIN MEDICION') return 'Registrar una línea base de '.strtoupper($resource).' y definir una frecuencia de medición verificable.';
    return 'Mantener monitoreo periódico y conservar evidencia de la revisión de capacidad.';
}

$servers = find_by_sql("SELECT h.IdHipervisor,m.IdMaquina,m.Host,m.Tipo_Activo,m.Procesador,m.Mem_Ram,m.Tamano_Disco,m.Ip,h.TipoHipervisor,h.Fabricante,h.Version,h.NucleosCPU,h.FrecuenciaCPUMHz,h.Estado FROM hipervisor h INNER JOIN maquina m ON m.IdMaquina=h.IdMaquina ORDER BY m.Host");
$vms = find_by_sql("SELECT v.*,m.Host,m.Procesador,h.TipoHipervisor,h.FrecuenciaCPUMHz FROM maquina_virtual v INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina LEFT JOIN hipervisor h ON h.IdHipervisor=v.IdHipervisor ORDER BY m.Host,v.NombreVM");
$applications = find_by_sql("SELECT * FROM aplicacion ORDER BY NombreAplicacion");
$appVmLinks = find_by_sql("SELECT av.*,v.NombreVM,v.SistemaOperativo,v.CPUAsignada,v.RAMAsignada,v.DiscoAsignado,v.EstadoVM,m.Host,m.Procesador,h.FrecuenciaCPUMHz,c.UmbralAdvertencia,c.UmbralCritico,c.Responsable ResponsableCapacidad FROM aplicacion_vm av INNER JOIN maquina_virtual v ON v.IdVM=av.IdVM INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina LEFT JOIN hipervisor h ON h.IdHipervisor=v.IdHipervisor LEFT JOIN configuracion_capacidad_vm c ON c.IdVM=v.IdVM AND c.Activa=1 WHERE av.EstadoDespliegue='ACTIVO' ORDER BY av.IdAplicacion,m.Host,v.NombreVM");
$components = find_by_sql("SELECT sc.*,a.NombreAplicacion,a.Ambiente,a.Criticidad,a.Responsable FROM software_componente sc INNER JOIN aplicacion a ON a.IdAplicacion=sc.IdAplicacion ORDER BY a.NombreAplicacion,sc.TipoComponente,sc.NombreComponente");
$serviceLinks = find_by_sql("SELECT sa.IdAplicacion,s.NombreServicio FROM servicio_aplicacion sa INNER JOIN servicio_ti s ON s.IdServicio=sa.IdServicio ORDER BY s.NombreServicio");
$measurements = find_by_sql("SELECT hc.*,v.IdMaquina,v.NombreVM,m.Host,
    CASE WHEN hc.RegistradoPor=0 AND hc.Observacion LIKE 'Importación automática%' THEN 'Importador automático' ELSE u.name END RegistradoPor
    FROM historial_capacidad hc INNER JOIN maquina_virtual v ON v.IdVM=hc.IdVM INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina
    LEFT JOIN users u ON u.id=hc.RegistradoPor ORDER BY hc.FechaEvaluacion,hc.IdHistorial");
$alerts = find_by_sql("SELECT a.*,v.NombreVM,v.IP_VM,m.Host,u.name AtendidaPorNombre,
    t.Folio,t.Estado EstadoIncidencia,t.Prioridad PrioridadIncidencia,t.Titulo TituloIncidencia
    FROM alerta_capacidad a
    INNER JOIN maquina_virtual v ON v.IdVM=a.IdVM
    INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina
    LEFT JOIN users u ON u.id=a.AtendidaPor
    LEFT JOIN inc_tickets t ON t.IdTicket=a.IdIncidencia
    ORDER BY a.FechaAlerta DESC,a.IdAlerta DESC");
$logImports = find_by_sql("SELECT i.*,v.NombreVM,v.IP_VM,m.Host
    FROM importacion_log_capacidad i
    INNER JOIN maquina_virtual v ON v.IdVM=i.IdVM
    INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina
    ORDER BY i.FechaProceso DESC,i.IdImportacion DESC");
$logSources = find_by_sql("SELECT f.*,v.NombreVM,v.IP_VM,m.Host
    FROM fuente_log_capacidad f
    INNER JOIN maquina_virtual v ON v.IdVM=f.IdVM
    INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina
    ORDER BY f.Activa DESC,f.NombreFuente");

$vmsByMachine=[]; $linksByApp=[]; $componentsByApp=[]; $servicesByApp=[]; $historyByMetric=[]; $latestByMetric=[]; $latestByVm=[]; $openAlertByMetric=[];
foreach($vms as $vm) $vmsByMachine[(int)$vm['IdMaquina']][]=$vm;
foreach($appVmLinks as $link) $linksByApp[(int)$link['IdAplicacion']][]=$link;
foreach($components as $component) $componentsByApp[(int)$component['IdAplicacion']][]=$component;
foreach($serviceLinks as $link) $servicesByApp[(int)$link['IdAplicacion']][]=$link['NombreServicio'];
foreach($measurements as $measurement){$key=(int)$measurement['IdVM'].'|'.strtoupper($measurement['Recurso']);$historyByMetric[$key][]=$measurement;$latestByMetric[$key]=$measurement;if(!isset($latestByVm[(int)$measurement['IdVM']])||$measurement['FechaEvaluacion']>$latestByVm[(int)$measurement['IdVM']])$latestByVm[(int)$measurement['IdVM']]=$measurement['FechaEvaluacion'];}
foreach($alerts as $alert){$key=(int)$alert['IdVM'].'|'.strtoupper($alert['Recurso']);if($alert['EstadoAlerta']==='ABIERTA'&&!isset($openAlertByMetric[$key]))$openAlertByMetric[$key]=$alert;}

$serverRows=[];
foreach($servers as $server){
    $serverVms=$vmsByMachine[(int)$server['IdMaquina']]??[];
    foreach(['CPU','RAM','DISCO'] as $resource){
        $assigned=0.0;$current=0.0;$measured=0;$lastDate='';
        foreach($serverVms as $vm){
            $assigned += $resource==='CPU'?(float)$vm['CPUAsignada']*(float)$server['FrecuenciaCPUMHz']:($resource==='RAM'?(float)$vm['RAMAsignada']:(float)$vm['DiscoAsignado']);
            $latest=$latestByMetric[(int)$vm['IdVM'].'|'.$resource]??null;
            if($latest){$current+=(float)$latest['ConsumoActual'];$measured++;if($latest['FechaEvaluacion']>$lastDate)$lastDate=$latest['FechaEvaluacion'];}
        }
        $capacity=$resource==='CPU'?(float)$server['NucleosCPU']*(float)$server['FrecuenciaCPUMHz']:($resource==='RAM'?(float)$server['Mem_Ram']:(float)$server['Tamano_Disco']);
        $allocation=$capacity>0?round($assigned/$capacity*100,2):null;
        $usage=$capacity>0&&$measured>0?round($current/$capacity*100,2):null;
        $status=$usage===null?'SIN MEDICION':($usage>=90?'CRITICO':($usage>=75?'ADVERTENCIA':'NORMAL'));
        $metric=cap_report_metric($resource);
        $action=$capacity<=0?'Completar la capacidad física del servidor para habilitar el cálculo.':cap_report_action($status,$resource);
        $serverRows[]=['INF-'.str_pad((string)$server['IdMaquina'],4,'0',STR_PAD_LEFT).'-'.$resource,$server['Host'],$server['TipoHipervisor'],trim($server['Fabricante'].' '.$server['Version']),$server['Procesador'],$metric[0],$metric[1],$capacity,$assigned,$allocation,$current,$usage,count($serverVms),$measured.'/'.count($serverVms),75,90,$status,$lastDate,$action,'historial_capacidad'];
    }
}

$applicationRows=[];$applicationGaps=[];
foreach($applications as $app){
    $appId=(int)$app['IdAplicacion'];$links=$linksByApp[$appId]??[];$serviceNames=implode(', ',array_unique($servicesByApp[$appId]??[]));
    if(!$links){
        $applicationRows[]=['APP-'.str_pad((string)$appId,4,'0',STR_PAD_LEFT),$serviceNames,$app['NombreAplicacion'],'Sin infraestructura vinculada',$app['Ambiente'],$app['Criticidad'],$app['Responsable'],'-','-','-','','','','',75,90,'Mayor es peor','SIN VINCULO','','','Sin periodicidad','Catálogo de aplicaciones','Vincular la aplicación con la VM o infraestructura que la soporta.','','',$app['Descripcion']];
        $applicationGaps[]=['ALTA','Cobertura de aplicación',$app['NombreAplicacion'],'La aplicación no tiene infraestructura vinculada.','No se puede demostrar su capacidad ni continuidad.','Vincular la aplicación con una VM y registrar mediciones.',$app['Responsable'],date('Y-m-d',strtotime('+30 days')),'aplicacion.IdAplicacion='.$appId,'PENDIENTE'];continue;
    }
    foreach($links as $link){
        $resources=['CPU','RAM','DISCO'];if(isset($latestByMetric[(int)$link['IdVM'].'|RED']))$resources[]='RED';
        foreach($resources as $resource){
            $key=(int)$link['IdVM'].'|'.$resource;$measurement=$latestByMetric[$key]??null;
            $warning=$link['UmbralAdvertencia']!==null?(float)$link['UmbralAdvertencia']:75.0;$critical=$link['UmbralCritico']!==null?(float)$link['UmbralCritico']:90.0;
            $trend=cap_report_trend($historyByMetric[$key]??[]);$status=$measurement?strtoupper($measurement['Estado']):'SIN MEDICION';$currentPercent=$measurement?(float)$measurement['PorcentajeUso']:null;$daysToCritical=null;
            if($currentPercent!==null&&$currentPercent>=$critical)$daysToCritical=0;elseif($trend['growth']!==null&&$trend['growth']>0&&$currentPercent<$critical)$daysToCritical=(int)ceil(($critical-$currentPercent)/($trend['growth']/30));
            $metric=cap_report_metric($resource);$alert=$openAlertByMetric[$key]??null;$evidence=$measurement?'HC-'.str_pad((string)$measurement['IdHistorial'],6,'0',STR_PAD_LEFT):'';if($alert)$evidence.=($evidence?' / ':'').'ALERTA-'.str_pad((string)$alert['IdAlerta'],5,'0',STR_PAD_LEFT);
            $applicationRows[]=['APP-'.str_pad((string)$appId,4,'0',STR_PAD_LEFT).'-'.str_pad((string)$link['IdVM'],3,'0',STR_PAD_LEFT).'-'.$resource,$serviceNames,$app['NombreAplicacion'],$link['Host'].' / '.$link['NombreVM'].' / '.$resource,$app['Ambiente'],$app['Criticidad'],$app['Responsable']?:$link['ResponsableCapacidad'],$link['Host'].' / '.$link['NombreVM'],$metric[0],$metric[1],$measurement?(float)$measurement['CapacidadTotal']:null,$measurement?(float)$measurement['ConsumoActual']:null,$currentPercent,$trend['growth'],$warning,$critical,'Mayor es peor',$status,$trend['projection'],$daysToCritical,$trend['frequency'],'Medición de la VM que soporta la aplicación',cap_report_action($status,$resource),$evidence,$measurement['FechaEvaluacion']??'',trim(($measurement['Observacion']??'').' Rendimiento de infraestructura; no equivale a una métrica transaccional propia de la aplicación.')];
            if(!$measurement)$applicationGaps[]=['MEDIA','Cobertura de medición',$app['NombreAplicacion'].' / '.$link['NombreVM'].' / '.$resource,'No existe una línea base para este recurso.','La evaluación de capacidad está incompleta.',cap_report_action('SIN MEDICION',$resource),$app['Responsable']?:$link['ResponsableCapacidad'],date('Y-m-d',strtotime('+60 days')),'aplicacion_vm.IdAplicacionVM='.$link['IdAplicacionVM'],'PENDIENTE'];
        }
    }
}

$componentRows=[];
foreach($applications as $app){
    $appId=(int)$app['IdAplicacion'];$appComponents=$componentsByApp[$appId]??[];$links=$linksByApp[$appId]??[null];
    if(!$appComponents){
        $componentRows[]=['COMP-APP-'.str_pad((string)$appId,4,'0',STR_PAD_LEFT),$app['NombreAplicacion'],'SIN COMPONENTES','No registrados','','',$app['Ambiente'],$app['Criticidad'],$app['Responsable'],implode(', ',array_unique($servicesByApp[$appId]??[])),'-','-','','','','','Sin cobertura específica','BRECHA','Registrar motor de base de datos, servidor web, runtime y demás dependencias aplicables.','','Completar el catálogo técnico de la aplicación.',$app['Descripcion'],'No existe detalle suficiente para evaluar componentes.'];
        $applicationGaps[]=['MEDIA','Inventario técnico',$app['NombreAplicacion'],'No tiene componentes técnicos registrados.','No se identifican dependencias de software o base de datos.','Registrar los componentes técnicos y sus versiones.',$app['Responsable'],date('Y-m-d',strtotime('+60 days')),'aplicacion.IdAplicacion='.$appId,'PENDIENTE'];continue;
    }
    foreach($appComponents as $component)foreach($links as $link){
        $type=strtoupper($component['TipoComponente']);$isDatabase=strpos($type,'BASE')!==false||strpos($type,'DATOS')!==false||preg_match('/MYSQL|MARIADB|SQL SERVER|HANA|ORACLE|POSTGRES/i',$component['NombreComponente']);
        $required=$isDatabase?'CPU del proceso; memoria/buffer; tamaño y crecimiento de datos; conexiones; latencia; duración de respaldo.':'CPU y memoria del proceso; disponibilidad; tiempo de respuesta; errores; crecimiento según el componente.';
        $last=$link?($latestByVm[(int)$link['IdVM']]??''):'';$cpuCapacity=$link?(float)$link['CPUAsignada']*(float)$link['FrecuenciaCPUMHz']:null;
        $componentRows[]=['COMP-'.str_pad((string)$component['IdComponente'],5,'0',STR_PAD_LEFT),$app['NombreAplicacion'],$component['TipoComponente'],$component['NombreComponente'],$component['Version'],$component['Fabricante'],$app['Ambiente'],$app['Criticidad'],$app['Responsable'],implode(', ',array_unique($servicesByApp[$appId]??[])),$link?$link['Host'].' / '.$link['NombreVM']:'-',$link['Procesador']??'',$link?(float)$link['CPUAsignada']:null,$cpuCapacity,$link?(float)$link['RAMAsignada']:null,$link?(float)$link['DiscoAsignado']:null,$link?'Monitoreo indirecto mediante capacidad de la VM':'Sin infraestructura vinculada',$link?'NO EVALUADO':'BRECHA',$required,$last,$isDatabase?'Configurar mediciones propias del motor de base de datos y conservar tendencia mensual.':'Definir métricas técnicas específicas si el componente es crítico.',$component['Observacion'],'Las métricas de VM no demuestran por sí solas el rendimiento individual del componente.'];
    }
}

$actionRows=[];
foreach($alerts as $alert)if($alert['EstadoAlerta']==='ABIERTA'){
    $priority=strtoupper($alert['NivelAlerta'])==='ROJO'?'ALTA':'MEDIA';
    $evidence='ALERTA-'.str_pad((string)$alert['IdAlerta'],5,'0',STR_PAD_LEFT);
    if(!empty($alert['Folio']))$evidence.=' / '.$alert['Folio'];
    $actionRows[]=[$priority,'Alerta abierta',$alert['Host'].' / '.$alert['NombreVM'].' / '.$alert['Recurso'],$alert['Descripcion'],'Posible degradación o falta de capacidad.',cap_report_action($priority==='ALTA'?'CRITICO':'ADVERTENCIA',$alert['Recurso']),$alert['AtendidaPorNombre']?:'Responsable de TI',date('Y-m-d',strtotime($priority==='ALTA'?'+7 days':'+30 days')),$evidence,'PENDIENTE'];
}
$actionRows=array_merge($actionRows,$applicationGaps);
$historyRows=array_map(fn($r)=>['HC-'.str_pad((string)$r['IdHistorial'],6,'0',STR_PAD_LEFT),$r['Host'],$r['NombreVM'],$r['FechaEvaluacion'],$r['Recurso'],(float)$r['CapacidadTotal'],(float)$r['ConsumoActual'],(float)$r['PorcentajeUso'],(float)$r['UmbralAlerta'],$r['Estado'],$r['RegistradoPor'],$r['Observacion']],array_reverse($measurements));
$alertRows=array_map(fn($r)=>['ALERTA-'.str_pad((string)$r['IdAlerta'],5,'0',STR_PAD_LEFT),$r['IdHistorial']?'HC-'.str_pad((string)$r['IdHistorial'],6,'0',STR_PAD_LEFT):'',$r['Host'],$r['NombreVM'],$r['IP_VM'],$r['FechaAlerta'],$r['Recurso'],(float)$r['PorcentajeDetectado'],$r['NivelAlerta'],$r['Descripcion'],$r['EstadoAlerta'],$r['FechaAtencion'],$r['ComentarioAtencion'],$r['AtendidaPorNombre'],$r['IdIncidencia'],$r['Folio'],$r['PrioridadIncidencia'],$r['EstadoIncidencia'],$r['TituloIncidencia']],$alerts);
$importRows=array_map(fn($r)=>['IMP-'.str_pad((string)$r['IdImportacion'],6,'0',STR_PAD_LEFT),$r['ArchivoOriginal'],$r['HashArchivo'],$r['Host'],$r['NombreVM'],$r['IP_VM'],$r['NombreVMArchivo'],$r['FechaMedicion'],(int)$r['CantidadMediciones'],$r['Estado'],$r['Mensaje'],$r['FechaProceso']],$logImports);
$sourceRows=array_map(fn($r)=>[(int)$r['IdFuente'],$r['NombreFuente'],$r['NombreNormalizado'],$r['IPFuente'],$r['Host'],$r['NombreVM'],$r['IP_VM'],$r['UltimaLectura'],(int)$r['Activa'],$r['created_at'],$r['updated_at']],$logSources);

$openAlerts=count(array_filter($alerts,fn($r)=>$r['EstadoAlerta']==='ABIERTA'));
$linkedAlerts=count(array_filter($alerts,fn($r)=>!empty($r['IdIncidencia'])));
$pendingIncidentAlerts=count(array_filter($alerts,fn($r)=>$r['EstadoAlerta']==='ABIERTA'&&empty($r['IdIncidencia'])));
$criticalMetrics=count(array_filter($applicationRows,fn($r)=>$r[17]==='CRITICO'));
$warningMetrics=count(array_filter($applicationRows,fn($r)=>$r[17]==='ADVERTENCIA'));
$missingMetrics=count(array_filter($applicationRows,fn($r)=>in_array($r[17],['SIN MEDICION','SIN VINCULO'],true)));
$reportUser=current_user();
$summaryRows=[
    ['Periodo del reporte',date('Y-m-d H:i'),'Estado vigente y todo el historial disponible a la fecha de generación.'],
    ['Elaborado por',$reportUser['name']??'Usuario del sistema','Usuario autenticado que generó el archivo.'],['Revisado por','PENDIENTE','Completar durante la revisión formal del reporte.'],['Aprobado por','PENDIENTE','Completar según el flujo documental del SGSI.'],
    ['Servidores físicos con hipervisor',count($servers),'Infraestructura incluida en la evaluación.'],['Máquinas virtuales',count($vms),'VM relacionadas con servidores físicos.'],['Aplicaciones',count($applications),'Aplicaciones registradas en el mapa de capacidad.'],['Componentes técnicos',count($components),'Bases de datos, plataformas, runtimes y otras dependencias registradas.'],['Mediciones históricas',count($measurements),'Registros que sustentan tendencias y valores actuales.'],['Archivos de medición procesados',count($logImports),'Evidencia del ingreso automático desde Log_Sistems.'],['Fuentes automáticas configuradas',count($logSources),'Alias e IP relacionados con máquinas virtuales.'],['Métricas críticas',$criticalMetrics,'Requieren atención inmediata.'],['Métricas en advertencia',$warningMetrics,'Requieren análisis y planificación.'],['Brechas de medición o vínculo',$missingMetrics,'No deben interpretarse como utilización 0%.'],['Alertas abiertas',$openAlerts,'Pendientes de atención y cierre documentado.'],['Alertas vinculadas a ticket',$linkedAlerts,'Tienen trazabilidad directa con Mesa de Ayuda.'],['Alertas abiertas sin ticket',$pendingIncidentAlerts,'Deben evaluarse para crear o vincular una incidencia.'],['Criterio de CPU','vCPU asignada × frecuencia MHz del host','El consumo VMware en MHz se divide entre esta capacidad para obtener el porcentaje.'],['Alcance ISO/IEC 27001:2022','Evidencia de gestión de capacidad','El libro apoya seguimiento, medición, revisión y mejora; no constituye por sí solo certificación.']
];
$methodRows=[
    ['Objeto de medición','Servidor físico','Capacidad instalada y consumo agregado de sus VM.','maquina, hipervisor, maquina_virtual, historial_capacidad'],['Objeto de medición','Aplicación','Uso de CPU, RAM, disco y red de cada VM que soporta la aplicación.','aplicacion, aplicacion_vm, historial_capacidad'],['Limitación','Rendimiento de aplicación','La métrica de VM es infraestructura de soporte; no equivale a latencia, transacciones o errores propios de la aplicación.','Debe integrarse una fuente APM para evaluación transaccional.'],['Limitación','Base de datos','Se muestran motor, versión, host y recursos asignados. El rendimiento del motor requiere métricas específicas.','Conexiones, latencia, buffer, crecimiento, bloqueos y respaldos.'],['Fórmula','Uso %','Consumo actual / capacidad total × 100.','CPU en MHz; RAM y disco en GB; red en Mbps.'],['Fórmula','Crecimiento mensual','Variación entre primera y última medición / días transcurridos × 30.','Se expresa en puntos porcentuales por mes.'],['Fórmula','Proyección 6 meses','Uso actual + crecimiento mensual × 6.','Solo se calcula con al menos dos mediciones en fechas distintas.'],['Criterio','Estado','Normal < advertencia; Advertencia >= umbral; Crítico >= umbral crítico.','Umbrales de la configuración vigente de cada VM; por defecto 75% y 90%.'],['Control','Trazabilidad','Cada resultado enlaza IDs de historial o alerta como evidencia.','Conservar el archivo junto con revisión, aprobación y acciones ejecutadas.'],['Control','Fechas del plan','Las fechas objetivo generadas son sugeridas y no constituyen compromisos aprobados.','Deben confirmarse durante la revisión del responsable.']
];

$generated='Generado el '.date('d/m/Y H:i').' | Fuente: Sistema de Soporte TI | Responsable de revisión: Área de TI';
$sheets=[
    ['Resumen ejecutivo','REPORTE EJECUTIVO DE GESTIÓN DE CAPACIDAD TI',$generated,['Indicador','Resultado','Interpretación'],$summaryRows,[1],[]],
    ['Capacidad servidores','CAPACIDAD Y UTILIZACIÓN DE SERVIDORES',$generated,['ID','Servidor','Hipervisor','Fabricante / versión','Procesador','Métrica','Unidad','Capacidad física','Capacidad asignada a VM','Asignación %','Consumo medido','Utilización %','Total VM','VM con medición','Umbral advertencia %','Umbral crítico %','Estado','Última medición','Acción propuesta','Fuente / evidencia'],$serverRows,[7,8,9,10,11,12,14,15],[9,11,14,15]],
    ['Rendimiento aplicaciones','CAPACIDAD DE INFRAESTRUCTURA POR APLICACIÓN',$generated,['ID','Servicio TI','Aplicación','Componente / recurso','Ambiente','Criticidad','Responsable','Servidor / VM','Métrica','Unidad','Capacidad total','Valor actual','Utilizado %','Crecimiento mensual','Umbral advertencia %','Umbral crítico %','Dirección','Estado','Proyección 6 meses','Días al crítico','Frecuencia','Fuente de medición','Acción propuesta','Ticket / evidencia','Última medición','Observaciones'],$applicationRows,[10,11,12,13,14,15,18,19],[12,13,14,15,18]],
    ['Componentes y BD','COMPONENTES TÉCNICOS Y BASES DE DATOS',$generated,['ID','Aplicación','Tipo de componente','Componente','Versión','Fabricante','Ambiente','Criticidad','Responsable','Servicio TI','Infraestructura','Procesador del host','vCPU asignada','Capacidad CPU MHz','RAM asignada GB','Disco asignado GB','Cobertura actual','Estado','Métricas requeridas','Última medición de infraestructura','Acción propuesta','Observaciones','Aclaración de auditoría'],$componentRows,[12,13,14,15],[]],
    ['Plan de acción','HALLAZGOS Y PLAN DE TRATAMIENTO',$generated,['Prioridad','Tipo','Objeto','Hallazgo','Riesgo','Acción propuesta','Responsable acción','Fecha objetivo sugerida','Ticket / evidencia','Estado'],$actionRows,[],[]],
    ['Historial mediciones','EVIDENCIA: HISTORIAL DE MEDICIONES',$generated,['ID evidencia','Servidor','VM','Fecha evaluación','Recurso','Capacidad total','Consumo actual','Uso %','Umbral aplicado %','Estado','Registrado por','Observaciones'],$historyRows,[5,6,7,8],[7,8]],
    ['Alertas','EVIDENCIA: ALERTAS DE CAPACIDAD Y TICKETS',$generated,['ID alerta','ID medición','Servidor','VM','IP VM','Fecha alerta','Recurso','Uso detectado %','Nivel','Descripción','Estado alerta','Fecha atención','Comentario de atención','Atendida por','ID ticket','Folio ticket','Prioridad ticket','Estado ticket','Asunto ticket'],$alertRows,[7,14],[7]],
    ['Importaciones logs','EVIDENCIA: ARCHIVOS AUTOMÁTICOS PROCESADOS',$generated,['ID importación','Archivo original','Hash archivo','Servidor','VM relacionada','IP registrada','Nombre VM del archivo','Fecha medición','Cantidad mediciones','Estado','Mensaje','Fecha proceso'],$importRows,[8],[]],
    ['Fuentes automáticas','RELACIÓN DE FUENTES DE LOG CON MÁQUINAS VIRTUALES',$generated,['ID fuente','Nombre fuente','Nombre normalizado','IP fuente','Servidor físico','VM relacionada','IP registrada VM','Última lectura','Activa','Fecha creación','Última actualización'],$sourceRows,[0,8],[]],
    ['Metodología','METODOLOGÍA, ALCANCE Y LIMITACIONES',$generated,['Categoría','Elemento','Criterio aplicado','Fuente / evidencia'],$methodRows,[],[]]
];

$corporateSheets=[];
foreach($sheets as $sheetDefinition){
    $headers=$sheetDefinition[3];
    $sourceRows=$sheetDefinition[4];
    $numericIndexes=$sheetDefinition[5];
    $columns=[];$rows=[];
    foreach($headers as $index=>$header){
        $columns[]=['key'=>'C'.($index+1),'label'=>$header,'type'=>in_array($index,$numericIndexes,true)?'number':'text','width'=>in_array($header,['Acción propuesta','Observaciones','Descripción','Hallazgo','Interpretación','Fuente / evidencia','Mensaje','Asunto ticket'],true)?48:22];
    }
    foreach($sourceRows as $sourceRow){
        $row=[];
        foreach(array_values($sourceRow) as $index=>$value)$row['C'.($index+1)]=$value;
        $rows[]=$row;
    }
    $corporateSheets[]=['name'=>$sheetDefinition[0],'title'=>$sheetDefinition[1],'code'=>'T-TI-F-02','columns'=>$columns,'rows'=>$rows];
}
tx_export($corporateSheets,'Capacidad_TI_Trazabilidad_'.date('Y-m-d_His').'.xlsx',dirname(__DIR__,2).'/assets/img/logo.png');

$temporaryFile=tempnam(sys_get_temp_dir(),'capacidad_ti_');$zip=new ZipArchive();
if($temporaryFile===false||$zip->open($temporaryFile,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('No se pudo crear temporalmente el reporte Excel.');
$contentTypes='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
$workbook='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
$relationships='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
foreach($sheets as $index=>$sheet){$number=$index+1;$contentTypes.='<Override PartName="/xl/worksheets/sheet'.$number.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';$workbook.='<sheet name="'.cap_report_xml($sheet[0]).'" sheetId="'.$number.'" r:id="rId'.$number.'"/>';$relationships.='<Relationship Id="rId'.$number.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$number.'.xml"/>';$zip->addFromString('xl/worksheets/sheet'.$number.'.xml',cap_report_sheet($sheet[1],$sheet[2],$sheet[3],$sheet[4],$sheet[5],$sheet[6]));}
$styleId=count($sheets)+1;$contentTypes.='</Types>';$workbook.='</sheets></workbook>';$relationships.='<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
$styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="0.00&amp;quot;%&amp;quot;"/></numFmts><fonts count="4"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font><font><b/><sz val="17"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><i/><sz val="10"/><color rgb="FF4F6870"/><name val="Aptos"/></font></fonts><fills count="8"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF123746"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF087F8C"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFDDE1"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF1CC"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDDF3E4"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8ECEE"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FFDCE7E7"/></left><right style="thin"><color rgb="FFDCE7E7"/></right><top style="thin"><color rgb="FFDCE7E7"/></top><bottom style="thin"><color rgb="FFDCE7E7"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="10"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
$styles=str_replace('&amp;quot;','&quot;',$styles);
$zip->addFromString('[Content_Types].xml',$contentTypes);
$zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$zip->addFromString('xl/workbook.xml',$workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels',$relationships);
$zip->addFromString('xl/styles.xml',$styles);
if(!$zip->close())throw new RuntimeException('No se pudo finalizar el reporte Excel.');
clearstatcache(true,$temporaryFile);
if(!is_file($temporaryFile)||filesize($temporaryFile)<1000){@unlink($temporaryFile);throw new RuntimeException('El reporte Excel no se generó correctamente.');}

$filename='Reporte_Auditoria_Capacidad_TI_'.date('Y-m-d_His').'.xlsx';
while(ob_get_level()>$reportOutputBuffer)ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.filesize($temporaryFile));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($temporaryFile);
@unlink($temporaryFile);
exit;
