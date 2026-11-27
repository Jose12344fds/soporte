<?php
declare(strict_types=1);
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/funciones_checklist_pantalla.php';
require_once dirname(__DIR__, 2) . '/Reportes/_xlsx_corporativo.php';

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'ver');

try {
    $summarySource = find_by_sql(
        "SELECT c.id,c.fecha_inspeccion,c.usuario_inspeccionado,c.area,c.equipo_nombre,c.responsable_nombre,"
        . "m.folio,u.name registrado_por_nombre,"
        . "SUM(CASE WHEN r.estado='CUMPLE' THEN 1 ELSE 0 END) cumple,"
        . "SUM(CASE WHEN r.estado='NO_CUMPLE' THEN 1 ELSE 0 END) no_cumple,"
        . "SUM(CASE WHEN r.estado='NO_APLICA' THEN 1 ELSE 0 END) no_aplica,COUNT(r.id) total_criterios,"
        . "(SELECT COUNT(*) FROM mti_checklist_pantalla_fotos f WHERE f.checklist_id=c.id) total_fotos "
        . "FROM mti_checklist_pantalla c INNER JOIN activos_mantenimientos m ON m.id=c.mantenimiento_id "
        . "LEFT JOIN users u ON u.id=c.creado_por LEFT JOIN mti_checklist_pantalla_respuestas r ON r.checklist_id=c.id "
        . "GROUP BY c.id ORDER BY c.fecha_inspeccion DESC,c.id DESC"
    );
    $summary = [];
    foreach ($summarySource as $row) {
        $evaluated = (int)$row['cumple'] + (int)$row['no_cumple'];
        $summary[] = [
            'Id'=>(int)$row['id'],'Fecha'=>$row['fecha_inspeccion'],'Usuario'=>$row['usuario_inspeccionado'],'Area'=>$row['area'],
            'Equipo'=>$row['equipo_nombre'],'OT'=>$row['folio'],'Responsable'=>$row['responsable_nombre'],
            'RegistradoPor'=>$row['registrado_por_nombre'] ?: 'Sistema','Cumple'=>(int)$row['cumple'],
            'NoCumple'=>(int)$row['no_cumple'],'NoAplica'=>(int)$row['no_aplica'],'Total'=>(int)$row['total_criterios'],
            'Cumplimiento'=>$evaluated > 0 ? number_format((int)$row['cumple']*100/$evaluated,2).'%' : '0.00%',
            'Evidencias'=>(int)$row['total_fotos'],
        ];
    }

    $detailSource = find_by_sql(
        "SELECT c.id checklist_id,c.fecha_inspeccion,c.usuario_inspeccionado,c.area,c.equipo_nombre,c.responsable_nombre,"
        . "m.folio,r.numero,r.criterio_snapshot,r.estado,r.observacion,"
        . "(SELECT COUNT(*) FROM mti_checklist_pantalla_fotos f WHERE f.checklist_id=c.id) evidencias_generales,"
        . "(SELECT COUNT(*) FROM mti_checklist_pantalla_fotos f WHERE f.respuesta_id=r.id) evidencias_criterio "
        . "FROM mti_checklist_pantalla c INNER JOIN activos_mantenimientos m ON m.id=c.mantenimiento_id "
        . "INNER JOIN mti_checklist_pantalla_respuestas r ON r.checklist_id=c.id "
        . "ORDER BY c.fecha_inspeccion DESC,c.id DESC,r.numero"
    );
    $detail = [];
    foreach ($detailSource as $row) {
        $detail[] = [
            'Checklist'=>(int)$row['checklist_id'],'Fecha'=>$row['fecha_inspeccion'],'Usuario'=>$row['usuario_inspeccionado'],
            'Area'=>$row['area'],'Equipo'=>$row['equipo_nombre'],'OT'=>$row['folio'],'Responsable'=>$row['responsable_nombre'],
            'Numero'=>(int)$row['numero'],'Criterio'=>$row['criterio_snapshot'],'Estado'=>str_replace('_',' ',(string)$row['estado']),
            'Observacion'=>$row['observacion'] ?: 'Sin observacion','FotosGenerales'=>(int)$row['evidencias_generales'],
            'FotosCriterio'=>(int)$row['evidencias_criterio'],
        ];
    }

    $summaryColumns = [
        ['key'=>'Id','label'=>'ID','type'=>'number','width'=>9],['key'=>'Fecha','label'=>'FECHA DE INSPECCION','type'=>'date','width'=>20],
        ['key'=>'Usuario','label'=>'USUARIO INSPECCIONADO','width'=>28],['key'=>'Area','label'=>'AREA','width'=>20],
        ['key'=>'Equipo','label'=>'EQUIPO / PC','width'=>24],['key'=>'OT','label'=>'ORDEN DE TRABAJO','width'=>20],
        ['key'=>'Responsable','label'=>'RESPONSABLE DE INSPECCION','width'=>29],['key'=>'RegistradoPor','label'=>'REGISTRADO POR','width'=>24],
        ['key'=>'Cumple','label'=>'CUMPLE','type'=>'number','width'=>11],['key'=>'NoCumple','label'=>'NO CUMPLE','type'=>'number','width'=>13],
        ['key'=>'NoAplica','label'=>'NO APLICA','type'=>'number','width'=>12],['key'=>'Total','label'=>'TOTAL CRITERIOS','type'=>'number','width'=>16],
        ['key'=>'Cumplimiento','label'=>'CUMPLIMIENTO','width'=>16],['key'=>'Evidencias','label'=>'FOTOGRAFIAS','type'=>'number','width'=>14],
    ];
    $detailColumns = [
        ['key'=>'Checklist','label'=>'ID CHECKLIST','type'=>'number','width'=>13],['key'=>'Fecha','label'=>'FECHA','type'=>'date','width'=>16],
        ['key'=>'Usuario','label'=>'USUARIO INSPECCIONADO','width'=>27],['key'=>'Area','label'=>'AREA','width'=>18],
        ['key'=>'Equipo','label'=>'EQUIPO / PC','width'=>23],['key'=>'OT','label'=>'OT','width'=>18],
        ['key'=>'Responsable','label'=>'RESPONSABLE','width'=>25],['key'=>'Numero','label'=>'N.','type'=>'number','width'=>8],
        ['key'=>'Criterio','label'=>'CRITERIO DE VERIFICACION','width'=>48],['key'=>'Estado','label'=>'ESTADO','width'=>15],
        ['key'=>'Observacion','label'=>'OBSERVACION','width'=>45],['key'=>'FotosGenerales','label'=>'FOTOS GENERALES','type'=>'number','width'=>17],
        ['key'=>'FotosCriterio','label'=>'FOTOS DEL CRITERIO','type'=>'number','width'=>18],
    ];

    tx_export([
        ['name'=>'Resumen','title'=>'CHECKLIST DE ESCRITORIO Y PANTALLA LIMPIA - RESUMEN','code'=>'T-SI-PT-14','columns'=>$summaryColumns,'rows'=>$summary,'rowHeight'=>34],
        ['name'=>'Detalle','title'=>'CHECKLIST DE ESCRITORIO Y PANTALLA LIMPIA - DETALLE','code'=>'T-SI-PT-14','columns'=>$detailColumns,'rows'=>$detail,'rowHeight'=>46],
    ], 'Reporte_Checklist_Pantalla_Limpia_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__,2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo 'No se pudo generar el Excel: ' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
}
