<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/../../Reportes/_xlsx_corporativo.php';

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'ver');

function mti_xlsx_filter(string $name, array $allowed = []): string {
    $value = trim((string)($_GET[$name] ?? ''));
    return ($value === '' || ($allowed && !in_array($value, $allowed, true))) ? '' : $value;
}
function mti_xlsx_text($value): string {
    $value = (string)($value ?? '');
    return mb_strlen($value, 'UTF-8') > 32000 ? mb_substr($value, 0, 32000, 'UTF-8').'...' : $value;
}

$estado = mti_xlsx_filter('estado', ['PROGRAMADO','EN_PROCESO','COMPLETADO','CANCELADO']);
$tipo = mti_xlsx_filter('tipo', ['PREVENTIVO','CORRECTIVO','DIAGNOSTICO','MEJORA']);
$prioridad = mti_xlsx_filter('prioridad', ['BAJA','MEDIA','ALTA','CRITICA']);
$buscar = trim((string)($_GET['q'] ?? ''));
$where = ['1=1'];
if ($estado !== '') $where[] = "m.estado='".$db->escape($estado)."'";
if ($tipo !== '') $where[] = "m.tipo='".$db->escape($tipo)."'";
if ($prioridad !== '') $where[] = "m.prioridad='".$db->escape($prioridad)."'";
if ($buscar !== '') {
    $term = $db->escape($buscar);
    $where[] = "(m.folio LIKE '%{$term}%' OR q.Host LIKE '%{$term}%' OR q.Tipo_Activo LIKE '%{$term}%' OR m.tecnico_responsable LIKE '%{$term}%' OR m.usuario_asignado_nombre LIKE '%{$term}%' OR m.motivo LIKE '%{$term}%' OR m.diagnostico LIKE '%{$term}%' OR m.trabajo_realizado LIKE '%{$term}%')";
}
$whereSql = implode(' AND ', $where);

try {
    $orders = find_by_sql("SELECT m.id,m.folio,m.IdMaquina,q.Host,q.Tipo_Activo,q.EstadoMaquina,q.Ip,
        m.tipo,m.prioridad,m.estado,m.fecha_programada,m.fecha_ejecucion,m.motivo,m.diagnostico,m.trabajo_realizado,
        m.tecnico_responsable,m.usuario_asignado_id,m.usuario_asignado_nombre,
        CASE WHEN COALESCE(m.foto_antes,'')<>'' THEN 'SI' ELSE 'NO' END EvidenciaAntes,
        CASE WHEN COALESCE(m.foto_despues,'')<>'' THEN 'SI' ELSE 'NO' END EvidenciaDespues,
        oc.id OcurrenciaId,oc.codigo OcurrenciaCodigo,p.codigo PlanCodigo,p.nombre PlanNombre,
        COALESCE(d.Items,0) Items,COALESCE(d.CostoTotal,0) CostoTotal,
        uc.name CreadoPor,ua.name ActualizadoPor,m.created_at,m.updated_at
      FROM activos_mantenimientos m
      INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina
      LEFT JOIN activos_mantenimiento_ocurrencias oc ON oc.mantenimiento_id=m.id
      LEFT JOIN activos_mantenimiento_planes p ON p.id=oc.plan_id
      LEFT JOIN (SELECT mantenimiento_id,COUNT(*) Items,SUM(cantidad*costo_unitario) CostoTotal FROM activos_mantenimiento_detalle GROUP BY mantenimiento_id) d ON d.mantenimiento_id=m.id
      LEFT JOIN users uc ON uc.id=m.creado_por LEFT JOIN users ua ON ua.id=m.actualizado_por
      WHERE {$whereSql} ORDER BY m.fecha_programada DESC,m.id DESC");

    $ids = array_map(static fn(array $r): int => (int)$r['id'], $orders);
    $idList = $ids ? implode(',', $ids) : '0';
    $details = find_by_sql("SELECT d.id DetalleId,d.mantenimiento_id OrdenId,m.folio,m.IdMaquina,q.Host,d.tipo,d.descripcion,d.cantidad,d.costo_unitario,(d.cantidad*d.costo_unitario) CostoTotal,d.resultado,u.name CreadoPor,d.created_at
      FROM activos_mantenimiento_detalle d INNER JOIN activos_mantenimientos m ON m.id=d.mantenimiento_id INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina LEFT JOIN users u ON u.id=d.creado_por
      WHERE d.mantenimiento_id IN ({$idList}) ORDER BY m.fecha_programada DESC,m.id DESC,d.id");
    $history = find_by_sql("SELECT h.id HistorialId,h.mantenimiento_id OrdenId,m.folio,q.Host,h.accion,h.estado_anterior,h.estado_nuevo,h.comentario,u.name RegistradoPor,h.created_at
      FROM activos_mantenimiento_historial h INNER JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina LEFT JOIN users u ON u.id=h.registrado_por
      WHERE h.mantenimiento_id IN ({$idList}) ORDER BY h.created_at DESC,h.id DESC");

    foreach ($orders as &$r) foreach (['motivo','diagnostico','trabajo_realizado'] as $f) $r[$f] = mti_xlsx_text($r[$f]);
    unset($r);
    foreach ($details as &$r) { $r['descripcion']=mti_xlsx_text($r['descripcion']); $r['resultado']=mti_xlsx_text($r['resultado']); }
    unset($r);
    foreach ($history as &$r) $r['comentario']=mti_xlsx_text($r['comentario']);
    unset($r);

    $totalCost = array_sum(array_map(static fn(array $r): float => (float)$r['CostoTotal'], $orders));
    $completed = count(array_filter($orders, static fn(array $r): bool => $r['estado']==='COMPLETADO'));
    $pending = count(array_filter($orders, static fn(array $r): bool => in_array($r['estado'], ['PROGRAMADO','EN_PROCESO'], true)));
    $overdue = count(array_filter($orders, static fn(array $r): bool => $r['estado']==='PROGRAMADO' && $r['fecha_programada']<date('Y-m-d')));
    $scope=[];
    if($buscar!=='')$scope[]='Busqueda: '.$buscar; if($estado!=='')$scope[]='Estado: '.$estado; if($tipo!=='')$scope[]='Tipo: '.$tipo; if($prioridad!=='')$scope[]='Prioridad: '.$prioridad;
    $summary = [
      ['Indicador'=>'Fecha de generacion','Resultado'=>date('d/m/Y H:i:s'),'Detalle'=>'Corte del reporte.'],
      ['Indicador'=>'Filtros aplicados','Resultado'=>$scope?implode(' / ',$scope):'Todos los registros','Detalle'=>'Coincide con los filtros seleccionados en la pantalla.'],
      ['Indicador'=>'Ordenes incluidas','Resultado'=>(string)count($orders),'Detalle'=>'Total de ordenes exportadas.'],
      ['Indicador'=>'Pendientes o en proceso','Resultado'=>(string)$pending,'Detalle'=>'Ordenes programadas o en proceso.'],
      ['Indicador'=>'Completadas','Resultado'=>(string)$completed,'Detalle'=>'Ordenes con trabajo concluido.'],
      ['Indicador'=>'Programadas vencidas','Resultado'=>(string)$overdue,'Detalle'=>'Ordenes programadas cuya fecha ya paso.'],
      ['Indicador'=>'Repuestos y servicios','Resultado'=>(string)count($details),'Detalle'=>'Lineas de detalle vinculadas.'],
      ['Indicador'=>'Costo total','Resultado'=>'S/ '.number_format($totalCost,2,'.',','),'Detalle'=>'Suma de cantidad por costo unitario.'],
    ];

    $summaryCols=[['key'=>'Indicador','label'=>'INDICADOR','width'=>32],['key'=>'Resultado','label'=>'RESULTADO','width'=>42],['key'=>'Detalle','label'=>'DETALLE','width'=>48]];
    $orderCols=[
      ['key'=>'id','label'=>'ID ORDEN','type'=>'number','width'=>12],['key'=>'folio','label'=>'FOLIO','width'=>18],['key'=>'IdMaquina','label'=>'ID ACTIVO','type'=>'number','width'=>12],
      ['key'=>'Host','label'=>'EQUIPO / HOST','width'=>23],['key'=>'Tipo_Activo','label'=>'TIPO DE ACTIVO','width'=>18],['key'=>'EstadoMaquina','label'=>'ESTADO DEL ACTIVO','width'=>19],['key'=>'Ip','label'=>'IP','width'=>17],
      ['key'=>'usuario_asignado_nombre','label'=>'USUARIO ASIGNADO','width'=>27],['key'=>'tipo','label'=>'TIPO DE MANTENIMIENTO','width'=>22],['key'=>'prioridad','label'=>'PRIORIDAD','width'=>14],['key'=>'estado','label'=>'ESTADO DE LA ORDEN','width'=>20],
      ['key'=>'fecha_programada','label'=>'FECHA PROGRAMADA','type'=>'date','width'=>19],['key'=>'fecha_ejecucion','label'=>'FECHA REALIZADA','type'=>'date','width'=>18],['key'=>'tecnico_responsable','label'=>'RESPONSABLE TI','width'=>26],
      ['key'=>'motivo','label'=>'MOTIVO O FALLA REPORTADA','width'=>48],['key'=>'diagnostico','label'=>'DIAGNOSTICO','width'=>48],['key'=>'trabajo_realizado','label'=>'TRABAJO REALIZADO','width'=>48],
      ['key'=>'OcurrenciaId','label'=>'ID PROGRAMACION','type'=>'number','width'=>16],['key'=>'OcurrenciaCodigo','label'=>'CODIGO PROGRAMACION','width'=>21],['key'=>'PlanCodigo','label'=>'CODIGO DEL PLAN','width'=>18],['key'=>'PlanNombre','label'=>'PLAN DE MANTENIMIENTO','width'=>32],
      ['key'=>'Items','label'=>'ITEMS','type'=>'number','width'=>10],['key'=>'CostoTotal','label'=>'COSTO TOTAL (S/)','type'=>'number','width'=>17],['key'=>'EvidenciaAntes','label'=>'EVIDENCIA ANTES','width'=>17],['key'=>'EvidenciaDespues','label'=>'EVIDENCIA DESPUES','width'=>19],
      ['key'=>'CreadoPor','label'=>'CREADO POR','width'=>24],['key'=>'created_at','label'=>'FECHA DE CREACION','width'=>21],['key'=>'ActualizadoPor','label'=>'ACTUALIZADO POR','width'=>24],['key'=>'updated_at','label'=>'ULTIMA ACTUALIZACION','width'=>22]
    ];
    $detailCols=[
      ['key'=>'DetalleId','label'=>'ID DETALLE','type'=>'number','width'=>13],['key'=>'OrdenId','label'=>'ID ORDEN','type'=>'number','width'=>12],['key'=>'folio','label'=>'FOLIO','width'=>18],['key'=>'IdMaquina','label'=>'ID ACTIVO','type'=>'number','width'=>12],['key'=>'Host','label'=>'EQUIPO / HOST','width'=>23],
      ['key'=>'tipo','label'=>'TIPO','width'=>17],['key'=>'descripcion','label'=>'REPUESTO, SERVICIO O ACTIVIDAD','width'=>48],['key'=>'cantidad','label'=>'CANTIDAD','type'=>'number','width'=>12],['key'=>'costo_unitario','label'=>'COSTO UNITARIO (S/)','type'=>'number','width'=>20],['key'=>'CostoTotal','label'=>'COSTO TOTAL (S/)','type'=>'number','width'=>18],
      ['key'=>'resultado','label'=>'RESULTADO','width'=>38],['key'=>'CreadoPor','label'=>'REGISTRADO POR','width'=>24],['key'=>'created_at','label'=>'FECHA DE REGISTRO','width'=>21]
    ];
    $historyCols=[
      ['key'=>'HistorialId','label'=>'ID HISTORIAL','type'=>'number','width'=>14],['key'=>'OrdenId','label'=>'ID ORDEN','type'=>'number','width'=>12],['key'=>'folio','label'=>'FOLIO','width'=>18],['key'=>'Host','label'=>'EQUIPO / HOST','width'=>23],
      ['key'=>'accion','label'=>'ACCION','width'=>20],['key'=>'estado_anterior','label'=>'ESTADO ANTERIOR','width'=>18],['key'=>'estado_nuevo','label'=>'ESTADO NUEVO','width'=>18],['key'=>'comentario','label'=>'COMENTARIO','width'=>48],['key'=>'RegistradoPor','label'=>'REGISTRADO POR','width'=>25],['key'=>'created_at','label'=>'FECHA Y HORA','width'=>21]
    ];

    tx_export([
      ['name'=>'Resumen','title'=>'REPORTE DE MANTENIMIENTO TI','code'=>'T-TI-F-02','columns'=>$summaryCols,'rows'=>$summary],
      ['name'=>'Ordenes','title'=>'ORDENES DE MANTENIMIENTO TI','code'=>'T-TI-F-02','columns'=>$orderCols,'rows'=>$orders],
      ['name'=>'Repuestos y servicios','title'=>'REPUESTOS, SERVICIOS Y ACTIVIDADES','code'=>'T-TI-F-02','columns'=>$detailCols,'rows'=>$details],
      ['name'=>'Trazabilidad','title'=>'HISTORIAL DE CAMBIOS DE MANTENIMIENTO TI','code'=>'T-TI-F-02','columns'=>$historyCols,'rows'=>$history],
    ], 'Reporte_Mantenimiento_TI_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__,2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while(ob_get_level())ob_end_clean();
    http_response_code(500); header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el reporte de Mantenimiento TI. '.$error->getMessage(); exit;
}
