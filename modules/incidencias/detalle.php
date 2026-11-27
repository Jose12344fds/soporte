<?php
$page_title = 'Detalle de ticket';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';

inc_schema();
require_permiso('incidencias', 'ver');

$id = (int)($_GET['id'] ?? 0);
$rows = find_by_sql(
    "SELECT t.*,c.Nombre CategoriaNombre,c.Icono CategoriaIcono,c.Color CategoriaColor,
            m.Host,m.Tipo_Activo,m.Ip,m.EstadoMaquina,sv.CodigoServicio,sv.NombreServicio,sv.TipoServicio,sv.Criticidad ServicioCriticidad,sv.EstadoServicio,
            ca.NombreAplicacion,ca.TipoAplicacion,ca.Ambiente,ca.Criticidad CriticidadAplicacion,
            cs.NombreServicio NombreServicioCapacidad,cs.Categoria CategoriaServicioCapacidad,cs.Criticidad CriticidadServicioCapacidad,
            COALESCE(ca.NombreAplicacion,cs.NombreServicio,sv.NombreServicio) ElementoAfectado,
            CASE WHEN ca.IdAplicacion IS NOT NULL THEN 'Aplicación' WHEN cs.IdServicio IS NOT NULL THEN 'Servicio TI' WHEN sv.IdServicio IS NOT NULL THEN 'Servicio anterior' ELSE '' END TipoElementoAfectado,
            s.Nombre SLANombre,s.MinutosRespuesta,s.MinutosResolucion
     FROM inc_tickets t
     LEFT JOIN inc_categorias c ON c.IdCategoria=t.IdCategoria
     LEFT JOIN maquina m ON m.IdMaquina=t.IdMaquina
     LEFT JOIN servicios_ti sv ON sv.IdServicio=t.IdServicio
     LEFT JOIN aplicacion ca ON ca.IdAplicacion=t.IdAplicacionCapacidad
     LEFT JOIN servicio_ti cs ON cs.IdServicio=t.IdServicioCapacidad
     LEFT JOIN inc_sla_politicas s ON s.IdSLA=t.IdSLA
     WHERE t.IdTicket={$id} LIMIT 1"
);
if (!$rows || !inc_can_view_ticket($rows[0])) inc_redirect('El ticket no existe o no puede consultarlo.', 'd');
$ticket = $rows[0];
$isAgent = inc_is_agent();
$timelineWhere = $isAgent ? '1=1' : 'EsPublico=1';
$timeline = find_by_sql("SELECT * FROM inc_seguimiento WHERE IdTicket={$id} AND {$timelineWhere} ORDER BY Fecha DESC,IdSeguimiento DESC");
$lastVisibleTrackingId = 0;
foreach ($timeline as $timelineRow) {
    $lastVisibleTrackingId = max($lastVisibleTrackingId, (int)$timelineRow['IdSeguimiento']);
}
if ($lastVisibleTrackingId > 0) {
    $readerId = (int)inc_user_id();
    $db->query("INSERT INTO inc_ticket_lecturas (IdTicket,UsuarioId,UltimoSeguimientoLeido,FechaLectura)
        VALUES ({$id},{$readerId},{$lastVisibleTrackingId},NOW())
        ON DUPLICATE KEY UPDATE
            UltimoSeguimientoLeido=GREATEST(UltimoSeguimientoLeido,VALUES(UltimoSeguimientoLeido)),
            FechaLectura=NOW()");
}
$attachments = find_by_sql("SELECT * FROM inc_adjuntos WHERE IdTicket={$id} AND Activo=1 ORDER BY Fecha DESC,IdAdjunto DESC");
$categories = $isAgent ? find_by_sql("SELECT * FROM inc_categorias ORDER BY Activa DESC,Nombre") : [];
$agents = $isAgent ? find_by_sql("SELECT id,name,username FROM users WHERE status=1 AND es_personal_ti=1 ORDER BY name") : [];

/* Monitoreo vinculado: tolera instalaciones sin los modulos opcionales. */
if (!function_exists('inc_trace_has_columns')) {
    function inc_trace_has_columns(string $table, array $columns): bool
    {
        global $db;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !$columns) return false;
        try {
            $tableSql = $db->escape($table);
            $columnSql = [];
            foreach (array_unique($columns) as $column) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$column)) return false;
                $columnSql[] = "'".$db->escape((string)$column)."'";
            }
            $rows = find_by_sql("SELECT COUNT(*) Cantidad
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$tableSql}'
                  AND COLUMN_NAME IN (".implode(',', $columnSql).")");
            return (int)($rows[0]['Cantidad'] ?? 0) === count($columnSql);
        } catch (Throwable $error) {
            return false;
        }
    }
}

$monitoringEvents = [];
$capacityAlerts = [];
$eventTraceAvailable = false;
$capacityTraceAvailable = false;
if ($isAgent) {
    $eventTraceAvailable = inc_trace_has_columns('evt_eventos', [
        'IdEvento','IdIncidencia','FechaEvento','Severidad','Titulo','Estado',
        'HostOrigen','IPOrigen','ArchivoOrigen','IdVM','IdMaquina'
    ]);
    if ($eventTraceAvailable) {
        try {
            $monitoringEvents = find_by_sql("SELECT e.IdEvento,e.FechaEvento,e.Severidad,e.Titulo,e.Estado,
                    e.HostOrigen,e.IPOrigen,e.ArchivoOrigen,
                    v.NombreVM,v.IP_VM,m.Host
                FROM evt_eventos e
                LEFT JOIN maquina_virtual v ON v.IdVM=e.IdVM
                LEFT JOIN maquina m ON m.IdMaquina=COALESCE(e.IdMaquina,v.IdMaquina)
                WHERE e.IdIncidencia={$id}
                ORDER BY e.FechaEvento DESC,e.IdEvento DESC
                LIMIT 100");
        } catch (Throwable $error) {
            $eventTraceAvailable = false;
            $monitoringEvents = [];
        }
    }
}

if ($isAgent) {
    $capacityTraceAvailable = inc_trace_has_columns('alerta_capacidad', [
        'IdAlerta','IdIncidencia','IdVM','IdHistorial','FechaAlerta','Recurso',
        'PorcentajeDetectado','NivelAlerta','Descripcion','EstadoAlerta'
    ]);
    if ($capacityTraceAvailable) {
        try {
            $hasCapacityImports = inc_trace_has_columns('importacion_log_capacidad', [
                'IdImportacion','ArchivoOriginal','IdVM','FechaMedicion'
            ]);
            $fileSelect = $hasCapacityImports
                ? "(SELECT i.ArchivoOriginal FROM importacion_log_capacidad i
                    WHERE i.IdVM=a.IdVM AND i.FechaMedicion=h.FechaEvaluacion
                    ORDER BY i.IdImportacion DESC LIMIT 1)"
                : 'NULL';
            $capacityAlerts = find_by_sql("SELECT a.IdAlerta,a.FechaAlerta,a.Recurso,a.PorcentajeDetectado,
                    a.NivelAlerta,a.Descripcion,a.EstadoAlerta,a.IdHistorial,
                    h.FechaEvaluacion,h.Observacion MedicionObservacion,
                    v.NombreVM,v.IP_VM,m.Host,{$fileSelect} ArchivoOrigen
                FROM alerta_capacidad a
                LEFT JOIN historial_capacidad h ON h.IdHistorial=a.IdHistorial
                LEFT JOIN maquina_virtual v ON v.IdVM=a.IdVM
                LEFT JOIN maquina m ON m.IdMaquina=v.IdMaquina
                WHERE a.IdIncidencia={$id}
                ORDER BY a.FechaAlerta DESC,a.IdAlerta DESC
                LIMIT 100");
        } catch (Throwable $error) {
            $capacityTraceAvailable = false;
            $capacityAlerts = [];
        }
    }
}

$activeStates = ['NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO'];
$isActive = in_array((string)$ticket['Estado'], $activeStates, true);
$isPaused = !empty($ticket['PausaSLAInicio']);
$dueTimestamp = strtotime((string)$ticket['FechaObjetivoResolucion']);
$registeredTimestamp = strtotime((string)$ticket['FechaRegistro']);
$endTimestamp = $isActive ? ($isPaused ? strtotime((string)$ticket['PausaSLAInicio']) : time()) : strtotime((string)($ticket['FechaResolucion'] ?: $ticket['FechaCierre'] ?: $ticket['updated_at']));
$totalWindow = max(1, $dueTimestamp - $registeredTimestamp);
$usedWindow = max(0, $endTimestamp - $registeredTimestamp);
$slaPercent = min(100, (int)round($usedWindow / $totalWindow * 100));
$slaExpired = $dueTimestamp > 0 && $endTimestamp > $dueTimestamp;
$slaLabel = $isPaused ? 'SLA en pausa' : ($slaExpired ? 'SLA vencido' : ($isActive ? 'Dentro del objetivo' : 'Atención finalizada'));
$slaRemaining = $isActive && !$isPaused ? (int)floor(($dueTimestamp - time()) / 60) : null;
$responseMinutes = $ticket['FechaPrimeraRespuesta'] ? (int)floor((strtotime((string)$ticket['FechaPrimeraRespuesta']) - $registeredTimestamp) / 60) : null;
$resolutionMinutes = $ticket['FechaResolucion'] ? (int)floor((strtotime((string)$ticket['FechaResolucion']) - $registeredTimestamp) / 60) : null;

$extraCss = [BASE_URL.'/assets/css/incidencias.css'];
include_once __DIR__.'/../../layouts/header.php';
?>

<section class="content inc-page"><div class="container-fluid pt-3 pb-4">
    <?= display_msg($msg) ?>
    <div class="inc-detail-banner mb-3">
        <div class="d-lg-flex justify-content-between align-items-center">
            <div>
                <div class="inc-hero-kicker"><?= inc_h(inc_type_label($ticket['Tipo'])) ?> · <?= inc_h($ticket['Canal']) ?></div>
                <h1 class="mb-1"><?= inc_h($ticket['Folio']) ?> · <?= inc_h($ticket['Titulo']) ?></h1>
                <div class="text-white-50">Registrado por <?= inc_h($ticket['SolicitanteNombre']) ?> el <?= inc_datetime($ticket['FechaRegistro']) ?></div>
            </div>
            <div class="mt-3 mt-lg-0 d-flex flex-wrap align-items-center">
                <span class="inc-badge inc-priority-<?= inc_h($ticket['Prioridad']) ?> mr-2"><?= inc_h(inc_priority_label($ticket['Prioridad'])) ?></span>
                <span class="inc-badge inc-status-<?= inc_h($ticket['Estado']) ?> mr-2"><?= inc_h(inc_status_label($ticket['Estado'])) ?></span>
                <button type="button" class="btn btn-light btn-sm mr-2" data-toggle="modal" data-target="#modalTratamiento"><i class="fas fa-clipboard-check mr-1 text-info"></i>Investigación y tratamiento</button><a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver a la bandeja</a>
            </div>
        </div>
    </div>

    <div class="inc-detail-grid">
        <div>
            <div class="inc-panel mb-3">
                <div class="inc-panel-header"><i class="fas fa-file-alt mr-2 text-info"></i>Información del ticket</div>
                <div class="inc-panel-body">
                    <div class="inc-meta-grid mb-3">
                        <div class="inc-meta"><span>Solicitante</span><strong><?= inc_h($ticket['SolicitanteNombre']) ?></strong><small><?= inc_h($ticket['SolicitanteCorreo'] ?: 'Sin correo') ?></small></div>
                        <div class="inc-meta"><span>Área</span><strong><?= inc_h($ticket['SolicitanteArea'] ?: 'Sin área registrada') ?></strong></div>
                        <div class="inc-meta"><span>Categoría</span><strong><?= inc_h($ticket['CategoriaNombre'] ?: 'Sin categoría') ?></strong></div>
                        <div class="inc-meta"><span>Aplicación / servicio TI afectado</span><strong><?= inc_h($ticket['ElementoAfectado'] ?: 'No especificado') ?></strong><small><?= inc_h($ticket['TipoElementoAfectado'] ?: '') ?><?= !empty($ticket['Ambiente']) ? ' · '.inc_h($ticket['Ambiente']) : '' ?></small></div><div class="inc-meta"><span>Equipo relacionado</span><strong><?= inc_h($ticket['Host'] ?: 'No aplica') ?></strong><small><?= inc_h($ticket['Ip'] ?: '') ?></small></div>
                        <div class="inc-meta"><span>Responsable TI</span><strong><?= inc_h($ticket['AsignadoNombre'] ?: 'Sin asignar') ?></strong></div>
                        <div class="inc-meta"><span>Última actualización</span><strong><?= inc_datetime($ticket['updated_at']) ?></strong></div>
                    </div>
                    <span class="inc-label">Descripción reportada</span>
                    <div class="inc-description"><?= inc_h($ticket['Descripcion']) ?></div>
                </div>
            </div>

            <?php if ($ticket['Resolucion'] || $ticket['CausaRaiz']): ?>
            <div class="inc-panel mb-3">
                <div class="inc-panel-header"><i class="fas fa-check-circle mr-2 text-success"></i>Diagnóstico y resolución</div>
                <div class="inc-panel-body"><div class="row">
                    <div class="col-md-6"><span class="inc-label">Solución aplicada</span><div class="inc-description"><?= inc_h($ticket['Resolucion'] ?: 'Pendiente') ?></div></div>
                    <div class="col-md-6"><span class="inc-label">Causa raíz / hallazgo</span><div class="inc-description"><?= inc_h($ticket['CausaRaiz'] ?: 'No documentada') ?></div></div>
                </div><?php if ($ticket['CodigoCierre']): ?><div class="mt-3"><span class="inc-badge inc-status-RESUELTO"><i class="fas fa-tag mr-1"></i><?= inc_h(str_replace('_',' ', $ticket['CodigoCierre'])) ?></span></div><?php endif; ?></div>
            </div>
            <?php endif; ?>

            <?php if ($isAgent): ?>
            <div class="inc-panel inc-origin-panel mb-3">
                <div class="inc-panel-header d-flex flex-wrap justify-content-between align-items-center">
                    <span><i class="fas fa-satellite-dish mr-2 text-info"></i>Origen y evidencia de monitoreo</span>
                    <span class="inc-origin-count"><?= count($monitoringEvents) + count($capacityAlerts) ?> registro(s) vinculados</span>
                </div>
                <div class="inc-panel-body">
                    <div class="inc-origin-flow mb-3">
                        <span><i class="fas fa-server"></i>Servidor</span><i class="fas fa-chevron-right"></i>
                        <span><i class="fas fa-file-code"></i>Log / medición</span><i class="fas fa-chevron-right"></i>
                        <span><i class="fas fa-bell"></i>Evento / alerta</span><i class="fas fa-chevron-right"></i>
                        <span class="is-current"><i class="fas fa-headset"></i><?= inc_h($ticket['Folio']) ?></span>
                    </div>
                    <?php if (!$monitoringEvents && !$capacityAlerts): ?>
                        <div class="inc-origin-empty"><i class="fas fa-link"></i><div><strong>Este ticket no tiene evidencia automática vinculada.</strong><span>Puede ser una incidencia registrada por un usuario o todavía no haber sido relacionada desde Eventos TI o Capacidad TI.</span></div></div>
                    <?php else:
                        $eventTabActive = !empty($monitoringEvents) || empty($capacityAlerts);
                    ?>
                        <ul class="nav nav-pills inc-origin-tabs mb-3" role="tablist">
                            <li class="nav-item"><a class="nav-link <?= $eventTabActive?'active':'' ?>" data-toggle="pill" href="#ticketMonitoringEvents" role="tab"><i class="fas fa-wave-square mr-1"></i>Eventos TI <span><?= count($monitoringEvents) ?></span></a></li>
                            <li class="nav-item"><a class="nav-link <?= !$eventTabActive?'active':'' ?>" data-toggle="pill" href="#ticketCapacityAlerts" role="tab"><i class="fas fa-chart-line mr-1"></i>Alertas de capacidad <span><?= count($capacityAlerts) ?></span></a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade <?= $eventTabActive?'show active':'' ?>" id="ticketMonitoringEvents" role="tabpanel">
                                <?php if ($monitoringEvents): ?><div class="inc-trace-list">
                                <?php foreach ($monitoringEvents as $trace):
                                    $severity = strtoupper((string)$trace['Severidad']);
                                    $severityClass = in_array($severity,['CRITICO','ERROR'],true) ? 'danger' : ($severity==='ADVERTENCIA' ? 'warning' : 'info');
                                    $originName = $trace['NombreVM'] ?: $trace['Host'] ?: $trace['HostOrigen'] ?: 'Equipo no identificado';
                                    $originIp = $trace['IPOrigen'] ?: $trace['IP_VM'] ?: '-';
                                ?>
                                    <article class="inc-trace-item">
                                        <span class="inc-trace-icon event"><i class="fas fa-wave-square"></i></span>
                                        <div class="inc-trace-main"><div class="d-flex flex-wrap align-items-center"><a href="../eventos_ti/detalle.php?id=<?= (int)$trace['IdEvento'] ?>" class="inc-trace-title mr-2">EVT-<?= str_pad((string)$trace['IdEvento'],7,'0',STR_PAD_LEFT) ?> · <?= inc_h($trace['Titulo']) ?></a><span class="badge badge-<?= $severityClass ?>"><?= inc_h($severity) ?></span></div><small><i class="fas fa-server mr-1"></i><?= inc_h($originName) ?> · <?= inc_h($originIp) ?></small><?php if($trace['ArchivoOrigen']):?><small><i class="fas fa-file-alt mr-1"></i><?= inc_h($trace['ArchivoOrigen']) ?></small><?php endif;?></div>
                                        <div class="inc-trace-meta"><strong><?= inc_datetime($trace['FechaEvento']) ?></strong><span><?= inc_h(str_replace('_',' ',$trace['Estado'])) ?></span><a href="../eventos_ti/detalle.php?id=<?= (int)$trace['IdEvento'] ?>">Ver evento <i class="fas fa-arrow-right ml-1"></i></a></div>
                                    </article>
                                <?php endforeach; ?></div><?php else: ?><div class="inc-origin-empty compact"><i class="fas fa-check-circle"></i><div><strong>Sin eventos vinculados</strong><span>No se relacionaron eventos puntuales con este ticket.</span></div></div><?php endif; ?>
                            </div>
                            <div class="tab-pane fade <?= !$eventTabActive?'show active':'' ?>" id="ticketCapacityAlerts" role="tabpanel">
                                <?php if ($capacityAlerts): ?><div class="inc-trace-list">
                                <?php foreach ($capacityAlerts as $trace):
                                    $level = strtoupper((string)$trace['NivelAlerta']);
                                    $levelClass = $level==='ROJO' ? 'danger' : 'warning';
                                ?>
                                    <article class="inc-trace-item">
                                        <span class="inc-trace-icon capacity"><i class="fas fa-chart-line"></i></span>
                                        <div class="inc-trace-main"><div class="d-flex flex-wrap align-items-center"><a href="../capacidad_ti/index.php#alertas" class="inc-trace-title mr-2">CAP-<?= str_pad((string)$trace['IdAlerta'],7,'0',STR_PAD_LEFT) ?> · <?= inc_h($trace['Recurso']) ?> al <?= number_format((float)$trace['PorcentajeDetectado'],2) ?>%</a><span class="badge badge-<?= $levelClass ?>"><?= inc_h($level) ?></span></div><small><i class="fas fa-cube mr-1"></i><?= inc_h($trace['NombreVM']?:'VM no identificada') ?> · <?= inc_h($trace['Host']?:'-') ?> · <?= inc_h($trace['IP_VM']?:'-') ?></small><?php if($trace['ArchivoOrigen']):?><small><i class="fas fa-file-alt mr-1"></i><?= inc_h($trace['ArchivoOrigen']) ?></small><?php endif;?><small class="inc-trace-description"><?= inc_h($trace['Descripcion']) ?></small></div>
                                        <div class="inc-trace-meta"><strong><?= inc_datetime($trace['FechaEvaluacion']?:$trace['FechaAlerta']) ?></strong><span><?= inc_h(str_replace('_',' ',$trace['EstadoAlerta'])) ?></span><a href="../capacidad_ti/index.php#alertas">Ver capacidad <i class="fas fa-arrow-right ml-1"></i></a></div>
                                    </article>
                                <?php endforeach; ?></div><?php else: ?><div class="inc-origin-empty compact"><i class="fas fa-check-circle"></i><div><strong>Sin alertas de capacidad</strong><span>No se relacionaron umbrales de CPU, RAM, disco o red con este ticket.</span></div></div><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="inc-panel mb-3">
                <div class="inc-panel-header d-flex justify-content-between"><span><i class="fas fa-paperclip mr-2 text-info"></i>Evidencias y documentos</span><span class="badge badge-info"><?= count($attachments) ?> archivo(s)</span></div>
                <div class="inc-panel-body">
                    <?php if ($attachments): ?><div class="inc-files">
                    <?php foreach ($attachments as $file):
                        $extension = strtolower(pathinfo((string)$file['NombreOriginal'], PATHINFO_EXTENSION));
                        $fileIcon = in_array($extension, ['jpg','jpeg','png','webp'], true) ? 'fa-image' : ($extension === 'pdf' ? 'fa-file-pdf' : (in_array($extension, ['xls','xlsx','csv'], true) ? 'fa-file-excel' : (in_array($extension, ['msg','eml'], true) ? 'fa-envelope' : 'fa-file-alt')));
                    ?>
                        <a class="inc-file" href="archivo.php?id=<?= (int)$file['IdAdjunto'] ?>" target="_blank" rel="noopener"><span class="inc-file-icon"><i class="fas <?= $fileIcon ?>"></i></span><span class="inc-file-text"><strong><?= inc_h($file['NombreOriginal']) ?></strong><small><?= number_format((int)$file['Tamano']/1024,1) ?> KB · <?= inc_datetime($file['Fecha']) ?></small></span></a>
                    <?php endforeach; ?>
                    </div><?php else: ?><div class="text-center text-muted py-3"><i class="far fa-folder-open fa-2x mb-2"></i><br>No se adjuntaron evidencias todavía.</div><?php endif; ?>
                </div>
            </div>

            <div class="inc-panel" id="actividad">
                <div class="inc-panel-header"><i class="fas fa-stream mr-2 text-info"></i>Actividad y comunicación</div>
                <div class="inc-panel-body">
                    <?php if ($isActive): ?>
                    <form method="post" action="acciones.php" enctype="multipart/form-data" class="mb-4">
                        <?= csrf_input() ?><input type="hidden" name="action" value="agregar_seguimiento"><input type="hidden" name="IdTicket" value="<?= $id ?>">
                        <div class="form-group"><label class="inc-label">Agregar seguimiento</label><textarea class="form-control inc-control" name="Comentario" rows="3" placeholder="Escriba una actualización, respuesta o información adicional..."></textarea></div>
                        <div class="row align-items-end"><div class="col-md-8"><label class="inc-label">Adjuntar evidencia</label><div class="custom-file"><input type="file" class="custom-file-input" id="trackingAttachments" name="adjuntos[]" multiple><label class="custom-file-label" for="trackingAttachments">Seleccionar archivos</label></div></div><div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <?php if ($isAgent): ?><div class="custom-control custom-checkbox text-left d-inline-block mr-3"><input type="checkbox" class="custom-control-input" id="internalNote" name="EsInterno" value="1"><label class="custom-control-label small" for="internalNote">Nota interna</label></div><?php endif; ?>
                            <button class="btn btn-info"><i class="fas fa-paper-plane mr-1"></i>Publicar</button>
                        </div></div>
                    </form>
                    <?php else: ?><div class="alert alert-secondary"><i class="fas fa-lock mr-2"></i>Ticket finalizado: historial en modo de solo lectura.</div><?php endif; ?>
                    <div class="inc-timeline">
                        <?php foreach ($timeline as $event): ?><div class="inc-event <?= !(int)$event['EsPublico']?'internal':'' ?>">
                            <div class="d-flex justify-content-between"><strong><?= inc_h($event['UsuarioNombre']) ?></strong><time><?= inc_datetime($event['Fecha']) ?></time></div>
                            <div class="mb-1"><span class="badge <?= !(int)$event['EsPublico']?'badge-warning':'badge-light' ?>"><?= inc_h(str_replace('_',' ', $event['Tipo'])) ?></span><?php if (!(int)$event['EsPublico']): ?><span class="badge badge-dark ml-1">Sólo TI</span><?php endif; ?></div>
                            <div class="inc-description"><?= inc_h($event['Comentario']) ?></div>
                        </div><?php endforeach; ?>
                        <?php if (!$timeline): ?><div class="text-muted">Aún no hay actividad registrada.</div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <div class="inc-sla-card mb-3">
                <div class="d-flex justify-content-between align-items-start"><div><div class="inc-hero-kicker">Compromiso de atención</div><h3 class="h5 font-weight-bold mb-1"><?= inc_h($ticket['SLANombre'] ?: 'Sin SLA') ?></h3></div><i class="fas fa-stopwatch fa-2x text-white-50"></i></div>
                <div class="d-flex justify-content-between mt-3 mb-1"><span><?= inc_h($slaLabel) ?></span><strong><?= $slaPercent ?>%</strong></div>
                <div class="inc-sla-track mb-3"><span style="width:<?= $slaPercent ?>%;background:<?= $slaExpired?'#ff7b73':'#efbd45' ?>"></span></div>
                <div class="row small"><div class="col-6"><span class="text-white-50 d-block">Objetivo</span><?= inc_datetime($ticket['FechaObjetivoResolucion']) ?></div><div class="col-6"><span class="text-white-50 d-block"><?= $slaRemaining !== null?'Tiempo restante':'Pausas acumuladas' ?></span><?= $slaRemaining !== null ? inc_h(($slaRemaining < 0 ? '-' : '').inc_duration(abs($slaRemaining))) : inc_duration($ticket['MinutosPausa']) ?></div></div>
                <hr style="border-color:rgba(255,255,255,.16)"><div class="row small"><div class="col-6"><span class="text-white-50 d-block">Primera respuesta</span><?= inc_duration($responseMinutes) ?></div><div class="col-6"><span class="text-white-50 d-block">Tiempo de resolución</span><?= inc_duration($resolutionMinutes) ?></div></div>
            </div>

            <?php if (!$isAgent && (string)$ticket['Estado'] === 'RESUELTO'): ?>
            <div class="inc-panel mb-3 border-success"><div class="inc-panel-header"><i class="fas fa-check-circle mr-2 text-success"></i>Confirmar solución</div><div class="inc-panel-body"><p class="small text-muted">Confirme si la solución de Soporte TI funcionó.</p><form method="post" action="acciones.php" class="mb-3"><?= csrf_input() ?><input type="hidden" name="action" value="confirmar_resolucion"><input type="hidden" name="IdTicket" value="<?= $id ?>"><input type="hidden" name="Decision" value="RESUELTO"><button class="btn btn-success btn-block">Sí, quedó resuelto</button></form><form method="post" action="acciones.php"><?= csrf_input() ?><input type="hidden" name="action" value="confirmar_resolucion"><input type="hidden" name="IdTicket" value="<?= $id ?>"><input type="hidden" name="Decision" value="NO_RESUELTO"><textarea class="form-control inc-control mb-2" name="Comentario" rows="3" minlength="10" required placeholder="Explique qué problema continúa..."></textarea><button class="btn btn-outline-danger btn-block">No, el problema continúa</button></form></div></div>
            <?php endif; ?>

            <?php if ($isAgent && tiene_permiso('incidencias', 'editar') && in_array((string)$ticket['Estado'], ['RESUELTO','PENDIENTE_USUARIO'], true)): ?>
            <?php $adminResolutionValue = $ticket['FechaResolucion'] ? date('Y-m-d\TH:i', strtotime((string)$ticket['FechaResolucion'])) : date('Y-m-d\TH:i'); ?>
            <div class="inc-panel mb-3 border-success">
                <div class="inc-panel-header"><i class="fas fa-user-shield mr-2 text-success"></i>Cierre administrativo</div>
                <div class="inc-panel-body">
                    <p class="small text-muted mb-3">Úselo cuando TI ya solucionó el caso y el usuario no confirma. El SLA toma la fecha real de solución; la fecha de cierre será la actual.</p>
                    <form method="post" action="acciones.php" onsubmit="return confirm('¿Confirma el cierre administrativo? Esta acción quedará en la trazabilidad.');">
                        <?= csrf_input() ?><input type="hidden" name="action" value="cerrar_administrativamente"><input type="hidden" name="IdTicket" value="<?= $id ?>">
                        <div class="form-group"><label class="inc-label">Fecha real de solución *</label><input type="datetime-local" class="form-control inc-control" name="FechaRealResolucion" value="<?= inc_h($adminResolutionValue) ?>" min="<?= inc_h(date('Y-m-d\TH:i', strtotime((string)$ticket['FechaRegistro']))) ?>" max="<?= inc_h(date('Y-m-d\TH:i')) ?>" required><small class="inc-help">Debe corresponder al momento comprobable en que TI terminó la solución.</small></div>
                        <div class="form-group"><label class="inc-label">Motivo del cierre *</label><textarea class="form-control inc-control" name="MotivoCierreAdministrativo" rows="3" minlength="10" required placeholder="Ej.: Solución aplicada y comunicada; el usuario no respondió la solicitud de confirmación."></textarea></div>
                        <button class="btn btn-success btn-block"><i class="fas fa-lock mr-1"></i>Cerrar administrativamente</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isAgent && (string)$ticket['Estado'] === 'PENDIENTE_USUARIO'): $calls=(int)($ticket['LlamadosUsuario'] ?? 0); $deadlineDue=!empty($ticket['FechaLimiteRespuesta']) && strtotime((string)$ticket['FechaLimiteRespuesta'])<=time(); ?>
            <div class="inc-panel mb-3 border-warning"><div class="inc-panel-header"><i class="fas fa-user-clock mr-2 text-warning"></i>Seguimiento al usuario</div><div class="inc-panel-body"><p><b>Llamados:</b> <?= $calls ?> de 3</p><?php if ($calls < 3): ?><form method="post" action="acciones.php"><?= csrf_input() ?><input type="hidden" name="action" value="registrar_llamado"><input type="hidden" name="IdTicket" value="<?= $id ?>"><button class="btn btn-warning btn-block">Registrar y enviar llamado <?= $calls+1 ?></button></form><?php elseif ($deadlineDue): ?><form method="post" action="acciones.php" onsubmit="return confirm('¿Cerrar por falta de respuesta?');"><?= csrf_input() ?><input type="hidden" name="action" value="cerrar_sin_respuesta"><input type="hidden" name="IdTicket" value="<?= $id ?>"><button class="btn btn-danger btn-block">Cerrar por falta de respuesta</button></form><?php else: ?><div class="alert alert-info py-2 mb-0">Cierre disponible después de <?= inc_datetime($ticket['FechaLimiteRespuesta']) ?>.</div><?php endif; ?></div></div>
            <?php endif; ?>
            <?php if ($isAgent && !in_array((string)$ticket['Estado'], ['RESUELTO','CERRADO','CANCELADO'], true)): ?>
            <div class="inc-panel mb-3">
                <div class="inc-panel-header"><i class="fas fa-tools mr-2 text-info"></i>Gestionar ticket</div>
                <div class="inc-panel-body">
                    <form method="post" action="acciones.php" id="managementForm">
                        <?= csrf_input() ?><input type="hidden" name="action" value="actualizar_ticket"><input type="hidden" name="IdTicket" value="<?= $id ?>">
                        <div class="form-group"><label class="inc-label">Responsable TI *</label><select class="form-control inc-control" name="AsignadoA"><option value="0">Sin asignar</option><?php foreach ($agents as $agent): ?><option value="<?= (int)$agent['id'] ?>" <?= (int)$ticket['AsignadoA']===(int)$agent['id']?'selected':'' ?>><?= inc_h($agent['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label class="inc-label">Estado *</label><select class="form-control inc-control" name="Estado"><?php foreach (['NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO','RESUELTO','CANCELADO'] as $state): ?><option value="<?= $state ?>" <?= $ticket['Estado']===$state?'selected':'' ?>><?= inc_h(inc_status_label($state)) ?></option><?php endforeach; ?></select><small class="inc-help">Pendiente de usuario o tercero pausa el SLA. Para cerrar un caso resuelto use el bloque de cierre administrativo.</small></div>
                        <div class="form-group"><label class="inc-label">Categoría *</label><select class="form-control inc-control" name="IdCategoria"><?php foreach ($categories as $category): ?><option value="<?= (int)$category['IdCategoria'] ?>" <?= (int)$ticket['IdCategoria']===(int)$category['IdCategoria']?'selected':'' ?>><?= inc_h($category['Nombre'].(!(int)$category['Activa']?' (inactiva)':'')) ?></option><?php endforeach; ?></select></div>
                        <div class="row"><div class="col-6"><div class="form-group"><label class="inc-label">Impacto</label><select class="form-control inc-control" name="Impacto"><?php for ($value=1;$value<=4;$value++): ?><option value="<?= $value ?>" <?= (int)$ticket['Impacto']===$value?'selected':'' ?>><?= $value ?></option><?php endfor; ?></select></div></div><div class="col-6"><div class="form-group"><label class="inc-label">Urgencia</label><select class="form-control inc-control" name="Urgencia"><?php for ($value=1;$value<=4;$value++): ?><option value="<?= $value ?>" <?= (int)$ticket['Urgencia']===$value?'selected':'' ?>><?= $value ?></option><?php endfor; ?></select></div></div></div>
                        <div class="form-group"><label class="inc-label">Solución aplicada</label><textarea class="form-control inc-control" name="Resolucion" rows="4" placeholder="Obligatoria para resolver o cerrar"><?= inc_h($ticket['Resolucion']) ?></textarea></div>
                        <div class="form-group"><label class="inc-label">Causa raíz / hallazgo</label><textarea class="form-control inc-control" name="CausaRaiz" rows="3"><?= inc_h($ticket['CausaRaiz']) ?></textarea></div>
                        <div class="form-group"><label class="inc-label">Código de cierre</label><select class="form-control inc-control" name="CodigoCierre"><option value="">Seleccione al finalizar...</option><?php foreach (['SOLUCION_DEFINITIVA'=>'Solución definitiva','SOLUCION_TEMPORAL'=>'Solución temporal','SOLICITUD_ATENDIDA'=>'Solicitud atendida','DUPLICADO'=>'Duplicado','NO_REPRODUCIBLE'=>'No reproducible'] as $code=>$label): ?><option value="<?= $code ?>" <?= $ticket['CodigoCierre']===$code?'selected':'' ?>><?= inc_h($label) ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label class="inc-label">Nota de gestión</label><textarea class="form-control inc-control" name="ComentarioGestion" rows="2" placeholder="Opcional; sólo visible para TI si no cambia el estado"></textarea></div>
                        <button class="btn btn-info btn-block"><i class="fas fa-save mr-1"></i>Guardar gestión</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ((string)$ticket['Estado'] === 'CERRADO' && !$isAgent): ?>
            <div class="inc-panel">
                <div class="inc-panel-header"><i class="fas fa-smile mr-2 text-warning"></i>Calidad de atención</div>
                <div class="inc-panel-body">
                    <form method="post" action="acciones.php"><?= csrf_input() ?><input type="hidden" name="action" value="registrar_satisfaccion"><input type="hidden" name="IdTicket" value="<?= $id ?>">
                        <label class="inc-label">¿Cómo califica la atención?</label><select class="form-control inc-control mb-2" name="Satisfaccion" required><option value="">Seleccione...</option><?php for ($score=5;$score>=1;$score--): ?><option value="<?= $score ?>" <?= (int)$ticket['Satisfaccion']===$score?'selected':'' ?>><?= $score ?> - <?= ['','Muy mala','Mala','Regular','Buena','Excelente'][$score] ?></option><?php endfor; ?></select>
                        <textarea class="form-control inc-control mb-2" name="ComentarioSatisfaccion" rows="2" placeholder="Comentario opcional"><?= inc_h($ticket['ComentarioSatisfaccion']) ?></textarea>
                        <button class="btn btn-outline-warning btn-block"><i class="fas fa-star mr-1"></i>Guardar calificación</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div></section>

<div class="modal fade inc-modal" id="modalTratamiento" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
<form method="post" action="acciones.php" id="formTratamiento"><?= csrf_input() ?><input type="hidden" name="action" value="guardar_tratamiento"><input type="hidden" name="IdTicket" value="<?= $id ?>">
<div class="modal-header"><div><div class="inc-hero-kicker">Ficha del supervisor</div><h5 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i>Investigación y tratamiento</h5></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><div class="alert alert-light border"><div class="row"><div class="col-md-3"><small class="text-muted d-block">Ticket</small><b><?= inc_h($ticket['Folio']) ?></b></div><div class="col-md-3"><small class="text-muted d-block">Clasificación</small><b><?= inc_h(inc_type_label($ticket['Tipo'])) ?></b></div><div class="col-md-3"><small class="text-muted d-block">Criticidad</small><b><?= inc_h(inc_priority_label($ticket['Prioridad'])) ?></b></div><div class="col-md-3"><small class="text-muted d-block">Responsable</small><b><?= inc_h($ticket['AsignadoNombre'] ?: 'Sin asignar') ?></b></div></div></div>
<div class="row"><div class="col-md-4 form-group"><label class="inc-label">Tipo de tratamiento *</label><select name="TipoTratamiento" class="form-control inc-control" required <?= !$isAgent?'disabled':'' ?>><option value="">Seleccione...</option><?php foreach (['CORRECTIVO'=>'Correctivo','PREVENTIVO'=>'Preventivo','CONTENCION'=>'Contención','MITIGACION'=>'Mitigación','MEJORA'=>'Mejora','OTRO'=>'Otro'] as $value=>$label): ?><option value="<?= $value ?>" <?= $ticket['TipoTratamiento']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 form-group"><label class="inc-label">Informe de tratamiento *</label><select name="InformeTratamiento" class="form-control inc-control" required <?= !$isAgent?'disabled':'' ?>><option value="">Seleccione...</option><option value="SI" <?= $ticket['InformeTratamiento']==='SI'?'selected':'' ?>>Sí</option><option value="NO" <?= $ticket['InformeTratamiento']==='NO'?'selected':'' ?>>No</option></select></div>
<div class="col-md-4 form-group"><label class="inc-label">SAC generada *</label><select name="SacGenerada" id="sacGenerada" class="form-control inc-control" required <?= !$isAgent?'disabled':'' ?>><option value="">Seleccione...</option><option value="SI" <?= $ticket['SacGenerada']==='SI'?'selected':'' ?>>Sí</option><option value="NO" <?= $ticket['SacGenerada']==='NO'?'selected':'' ?>>No</option><option value="NO_APLICA" <?= $ticket['SacGenerada']==='NO_APLICA'?'selected':'' ?>>No aplica</option></select></div></div>
<div class="row"><div class="col-md-4 form-group"><label class="inc-label">Código SAC</label><input name="CodigoSAC" id="codigoSAC" class="form-control inc-control" maxlength="60" value="<?= inc_h($ticket['CodigoSAC']) ?>" placeholder="Obligatorio cuando SAC = Sí" <?= !$isAgent?'readonly':'' ?>></div><div class="col-md-8 form-group"><label class="inc-label">Observaciones / lecciones aprendidas</label><textarea name="ObservacionesTratamiento" class="form-control inc-control" rows="4" maxlength="5000" <?= !$isAgent?'readonly':'' ?>><?= inc_h($ticket['ObservacionesTratamiento']) ?></textarea></div></div>
<?php if ($ticket['FechaTratamiento']): ?><div class="small text-muted"><i class="fas fa-history mr-1"></i>Última actualización: <?= inc_datetime($ticket['FechaTratamiento']) ?> por <?= inc_h($ticket['TratamientoActualizadoNombre'] ?: 'TI') ?></div><?php endif; ?></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button><?php if ($isAgent): ?><button class="btn btn-info"><i class="fas fa-save mr-1"></i>Guardar ficha</button><?php endif; ?></div>
</form></div></div></div>
<?php
$pageScripts = <<<'JS'
$(function () {
    function syncSacCode() {
        var enabled = $('#sacGenerada').val() === 'SI';
        $('#codigoSAC').prop('required', enabled).prop('disabled', !enabled);
        if (!enabled) $('#codigoSAC').val('');
    }
    $('#sacGenerada').on('change', syncSacCode);
    $('#modalTratamiento').on('shown.bs.modal', syncSacCode);
    $('#trackingAttachments').on('change', function () {
        var count = this.files ? this.files.length : 0;
        $(this).next('.custom-file-label').text(count ? count+' archivo(s) seleccionado(s)' : 'Seleccionar archivos');
    });
    $('#managementForm').on('submit', function (event) {
        var status = $(this).find('[name="Estado"]').val();
        if (status === 'CERRADO' || status === 'CANCELADO') {
            if (!window.confirm('Este cambio finaliza la atención del ticket. ¿Desea continuar?')) event.preventDefault();
        }
    });
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
