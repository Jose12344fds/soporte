<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../eventos_ti/bootstrap.php';
require_once __DIR__.'/../capacidad_ti/importar_logs_lib.php';
require_once __DIR__.'/../../Reportes/_xlsx_corporativo.php';

inc_schema();
evt_schema();
cap_log_schema();
require_permiso('incidencias', 'ver');

function inc_export_text($value, int $limit = 32000): string
{
    $value = (string)($value ?? '');
    return mb_strlen($value, 'UTF-8') > $limit ? mb_substr($value, 0, $limit, 'UTF-8').'…' : $value;
}

$year = (int)($_GET['anio'] ?? date('Y'));
if ($year < 2020 || $year > (int)date('Y') + 1) $year = (int)date('Y');
$scope = inc_ticket_scope_sql('t');

try {
    $tickets = find_by_sql("SELECT t.IdTicket,t.Folio,t.Tipo,t.Canal,t.Titulo,t.Descripcion,
            c.Codigo CategoriaCodigo,c.Nombre Categoria,m.Host Equipo,m.Tipo_Activo TipoEquipo,
            sv.CodigoServicio,sv.NombreServicio ServicioAfectado,sv.TipoServicio TipoServicio,sv.Criticidad CriticidadServicio,
            ca.NombreAplicacion AplicacionAfectada,ca.TipoAplicacion,ca.Ambiente AmbienteAplicacion,ca.Criticidad CriticidadAplicacion,
            cs.NombreServicio ServicioCapacidad,cs.Categoria CategoriaServicioCapacidad,cs.Criticidad CriticidadServicioCapacidad,
            COALESCE(ca.NombreAplicacion,cs.NombreServicio,sv.NombreServicio) ElementoAfectado,
            CASE WHEN ca.IdAplicacion IS NOT NULL THEN 'APLICACION' WHEN cs.IdServicio IS NOT NULL THEN 'SERVICIO TI' WHEN sv.IdServicio IS NOT NULL THEN 'SERVICIO ANTERIOR' ELSE '' END TipoElementoAfectado,
            t.IdMaquina,t.IdServicio,t.IdAplicacionCapacidad,t.IdServicioCapacidad,t.SolicitanteId,t.SolicitanteNombre,t.SolicitanteCorreo,t.SolicitanteArea,
            t.Impacto,t.Urgencia,t.Prioridad,t.Estado,t.AsignadoA,t.AsignadoNombre,t.GrupoAsignacion,
            s.Nombre SLA,s.MinutosRespuesta ObjetivoRespuestaMin,s.MinutosResolucion ObjetivoResolucionMin,
            t.FechaRegistro,t.FechaPrimeraRespuesta,t.FechaObjetivoRespuesta,t.FechaObjetivoResolucion,
            t.FechaResolucion,t.FechaCierre,t.PausaSLAInicio,t.MinutosPausa,t.Resolucion,t.CausaRaiz,
            t.CodigoCierre,t.Reaperturas,t.Satisfaccion,t.ComentarioSatisfaccion,t.CreadoPor,
            t.ActualizadoPor,t.updated_at,
            CASE WHEN t.FechaPrimeraRespuesta IS NULL THEN NULL ELSE TIMESTAMPDIFF(MINUTE,t.FechaRegistro,t.FechaPrimeraRespuesta) END TiempoRespuestaMin,
            CASE WHEN t.FechaResolucion IS NULL THEN NULL ELSE TIMESTAMPDIFF(MINUTE,t.FechaRegistro,t.FechaResolucion) END TiempoResolucionMin,
            CASE WHEN t.FechaResolucion IS NULL THEN 'PENDIENTE' WHEN t.FechaResolucion<=t.FechaObjetivoResolucion THEN 'CUMPLIDO' ELSE 'VENCIDO' END CumplimientoSLA,
            (SELECT COUNT(*) FROM inc_seguimiento sg WHERE sg.IdTicket=t.IdTicket) TotalSeguimientos,
            (SELECT COUNT(*) FROM inc_adjuntos ad WHERE ad.IdTicket=t.IdTicket AND ad.Activo=1) TotalAdjuntos,
            (SELECT COUNT(*) FROM evt_eventos ev WHERE ev.IdIncidencia=t.IdTicket) EventosTI,
            (SELECT COUNT(*) FROM alerta_capacidad ac WHERE ac.IdIncidencia=t.IdTicket) AlertasCapacidad
        FROM inc_tickets t
        LEFT JOIN inc_categorias c ON c.IdCategoria=t.IdCategoria
        LEFT JOIN maquina m ON m.IdMaquina=t.IdMaquina
        LEFT JOIN servicios_ti sv ON sv.IdServicio=t.IdServicio
        LEFT JOIN aplicacion ca ON ca.IdAplicacion=t.IdAplicacionCapacidad
        LEFT JOIN servicio_ti cs ON cs.IdServicio=t.IdServicioCapacidad
        LEFT JOIN inc_sla_politicas s ON s.IdSLA=t.IdSLA
        WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
        ORDER BY t.FechaRegistro,t.IdTicket");

    $followups = find_by_sql("SELECT sg.IdSeguimiento,sg.IdTicket,t.Folio,sg.Fecha,sg.UsuarioId,
            sg.UsuarioNombre,sg.Tipo,sg.EsPublico,sg.EstadoAnterior,sg.EstadoNuevo,sg.Comentario
        FROM inc_seguimiento sg
        INNER JOIN inc_tickets t ON t.IdTicket=sg.IdTicket
        WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
        ORDER BY sg.Fecha,sg.IdSeguimiento");

    $attachments = find_by_sql("SELECT ad.IdAdjunto,ad.IdTicket,t.Folio,ad.IdSeguimiento,
            ad.NombreOriginal,ad.NombreArchivo,ad.Ruta,ad.Mime,ad.Tamano,ad.SubidoPor,
            ad.SubidoPorNombre,ad.Fecha,ad.Activo
        FROM inc_adjuntos ad
        INNER JOIN inc_tickets t ON t.IdTicket=ad.IdTicket
        WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
        ORDER BY ad.Fecha,ad.IdAdjunto");

    $monitoring = find_by_sql("SELECT t.IdTicket,t.Folio,'EVENTO TI' TipoOrigen,
            CONCAT('EVT-',LPAD(e.IdEvento,7,'0')) CodigoOrigen,e.FechaEvento FechaOrigen,
            COALESCE(m.Host,e.HostOrigen,'') Equipo,COALESCE(v.NombreVM,'') VM,
            COALESCE(e.IPOrigen,v.IP_VM,'') IP,e.Categoria RecursoCategoria,e.Severidad NivelSeveridad,
            e.Titulo Descripcion,e.ArchivoOrigen ArchivoFuente,e.Estado EstadoOrigen,
            e.IdEvento IdRegistroOrigen
        FROM inc_tickets t
        INNER JOIN evt_eventos e ON e.IdIncidencia=t.IdTicket
        LEFT JOIN maquina_virtual v ON v.IdVM=e.IdVM
        LEFT JOIN maquina m ON m.IdMaquina=COALESCE(e.IdMaquina,v.IdMaquina)
        WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
        UNION ALL
        SELECT t.IdTicket,t.Folio,'ALERTA CAPACIDAD' TipoOrigen,
            CONCAT('ALERTA-',LPAD(ac.IdAlerta,5,'0')) CodigoOrigen,ac.FechaAlerta FechaOrigen,
            COALESCE(m.Host,'') Equipo,COALESCE(v.NombreVM,'') VM,COALESCE(v.IP_VM,'') IP,
            ac.Recurso RecursoCategoria,ac.NivelAlerta NivelSeveridad,ac.Descripcion,
            COALESCE(
                (SELECT i.ArchivoOriginal FROM importacion_log_capacidad i
                    WHERE i.IdVM=ac.IdVM AND i.FechaMedicion=h.FechaEvaluacion
                    ORDER BY i.IdImportacion DESC LIMIT 1),
                h.Observacion,
                CONCAT('HC-',LPAD(ac.IdHistorial,6,'0'))
            ) ArchivoFuente,
            ac.EstadoAlerta EstadoOrigen,ac.IdAlerta IdRegistroOrigen
        FROM inc_tickets t
        INNER JOIN alerta_capacidad ac ON ac.IdIncidencia=t.IdTicket
        LEFT JOIN historial_capacidad h ON h.IdHistorial=ac.IdHistorial
        LEFT JOIN maquina_virtual v ON v.IdVM=ac.IdVM
        LEFT JOIN maquina m ON m.IdMaquina=v.IdMaquina
        WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
        ORDER BY FechaOrigen,CodigoOrigen");

    foreach ($tickets as &$ticket) {
        foreach (['Descripcion','Resolucion','CausaRaiz','ComentarioSatisfaccion'] as $field) $ticket[$field] = inc_export_text($ticket[$field]);
    }
    unset($ticket);
    foreach ($followups as &$followup) $followup['Comentario'] = inc_export_text($followup['Comentario']);
    unset($followup);
    $total = count($tickets);
    $incidents = count(array_filter($tickets, static fn(array $r): bool => $r['Tipo'] === 'INCIDENTE'));
    $requests = count(array_filter($tickets, static fn(array $r): bool => $r['Tipo'] === 'SOLICITUD'));
    $events = count(array_filter($tickets, static fn(array $r): bool => $r['Tipo'] === 'EVENTO'));
    $open = count(array_filter($tickets, static fn(array $r): bool => !in_array($r['Estado'], ['RESUELTO','CERRADO','CANCELADO'], true)));
    $closed = count(array_filter($tickets, static fn(array $r): bool => in_array($r['Estado'], ['RESUELTO','CERRADO'], true)));
    $slaMet = count(array_filter($tickets, static fn(array $r): bool => $r['CumplimientoSLA'] === 'CUMPLIDO'));
    $slaLate = count(array_filter($tickets, static fn(array $r): bool => $r['CumplimientoSLA'] === 'VENCIDO'));
    $monitoringTickets = count(array_filter($tickets, static fn(array $r): bool => $r['Canal'] === 'MONITOREO'));
    $eventCount = array_sum(array_map(static fn(array $r): int => (int)$r['EventosTI'], $tickets));
    $capacityCount = array_sum(array_map(static fn(array $r): int => (int)$r['AlertasCapacidad'], $tickets));
    $summary = [
        ['Indicador'=>'Año del reporte','Resultado'=>(string)$year,'Interpretacion'=>'Año de registro de los tickets incluidos.'],
        ['Indicador'=>'Fecha de generación','Resultado'=>date('d/m/Y H:i:s'),'Interpretacion'=>'Corte del reporte.'],
        ['Indicador'=>'Tickets incluidos','Resultado'=>(string)$total,'Interpretacion'=>'Alcance según los permisos del usuario que exporta.'],
        ['Indicador'=>'Incidentes','Resultado'=>(string)$incidents,'Interpretacion'=>'Interrupciones o degradaciones reportadas.'],
        ['Indicador'=>'Solicitudes','Resultado'=>(string)$requests,'Interpretacion'=>'Solicitudes de servicio registradas.'],
        ['Indicador'=>'Tickets abiertos','Resultado'=>(string)$open,'Interpretacion'=>'Pendientes de resolución o cierre.'],
        ['Indicador'=>'Tickets resueltos o cerrados','Resultado'=>(string)$closed,'Interpretacion'=>'Atención concluida.'],
        ['Indicador'=>'SLA cumplido','Resultado'=>(string)$slaMet,'Interpretacion'=>'Resueltos dentro del objetivo de resolución.'],
        ['Indicador'=>'SLA vencido','Resultado'=>(string)$slaLate,'Interpretacion'=>'Resueltos después del objetivo de resolución.'],
        ['Indicador'=>'Tickets originados por monitoreo','Resultado'=>(string)$monitoringTickets,'Interpretacion'=>'Creados desde eventos TI o alertas de capacidad.'],
        ['Indicador'=>'Tickets tipo evento','Resultado'=>(string)$events,'Interpretacion'=>'Eventos registrados manualmente o desde monitoreo.'],
        ['Indicador'=>'Eventos TI vinculados','Resultado'=>(string)$eventCount,'Interpretacion'=>'Eventos técnicos que sustentan tickets del año.'],
        ['Indicador'=>'Alertas de capacidad vinculadas','Resultado'=>(string)$capacityCount,'Interpretacion'=>'Alertas de CPU, RAM, disco o red que sustentan tickets.'],
        ['Indicador'=>'Flujo de evidencia','Resultado'=>'Fuente automática → ticket → seguimiento → resolución','Interpretacion'=>'Use las hojas de origen, seguimiento y adjuntos para sustentar cada folio.'],
    ];
    $summaryColumns = [
        ['key'=>'Indicador','label'=>'INDICADOR','width'=>36],
        ['key'=>'Resultado','label'=>'RESULTADO','width'=>38],
        ['key'=>'Interpretacion','label'=>'INTERPRETACIÓN','width'=>52],
    ];
    $ticketColumns = [
        ['key'=>'IdTicket','label'=>'ID TICKET','type'=>'number','width'=>12],
        ['key'=>'Folio','label'=>'FOLIO','width'=>18],
        ['key'=>'Tipo','label'=>'TIPO','width'=>16],
        ['key'=>'Canal','label'=>'CANAL','width'=>15],
        ['key'=>'Titulo','label'=>'ASUNTO','width'=>38],
        ['key'=>'Descripcion','label'=>'DESCRIPCIÓN','width'=>48],
        ['key'=>'CategoriaCodigo','label'=>'CÓDIGO CATEGORÍA','width'=>18],
        ['key'=>'Categoria','label'=>'CATEGORÍA','width'=>28],
        ['key'=>'TipoElementoAfectado','label'=>'TIPO ELEMENTO AFECTADO','width'=>21],
        ['key'=>'ElementoAfectado','label'=>'APLICACIÓN / SERVICIO TI AFECTADO','width'=>32],
        ['key'=>'IdAplicacionCapacidad','label'=>'ID APLICACIÓN CAPACIDAD','type'=>'number','width'=>20],
        ['key'=>'TipoAplicacion','label'=>'TIPO APLICACIÓN','width'=>18],
        ['key'=>'AmbienteAplicacion','label'=>'AMBIENTE APLICACIÓN','width'=>20],
        ['key'=>'IdServicioCapacidad','label'=>'ID SERVICIO CAPACIDAD','type'=>'number','width'=>19],
        ['key'=>'CategoriaServicioCapacidad','label'=>'CATEGORÍA SERVICIO TI','width'=>22],
        ['key'=>'IdServicio','label'=>'ID SERVICIO ANTERIOR','type'=>'number','width'=>16],
        ['key'=>'CodigoServicio','label'=>'CÓDIGO SERVICIO','width'=>18],
        ['key'=>'ServicioAfectado','label'=>'SERVICIO AFECTADO','width'=>28],
        ['key'=>'TipoServicio','label'=>'TIPO SERVICIO','width'=>18],
        ['key'=>'CriticidadServicio','label'=>'CRITICIDAD SERVICIO','width'=>18],
        ['key'=>'IdMaquina','label'=>'ID ACTIVO','type'=>'number','width'=>12],
        ['key'=>'Equipo','label'=>'EQUIPO / HOST','width'=>22],
        ['key'=>'TipoEquipo','label'=>'TIPO EQUIPO','width'=>16],
        ['key'=>'SolicitanteId','label'=>'ID SOLICITANTE','type'=>'number','width'=>15],
        ['key'=>'SolicitanteNombre','label'=>'SOLICITANTE','width'=>26],
        ['key'=>'SolicitanteCorreo','label'=>'CORREO SOLICITANTE','width'=>30],
        ['key'=>'SolicitanteArea','label'=>'ÁREA SOLICITANTE','width'=>22],
        ['key'=>'Impacto','label'=>'IMPACTO','type'=>'number','width'=>11],
        ['key'=>'Urgencia','label'=>'URGENCIA','type'=>'number','width'=>11],
        ['key'=>'Prioridad','label'=>'PRIORIDAD','width'=>14],
        ['key'=>'Estado','label'=>'ESTADO','width'=>19],
        ['key'=>'AsignadoA','label'=>'ID RESPONSABLE','type'=>'number','width'=>15],
        ['key'=>'AsignadoNombre','label'=>'RESPONSABLE TI','width'=>25],
        ['key'=>'GrupoAsignacion','label'=>'GRUPO ASIGNACIÓN','width'=>22],
        ['key'=>'SLA','label'=>'POLÍTICA SLA','width'=>22],
        ['key'=>'ObjetivoRespuestaMin','label'=>'OBJETIVO RESPUESTA (MIN)','type'=>'number','width'=>22],
        ['key'=>'ObjetivoResolucionMin','label'=>'OBJETIVO RESOLUCIÓN (MIN)','type'=>'number','width'=>23],
        ['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21],
        ['key'=>'FechaPrimeraRespuesta','label'=>'PRIMERA RESPUESTA','width'=>21],
        ['key'=>'FechaObjetivoRespuesta','label'=>'OBJETIVO RESPUESTA','width'=>21],
        ['key'=>'FechaObjetivoResolucion','label'=>'OBJETIVO RESOLUCIÓN','width'=>21],
        ['key'=>'FechaResolucion','label'=>'FECHA RESOLUCIÓN','width'=>21],
        ['key'=>'FechaCierre','label'=>'FECHA CIERRE','width'=>21],
        ['key'=>'PausaSLAInicio','label'=>'INICIO PAUSA SLA','width'=>21],
        ['key'=>'MinutosPausa','label'=>'PAUSA SLA (MIN)','type'=>'number','width'=>17],
        ['key'=>'Resolucion','label'=>'SOLUCIÓN','width'=>48],
        ['key'=>'CausaRaiz','label'=>'CAUSA RAÍZ','width'=>48],
        ['key'=>'CodigoCierre','label'=>'CÓDIGO CIERRE','width'=>18],
        ['key'=>'Reaperturas','label'=>'REAPERTURAS','type'=>'number','width'=>14],
        ['key'=>'Satisfaccion','label'=>'SATISFACCIÓN','type'=>'number','width'=>14],
        ['key'=>'ComentarioSatisfaccion','label'=>'COMENTARIO SATISFACCIÓN','width'=>40],
        ['key'=>'CreadoPor','label'=>'CREADO POR ID','type'=>'number','width'=>14],
        ['key'=>'ActualizadoPor','label'=>'ACTUALIZADO POR ID','type'=>'number','width'=>18],
        ['key'=>'updated_at','label'=>'ÚLTIMA ACTUALIZACIÓN','width'=>21],
        ['key'=>'TiempoRespuestaMin','label'=>'TIEMPO RESPUESTA (MIN)','type'=>'number','width'=>22],
        ['key'=>'TiempoResolucionMin','label'=>'TIEMPO RESOLUCIÓN (MIN)','type'=>'number','width'=>23],
        ['key'=>'CumplimientoSLA','label'=>'CUMPLIMIENTO SLA','width'=>18],
        ['key'=>'TotalSeguimientos','label'=>'SEGUIMIENTOS','type'=>'number','width'=>14],
        ['key'=>'TotalAdjuntos','label'=>'ADJUNTOS','type'=>'number','width'=>12],
        ['key'=>'EventosTI','label'=>'EVENTOS TI VINCULADOS','type'=>'number','width'=>21],
        ['key'=>'AlertasCapacidad','label'=>'ALERTAS CAPACIDAD VINCULADAS','type'=>'number','width'=>26],
    ];
    $followupColumns = [
        ['key'=>'IdSeguimiento','label'=>'ID SEGUIMIENTO','type'=>'number','width'=>16],
        ['key'=>'IdTicket','label'=>'ID TICKET','type'=>'number','width'=>12],
        ['key'=>'Folio','label'=>'FOLIO','width'=>18],
        ['key'=>'Fecha','label'=>'FECHA','width'=>21],
        ['key'=>'UsuarioId','label'=>'ID USUARIO','type'=>'number','width'=>12],
        ['key'=>'UsuarioNombre','label'=>'USUARIO','width'=>25],
        ['key'=>'Tipo','label'=>'TIPO DE HITO','width'=>22],
        ['key'=>'EsPublico','label'=>'VISIBLE AL USUARIO','type'=>'boolean','width'=>18],
        ['key'=>'EstadoAnterior','label'=>'ESTADO ANTERIOR','width'=>18],
        ['key'=>'EstadoNuevo','label'=>'ESTADO NUEVO','width'=>18],
        ['key'=>'Comentario','label'=>'COMENTARIO / ACCIÓN REALIZADA','width'=>48],
    ];
    $monitoringColumns = [
        ['key'=>'IdTicket','label'=>'ID TICKET','type'=>'number','width'=>12],
        ['key'=>'Folio','label'=>'FOLIO','width'=>18],
        ['key'=>'TipoOrigen','label'=>'TIPO DE ORIGEN','width'=>20],
        ['key'=>'CodigoOrigen','label'=>'CÓDIGO DE EVIDENCIA','width'=>20],
        ['key'=>'IdRegistroOrigen','label'=>'ID REGISTRO ORIGEN','type'=>'number','width'=>18],
        ['key'=>'FechaOrigen','label'=>'FECHA DEL ORIGEN','width'=>21],
        ['key'=>'Equipo','label'=>'EQUIPO / HOST','width'=>24],
        ['key'=>'VM','label'=>'MÁQUINA VIRTUAL','width'=>24],
        ['key'=>'IP','label'=>'IP','width'=>17],
        ['key'=>'RecursoCategoria','label'=>'RECURSO / CATEGORÍA','width'=>22],
        ['key'=>'NivelSeveridad','label'=>'NIVEL / SEVERIDAD','width'=>20],
        ['key'=>'Descripcion','label'=>'DESCRIPCIÓN','width'=>48],
        ['key'=>'ArchivoFuente','label'=>'ARCHIVO O MEDICIÓN FUENTE','width'=>48],
        ['key'=>'EstadoOrigen','label'=>'ESTADO DEL ORIGEN','width'=>20],
    ];
    $attachmentColumns = [
        ['key'=>'IdAdjunto','label'=>'ID ADJUNTO','type'=>'number','width'=>13],
        ['key'=>'IdTicket','label'=>'ID TICKET','type'=>'number','width'=>12],
        ['key'=>'Folio','label'=>'FOLIO','width'=>18],
        ['key'=>'IdSeguimiento','label'=>'ID SEGUIMIENTO','type'=>'number','width'=>16],
        ['key'=>'NombreOriginal','label'=>'NOMBRE ORIGINAL','width'=>38],
        ['key'=>'NombreArchivo','label'=>'ARCHIVO ALMACENADO','width'=>38],
        ['key'=>'Ruta','label'=>'RUTA DE EVIDENCIA','width'=>48],
        ['key'=>'Mime','label'=>'TIPO DE ARCHIVO','width'=>24],
        ['key'=>'Tamano','label'=>'TAMAÑO (BYTES)','type'=>'number','width'=>16],
        ['key'=>'SubidoPor','label'=>'ID USUARIO','type'=>'number','width'=>12],
        ['key'=>'SubidoPorNombre','label'=>'SUBIDO POR','width'=>24],
        ['key'=>'Fecha','label'=>'FECHA','width'=>21],
        ['key'=>'Activo','label'=>'ACTIVO','type'=>'boolean','width'=>11],
    ];
    tx_export([
        ['name'=>'Resumen','title'=>'REPORTE ANUAL DE MESA DE AYUDA TI '.$year,'code'=>'T-TI-F-02','columns'=>$summaryColumns,'rows'=>$summary],
        ['name'=>'Tickets','title'=>'TICKETS, SLA Y TRAZABILIDAD '.$year,'code'=>'T-TI-F-02','columns'=>$ticketColumns,'rows'=>$tickets],
        ['name'=>'Seguimiento','title'=>'HISTORIAL DE ATENCIÓN DE TICKETS '.$year,'code'=>'T-TI-F-02','columns'=>$followupColumns,'rows'=>$followups],
        ['name'=>'Origen monitoreo','title'=>'EVENTOS Y ALERTAS QUE ORIGINARON TICKETS '.$year,'code'=>'T-TI-F-02','columns'=>$monitoringColumns,'rows'=>$monitoring],
        ['name'=>'Adjuntos','title'=>'EVIDENCIAS ADJUNTAS DE TICKETS '.$year,'code'=>'T-TI-F-02','columns'=>$attachmentColumns,'rows'=>$attachments],
    ], 'Tickets_TI_Trazabilidad_'.$year.'_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__, 2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el reporte Excel de tickets: '.$error->getMessage();
}
