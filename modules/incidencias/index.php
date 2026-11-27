<?php
$page_title = 'Mesa de Ayuda TI';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';

inc_schema();
require_permiso('incidencias', 'ver');

$extraCss = [
    BASE_URL.'/assets/css/incidencias.css',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css',
];

$isAgent = inc_is_agent();
$scope = inc_ticket_scope_sql('t');
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = strtoupper(trim((string)($_GET['estado'] ?? '')));
$priorityFilter = strtoupper(trim((string)($_GET['prioridad'] ?? '')));
$typeFilter = strtoupper(trim((string)($_GET['tipo'] ?? '')));
$categoryFilter = (int)($_GET['categoria'] ?? 0);
$assigneeFilter = (int)($_GET['responsable'] ?? 0);
$viewerId = (int)inc_user_id();
$unreadSql = $isAgent ? '0' : "(SELECT COUNT(*) FROM inc_seguimiento sg
    WHERE sg.IdTicket=t.IdTicket
      AND sg.EsPublico=1
      AND sg.UsuarioId<>{$viewerId}
      AND sg.IdSeguimiento>COALESCE((
          SELECT tl.UltimoSeguimientoLeido
          FROM inc_ticket_lecturas tl
          WHERE tl.IdTicket=t.IdTicket AND tl.UsuarioId={$viewerId}
          LIMIT 1
      ),0))";

$where = [$scope];
if ($search !== '') {
    $term = $db->escape($search);
    $where[] = "(t.Folio LIKE '%{$term}%' OR t.Titulo LIKE '%{$term}%' OR t.Descripcion LIKE '%{$term}%' OR t.SolicitanteNombre LIKE '%{$term}%' OR t.AsignadoNombre LIKE '%{$term}%' OR m.Host LIKE '%{$term}%' OR ca.NombreAplicacion LIKE '%{$term}%' OR cs.NombreServicio LIKE '%{$term}%' OR sv.NombreServicio LIKE '%{$term}%' OR sv.CodigoServicio LIKE '%{$term}%')";
}
$validStates = ['NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO','RESUELTO','CERRADO','CANCELADO'];
if (in_array($statusFilter, $validStates, true)) $where[] = "t.Estado='".$db->escape($statusFilter)."'";
if (in_array($priorityFilter, ['CRITICA','ALTA','MEDIA','BAJA'], true)) $where[] = "t.Prioridad='".$db->escape($priorityFilter)."'";
if (in_array($typeFilter, ['INCIDENTE','SOLICITUD','EVENTO'], true)) $where[] = "t.Tipo='".$db->escape($typeFilter)."'";
if ($categoryFilter > 0) $where[] = "t.IdCategoria={$categoryFilter}";
if ($isAgent && $assigneeFilter > 0) $where[] = "t.AsignadoA={$assigneeFilter}";
if ($isAgent && isset($_GET['responsable']) && (string)$_GET['responsable'] === '0') $where[] = 't.AsignadoA IS NULL';

$tickets = find_by_sql(
    "SELECT t.*,c.Nombre CategoriaNombre,c.Icono CategoriaIcono,c.Color CategoriaColor,m.Host,m.Tipo_Activo,
             sv.CodigoServicio,sv.NombreServicio,sv.TipoServicio,sv.EstadoServicio,
             ca.NombreAplicacion,ca.TipoAplicacion,ca.Ambiente,
             cs.NombreServicio NombreServicioCapacidad,cs.Categoria CategoriaServicioCapacidad,
             COALESCE(ca.NombreAplicacion,cs.NombreServicio,sv.NombreServicio) ElementoAfectado,
             CASE WHEN ca.IdAplicacion IS NOT NULL THEN 'Aplicación' WHEN cs.IdServicio IS NOT NULL THEN 'Servicio TI' WHEN sv.IdServicio IS NOT NULL THEN 'Servicio anterior' ELSE '' END TipoElementoAfectado,
             s.Nombre SLANombre,s.MinutosRespuesta,s.MinutosResolucion,
             {$unreadSql} MensajesNuevos,
            CASE
              WHEN t.Estado IN ('RESUELTO','CERRADO','CANCELADO') THEN 'FINALIZADO'
              WHEN t.PausaSLAInicio IS NOT NULL THEN 'PAUSADO'
              WHEN NOW()>t.FechaObjetivoResolucion THEN 'VENCIDO'
              WHEN TIMESTAMPDIFF(MINUTE,NOW(),t.FechaObjetivoResolucion)<=120 THEN 'POR_VENCER'
              ELSE 'EN_TIEMPO' END SLAEstado
     FROM inc_tickets t
     LEFT JOIN inc_categorias c ON c.IdCategoria=t.IdCategoria
     LEFT JOIN inc_sla_politicas s ON s.IdSLA=t.IdSLA
     LEFT JOIN maquina m ON m.IdMaquina=t.IdMaquina
     LEFT JOIN servicios_ti sv ON sv.IdServicio=t.IdServicio
     LEFT JOIN aplicacion ca ON ca.IdAplicacion=t.IdAplicacionCapacidad
     LEFT JOIN servicio_ti cs ON cs.IdServicio=t.IdServicioCapacidad
     WHERE ".implode(' AND ', $where)."
     ORDER BY FIELD(t.Prioridad,'CRITICA','ALTA','MEDIA','BAJA'),
              FIELD(t.Estado,'NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO','RESUELTO','CERRADO','CANCELADO'),
              t.FechaRegistro DESC"
);
$unreadTotal = 0;
foreach ($tickets as $ticketRow) $unreadTotal += (int)($ticketRow['MensajesNuevos'] ?? 0);

$statRows = find_by_sql(
    "SELECT COUNT(*) Total,
            SUM(t.Estado IN ('NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO')) Abiertos,
            SUM(t.AsignadoA IS NULL AND t.Estado IN ('NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO')) SinAsignar,
            SUM(t.Estado IN ('NUEVO','EN_PROGRESO') AND NOW()>t.FechaObjetivoResolucion) Vencidos,
            SUM(t.Estado IN ('RESUELTO','CERRADO') AND YEAR(COALESCE(t.FechaResolucion,t.FechaCierre))=YEAR(CURDATE()) AND MONTH(COALESCE(t.FechaResolucion,t.FechaCierre))=MONTH(CURDATE())) ResueltosMes
     FROM inc_tickets t WHERE {$scope}"
);
$stats = $statRows[0] ?? ['Total'=>0,'Abiertos'=>0,'SinAsignar'=>0,'Vencidos'=>0,'ResueltosMes'=>0];
$categories = find_by_sql("SELECT * FROM inc_categorias WHERE Activa=1 ORDER BY Nombre");
$capacityApplications = find_by_sql("SELECT IdAplicacion,NombreAplicacion,TipoAplicacion,Ambiente,Criticidad FROM aplicacion WHERE EstadoAplicacion='ACTIVA' ORDER BY NombreAplicacion");
$capacityServices = find_by_sql("SELECT IdServicio,NombreServicio,Categoria,Criticidad,SLA FROM servicio_ti WHERE EstadoServicio='ACTIVO' ORDER BY NombreServicio");
$agents = find_by_sql("SELECT id,name,username FROM users WHERE status=1 AND es_personal_ti=1 ORDER BY name");
$users = $isAgent ? find_by_sql("SELECT id,name,username,correo,area FROM users WHERE status=1 AND tipo_registro='USUARIO' ORDER BY name") : [];
$ticketRequestKey = bin2hex(random_bytes(24));
$machines = $isAgent
    ? find_by_sql("SELECT IdMaquina,Host,Tipo_Activo,EstadoMaquina FROM maquina ORDER BY Host")
    : find_by_sql("SELECT m.IdMaquina,m.Host,m.Tipo_Activo,m.EstadoMaquina
        FROM maquina m
        INNER JOIN (SELECT IdMaquina,MAX(IdAsMaq) IdAsMaq FROM asig_maquina GROUP BY IdMaquina) ultima ON ultima.IdMaquina=m.IdMaquina
        INNER JOIN asig_maquina am ON am.IdAsMaq=ultima.IdAsMaq
        WHERE am.id=".(int)inc_user_id()." AND am.Estado=1
        ORDER BY m.Host");

include_once __DIR__.'/../../layouts/header.php';
?>

<section class="content inc-page">
<div class="container-fluid pt-3">
    <?= display_msg($msg) ?>

    <div class="inc-hero mb-3">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="inc-hero-kicker">Centro de soporte y continuidad</div>
                <h1><i class="fas fa-headset mr-2"></i>Mesa de Ayuda TI</h1>
                <p>Registre, priorice y acompañe cada incidente o solicitud hasta recuperar el servicio y confirmar la atención.</p>
            </div>
            <div class="col-lg-5">
                <div class="inc-hero-actions justify-content-lg-end">
                    <a href="reportes.php" class="btn btn-outline-light"><i class="fas fa-chart-line mr-1"></i>Indicadores</a>
                    <?php if (tiene_permiso('incidencias', 'editar')): ?><a href="configuracion.php" class="btn btn-outline-light"><i class="fas fa-sliders-h mr-1"></i>Configuración</a><?php endif; ?>
                    <?php if (tiene_permiso('incidencias', 'crear')): ?><button class="btn btn-warning" data-toggle="modal" data-target="#modalNuevoTicket"><i class="fas fa-plus-circle mr-1"></i>Nuevo ticket</button><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-6 col-xl-3 mb-3 mb-xl-0"><div class="inc-kpi inc-kpi-blue"><span class="inc-kpi-icon"><i class="fas fa-inbox"></i></span><strong><?= (int)$stats['Abiertos'] ?></strong><span>Tickets abiertos</span><small>Requieren seguimiento</small></div></div>
        <div class="col-6 col-xl-3 mb-3 mb-xl-0"><div class="inc-kpi inc-kpi-amber"><span class="inc-kpi-icon"><i class="fas fa-user-clock"></i></span><strong><?= (int)$stats['SinAsignar'] ?></strong><span>Sin responsable</span><small>Pendientes de asignación</small></div></div>
        <div class="col-6 col-xl-3"><div class="inc-kpi inc-kpi-red"><span class="inc-kpi-icon"><i class="fas fa-hourglass-end"></i></span><strong><?= (int)$stats['Vencidos'] ?></strong><span>SLA vencido</span><small>Casos activos fuera de plazo</small></div></div>
        <div class="col-6 col-xl-3"><div class="inc-kpi inc-kpi-teal"><span class="inc-kpi-icon"><i class="fas fa-check-double"></i></span><strong><?= (int)$stats['ResueltosMes'] ?></strong><span>Resueltos este mes</span><small><?= (int)$stats['Total'] ?> tickets históricos</small></div></div>
    </div>

    <form method="get" class="inc-toolbar mb-3" id="ticketFilters">
        <div class="row align-items-end">
            <div class="col-lg-4 col-md-6 mb-2">
                <label class="inc-label">Buscar en la mesa de ayuda</label>
                <div class="inc-search"><i class="fas fa-search"></i><input class="form-control inc-control" name="q" value="<?= inc_h($search) ?>" placeholder="Folio, asunto, usuario, técnico o equipo"></div>
            </div>
            <div class="col-lg-2 col-md-3 col-6 mb-2"><label class="inc-label">Estado</label><select class="form-control inc-control" name="estado"><option value="">Todos</option><?php foreach ($validStates as $state): ?><option value="<?= $state ?>" <?= $statusFilter===$state?'selected':'' ?>><?= inc_h(inc_status_label($state)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-3 col-6 mb-2"><label class="inc-label">Prioridad</label><select class="form-control inc-control" name="prioridad"><option value="">Todas</option><?php foreach (['CRITICA','ALTA','MEDIA','BAJA'] as $priority): ?><option value="<?= $priority ?>" <?= $priorityFilter===$priority?'selected':'' ?>><?= inc_h(inc_priority_label($priority)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-4 col-6 mb-2"><label class="inc-label">Tipo</label><select class="form-control inc-control" name="tipo"><option value="">Todos</option><option value="INCIDENTE" <?= $typeFilter==='INCIDENTE'?'selected':'' ?>>Incidente</option><option value="SOLICITUD" <?= $typeFilter==='SOLICITUD'?'selected':'' ?>>Solicitud</option><option value="EVENTO" <?= $typeFilter==='EVENTO'?'selected':'' ?>>Evento</option></select></div>
            <div class="col-lg-2 col-md-4 col-6 mb-2"><label class="inc-label">Categoría</label><select class="form-control inc-control" name="categoria"><option value="0">Todas</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['IdCategoria'] ?>" <?= $categoryFilter===(int)$category['IdCategoria']?'selected':'' ?>><?= inc_h($category['Nombre']) ?></option><?php endforeach; ?></select></div>
            <?php if ($isAgent): ?><div class="col-lg-3 col-md-4 mb-2"><label class="inc-label">Responsable TI</label><select class="form-control inc-control" name="responsable"><option value="">Todos</option><option value="0" <?= isset($_GET['responsable']) && (string)$_GET['responsable']==='0'?'selected':'' ?>>Sin asignar</option><?php foreach ($agents as $agent): ?><option value="<?= (int)$agent['id'] ?>" <?= $assigneeFilter===(int)$agent['id']?'selected':'' ?>><?= inc_h($agent['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <div class="col-lg-3 col-md-6 mb-2 d-flex"><button class="btn btn-info flex-grow-1 mr-2"><i class="fas fa-filter mr-1"></i>Aplicar filtros</button><a href="index.php" class="btn btn-outline-secondary" title="Limpiar"><i class="fas fa-eraser"></i></a></div>
        </div>
    </form>

    <div class="card inc-table-card mb-4">
        <div class="inc-table-head">
            <div><h2><i class="fas fa-list-ul mr-2 text-info"></i>Bandeja de tickets <?php if ($unreadTotal > 0): ?><span class="inc-unread-total"><i class="fas fa-bell"></i><?= $unreadTotal ?> mensaje(s) nuevo(s)</span><?php endif; ?></h2><small class="text-muted"><?= count($tickets) ?> resultado(s) con los filtros actuales<?php if ($unreadTotal > 0): ?> &middot; Abra el ticket para marcar sus mensajes como le&iacute;dos<?php endif; ?></small></div>
            <div class="inc-row-legend"><span><i class="inc-dot inc-dot-new"></i>Sin atender</span><span><i class="inc-dot inc-dot-work"></i>En atención</span><span><i class="inc-dot inc-dot-done"></i>Finalizado</span></div>
        </div>
        <div class="table-responsive">
            <table class="table inc-table mb-0" id="tblIncidencias">
                <thead><tr><th>Folio</th><th>Ticket</th><th>Solicitante</th><th>Clasificación</th><th>Prioridad</th><th>Responsable</th><th>Estado</th><th>SLA</th><th>Actualizado</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $ticket):
                    $slaState = (string)$ticket['SLAEstado'];
                    $slaClass = $slaState === 'VENCIDO' ? 'inc-sla-danger' : ($slaState === 'POR_VENCER' ? 'inc-sla-warning' : 'inc-sla-ok');
                    $slaText = ['VENCIDO'=>'Vencido','POR_VENCER'=>'Por vencer','PAUSADO'=>'En pausa','FINALIZADO'=>'Finalizado','EN_TIEMPO'=>'En tiempo'][$slaState] ?? $slaState;
                ?>
                    <tr class="inc-ticket-row inc-row-<?= inc_h($ticket['Estado']) ?> <?= empty($ticket['AsignadoA']) && $ticket['Estado']==='NUEVO' ? 'inc-row-unattended' : '' ?> <?= (int)$ticket['MensajesNuevos'] > 0 ? 'inc-row-unread' : '' ?>">
                        <td><a class="inc-folio" href="detalle.php?id=<?= (int)$ticket['IdTicket'] ?>"><?= inc_h($ticket['Folio']) ?></a><?php if ((int)$ticket['MensajesNuevos'] > 0): ?><span class="inc-unread-badge"><i class="fas fa-comment-dots"></i><?= (int)$ticket['MensajesNuevos'] ?> nuevo<?= (int)$ticket['MensajesNuevos'] === 1 ? '' : 's' ?></span><?php endif; ?><span class="inc-subtext"><?= inc_h(inc_type_label($ticket['Tipo'])) ?></span></td>
                        <td style="min-width:230px"><a class="inc-ticket-title" href="detalle.php?id=<?= (int)$ticket['IdTicket'] ?>"><?= inc_h($ticket['Titulo']) ?></a><?php if ((int)$ticket['MensajesNuevos'] > 0): ?><span class="inc-unread-hint"><i class="fas fa-bell mr-1"></i>TI escribi&oacute; una nueva respuesta</span><?php endif; ?><span class="inc-subtext"><?= inc_h($ticket['ElementoAfectado'] ?: 'Sin aplicación o servicio asociado') ?><?php if (!empty($ticket['TipoElementoAfectado'])): ?> · <?= inc_h($ticket['TipoElementoAfectado']) ?><?php endif; ?><?php if (!empty($ticket['Host'])): ?><span class="inc-subtext"><i class="fas fa-desktop mr-1"></i><?= inc_h($ticket['Host']) ?></span><?php endif; ?></span></td>
                        <td><?= inc_h($ticket['SolicitanteNombre']) ?><span class="inc-subtext"><?= inc_h($ticket['SolicitanteArea'] ?: 'Sin área') ?></span></td>
                        <td><?= inc_h($ticket['CategoriaNombre'] ?: 'Sin categoría') ?><span class="inc-subtext"><?= inc_h($ticket['Canal']) ?></span></td>
                        <td><span class="inc-badge inc-priority-<?= inc_h($ticket['Prioridad']) ?>"><?= inc_h(inc_priority_label($ticket['Prioridad'])) ?></span></td>
                        <td><?= inc_h($ticket['AsignadoNombre'] ?: 'Sin asignar') ?></td>
                        <td><span class="inc-badge inc-status-<?= inc_h($ticket['Estado']) ?>"><?= inc_h(inc_status_label($ticket['Estado'])) ?></span></td>
                        <td><span class="font-weight-bold <?= $slaClass ?>"><i class="fas fa-stopwatch mr-1"></i><?= inc_h($slaText) ?></span><span class="inc-subtext"><?= inc_datetime($ticket['FechaObjetivoResolucion']) ?></span></td>
                        <td data-order="<?= inc_h($ticket['updated_at']) ?>"><?= inc_datetime($ticket['updated_at']) ?></td>
                        <td><a href="detalle.php?id=<?= (int)$ticket['IdTicket'] ?>" class="btn btn-sm btn-outline-info inc-action" title="Abrir ticket"><i class="fas fa-arrow-right"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>

<?php if (tiene_permiso('incidencias', 'crear')): ?>
<div class="modal fade inc-modal" id="modalNuevoTicket" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
    <form method="post" action="acciones.php" enctype="multipart/form-data" id="formNuevoTicket">
        <?= csrf_input() ?><input type="hidden" name="action" value="crear_ticket"><input type="hidden" name="ClaveSolicitud" value="<?= inc_h($ticketRequestKey) ?>">

        <div class="modal-header">
            <div><div class="inc-hero-kicker">Nuevo contacto con la Mesa de Ayuda</div><h5 class="modal-title mb-0"><i class="fas fa-life-ring mr-2"></i>Registrar ticket</h5></div>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-lg-7 mb-3 mb-lg-0"><div class="inc-form-panel">
                    <div class="inc-section-title"><i class="fas fa-comment-alt"></i>¿Qué necesita atender TI?</div>
                    <div class="form-group"><label class="inc-label">Asunto *</label><input class="form-control inc-control" name="Titulo" maxlength="180" required placeholder="Ej.: No puedo ingresar a SAP desde mi equipo"></div>
                    <div class="form-group"><label class="inc-label">Descripción detallada *</label><textarea class="form-control inc-control" name="Descripcion" rows="7" minlength="10" required placeholder="Indique qué ocurrió, desde cuándo, qué mensaje aparece y cómo afecta su trabajo."></textarea><small class="inc-help">Mientras más concreto sea el detalle, más rápido podrá diagnosticarse el caso.</small></div>
                    <div class="row"><div class="col-md-6"><div class="form-group"><label class="inc-label">Categoría *</label><select class="form-control inc-control select2-inc" name="IdCategoria" id="ticketCategory" data-placeholder="Buscar categoría..." required><option value=""></option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['IdCategoria'] ?>" data-code="<?= inc_h($category['Codigo']) ?>"><?= inc_h($category['Nombre']) ?></option><?php endforeach; ?></select></div></div><div class="col-md-6"><div class="form-group"><label class="inc-label"><i class="fas fa-layer-group mr-1 text-info"></i>Aplicación o servicio TI afectado</label><select class="form-control inc-control select2-inc" name="ElementoAfectado" data-placeholder="Buscar SAP, Internet, correo o aplicación..."><option value="">Ninguno / no identificado</option><?php if ($capacityApplications): ?><optgroup label="Aplicaciones de la empresa"><?php foreach ($capacityApplications as $application): ?><option value="APP:<?= (int)$application['IdAplicacion'] ?>"><?= inc_h($application['NombreAplicacion'].($application['Ambiente'] ? ' / '.$application['Ambiente'] : '').($application['Criticidad'] ? ' / '.$application['Criticidad'] : '')) ?></option><?php endforeach; ?></optgroup><?php endif; ?><?php if ($capacityServices): ?><optgroup label="Servicios TI entregados al usuario"><?php foreach ($capacityServices as $service): ?><option value="SRV:<?= (int)$service['IdServicio'] ?>"><?= inc_h($service['NombreServicio'].($service['Categoria'] ? ' / '.$service['Categoria'] : '').($service['Criticidad'] ? ' / '.$service['Criticidad'] : '')) ?></option><?php endforeach; ?></optgroup><?php endif; ?></select><small class="inc-help">Seleccione lo que el colaborador utiliza y presenta la falla. El catálogo se administra en Gestión de Capacidad.</small></div></div></div><div class="form-group"><label class="inc-label"><i class="fas fa-desktop mr-1 text-info"></i>Mi equipo relacionado</label><select class="form-control inc-control select2-inc" name="IdMaquina" data-placeholder="Buscar mi equipo asignado..."><option value="0">Ningún equipo específico</option><?php foreach ($machines as $machine): ?><option value="<?= (int)$machine['IdMaquina'] ?>"><?= inc_h($machine['Host'].' / '.$machine['Tipo_Activo'].' / '.$machine['EstadoMaquina']) ?></option><?php endforeach; ?></select><small class="inc-help"><?= $isAgent ? 'Como administrador puede seleccionar cualquier activo.' : 'Sólo aparecen los activos actualmente asignados a su usuario.' ?></small></div>
                    <div class="form-group mb-0"><label class="inc-label">Evidencias</label><div class="custom-file"><input type="file" class="custom-file-input" id="newAttachments" name="adjuntos[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.msg,.eml"><label class="custom-file-label" for="newAttachments">Seleccione hasta 8 archivos</label></div><small class="inc-help">Imágenes, PDF, Office, MSG o EML. Máximo 15 MB por archivo.</small></div>
                </div></div>
                <div class="col-lg-5"><div class="inc-form-panel">
                    <div class="inc-section-title"><i class="fas fa-route"></i>Clasificación y atención</div>
                    <?php if ($isAgent): ?><div class="form-group"><label class="inc-label">Solicitante *</label><select class="form-control inc-control select2-inc" name="SolicitanteId" required><?php foreach ($users as $optionUser): ?><option value="<?= (int)$optionUser['id'] ?>" <?= (int)$optionUser['id']===inc_user_id()?'selected':'' ?>><?= inc_h($optionUser['name'].' / '.$optionUser['area']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
                    <div class="row"><div class="col-6"><div class="form-group"><label class="inc-label d-flex justify-content-between">Tipo * <button type="button" class="btn btn-link btn-sm p-0" id="openTicketTypeHelp"><i class="fas fa-question-circle mr-1"></i>¿Cuál elegir?</button></label><select class="form-control inc-control" name="Tipo" id="ticketType"><option value="INCIDENTE">Incidente</option><option value="SOLICITUD">Solicitud</option><option value="EVENTO">Evento</option></select></div></div><div class="col-6"><div class="form-group"><label class="inc-label">Canal *</label><select class="form-control inc-control" name="Canal"><option value="PORTAL">Portal</option><?php if ($isAgent): ?><option value="CORREO">Correo</option><option value="TELEFONO">Teléfono</option><option value="PRESENCIAL">Presencial</option><option value="MONITOREO">Monitoreo</option><?php endif; ?></select></div></div></div>
                    <div class="row"><div class="col-6"><div class="form-group"><label class="inc-label">Impacto *</label><select class="form-control inc-control priority-input" name="Impacto"><option value="1">1 - Individual menor</option><option value="2" selected>2 - Un usuario</option><option value="3">3 - Área o servicio</option><option value="4">4 - Organización</option></select></div></div><div class="col-6"><div class="form-group"><label class="inc-label">Urgencia *</label><select class="form-control inc-control priority-input" name="Urgencia"><option value="1">1 - Puede esperar</option><option value="2" selected>2 - Normal</option><option value="3">3 - Urgente</option><option value="4">4 - Inmediata</option></select></div></div></div>
                    <div class="inc-priority-preview mb-3"><div><span class="inc-label mb-1">Prioridad calculada</span><small class="inc-help">Impacto × urgencia</small></div><strong id="priorityPreview" class="inc-badge inc-priority-MEDIA">Media</strong></div>
                    <?php if ($isAgent): ?><div class="form-group mb-0"><label class="inc-label">Asignar ahora (opcional)</label><select class="form-control inc-control select2-inc" name="AsignadoA"><option value="0">Dejar en cola sin asignar</option><?php foreach ($agents as $agent): ?><option value="<?= (int)$agent['id'] ?>"><?= inc_h($agent['name'].' / '.$agent['username']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
                </div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info px-4"><i class="fas fa-paper-plane mr-1"></i>Registrar ticket</button></div>
    </form>
</div></div>
</div>
<div class="modal fade" id="modalTiposTicket" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
<div class="modal-header bg-info text-white"><div><small class="text-white-50">Guía rápida</small><h5 class="modal-title"><i class="fas fa-compass mr-2"></i>¿Qué tipo de ticket debo registrar?</h5></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><p class="text-muted">Seleccione según lo que necesita comunicar a TI. La prioridad se calcula aparte mediante impacto y urgencia.</p><div class="row">
<div class="col-md-4 mb-3"><div class="inc-form-panel h-100"><span class="inc-badge inc-priority-ALTA">Incidente</span><h6 class="font-weight-bold mt-3">Algo dejó de funcionar</h6><p class="small text-muted">Falla que afecta un servicio, aplicación o equipo.</p><small><b>Ejemplos:</b> SAP no abre, internet caído, equipo bloqueado.</small></div></div>
<div class="col-md-4 mb-3"><div class="inc-form-panel h-100"><span class="inc-badge inc-priority-MEDIA">Solicitud</span><h6 class="font-weight-bold mt-3">Necesito algo de TI</h6><p class="small text-muted">Petición planificada que no parte de una falla.</p><small><b>Ejemplos:</b> crear acceso, instalar software, entregar equipo.</small></div></div>
<div class="col-md-4 mb-3"><div class="inc-form-panel h-100"><span class="inc-badge inc-status-ASIGNADO">Evento</span><h6 class="font-weight-bold mt-3">Observé una señal o alerta</h6><p class="small text-muted">Hecho detectado por una persona, servidor o herramienta que debe evaluar TI.</p><small><b>Ejemplos:</b> reinicio inesperado, alerta de seguridad, log crítico. Si ya interrumpió el servicio, use Incidente.</small></div></div>
</div><div class="alert alert-light border mb-0"><i class="fas fa-info-circle text-info mr-2"></i>Los logs escalados desde Gestión de Eventos TI se registran automáticamente como <b>Evento</b> y categoría <b>Eventos y monitoreo</b>.</div></div>
<div class="modal-footer"><button type="button" class="btn btn-info" data-dismiss="modal"><i class="fas fa-check mr-1"></i>Entendido</button></div>
</div></div></div>
<?php endif; ?>

<?php
$pageScripts = <<<'JS'
$(function () {
    var table = $('#tblIncidencias').DataTable({
        language: DATATABLES_SPANISH,
        pageLength: 20,
        lengthMenu: [[10,20,30,50],[10,20,30,50]],
        order: [],
        searching: false,
        responsive: false,
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: [9] }]
    });
    $('.select2-inc').each(function(){ $(this).select2({theme:'bootstrap4',width:'100%',dropdownParent:$('#modalNuevoTicket'),placeholder:$(this).data('placeholder')||'Buscar y seleccionar...',allowClear:!$(this).prop('required'),language:{noResults:function(){return 'No se encontraron resultados';},searching:function(){return 'Buscando...';}}}); });
    $('#ticketType').on('change', function () {
        if ($(this).val() === 'EVENTO') {
            var option = $('#ticketCategory option[data-code="EVT"]');
            if (option.length) $('#ticketCategory').val(option.val()).trigger('change');
        }
    });
    var returnToTicketForm = false;
    $('#openTicketTypeHelp').on('click', function () { returnToTicketForm = true; $('#modalNuevoTicket').modal('hide'); });
    $('#modalNuevoTicket').on('hidden.bs.modal', function () { if (returnToTicketForm) $('#modalTiposTicket').modal('show'); });
    $('#modalTiposTicket').on('hidden.bs.modal', function () { if (returnToTicketForm) { returnToTicketForm = false; $('#modalNuevoTicket').modal('show'); } });
    $('.priority-input').on('change', function () {
        var impact = parseInt($('[name="Impacto"]').val(), 10) || 1;
        var urgency = parseInt($('[name="Urgencia"]').val(), 10) || 1;
        var score = impact * urgency;
        var priority = score >= 12 ? 'CRITICA' : (score >= 8 ? 'ALTA' : (score >= 4 ? 'MEDIA' : 'BAJA'));
        var labels = {CRITICA:'Crítica',ALTA:'Alta',MEDIA:'Media',BAJA:'Baja'};
        $('#priorityPreview').attr('class','inc-badge inc-priority-'+priority).text(labels[priority]);
    });
    $('#formNuevoTicket').on('submit', function (event) {
        var form = this;
        var button = $(form).find('button[type="submit"]');
        if ($(form).data('submitting')) {
            event.preventDefault();
            return false;
        }
        $(form).data('submitting', true);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Registrando ticket...');
    });
    $('#newAttachments').on('change', function () {
        var count = this.files ? this.files.length : 0;
        $(this).next('.custom-file-label').text(count ? count+' archivo(s) seleccionado(s)' : 'Seleccione hasta 8 archivos');
    });
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
