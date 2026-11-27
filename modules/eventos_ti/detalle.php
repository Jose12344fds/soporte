<?php
declare(strict_types=1);

$page_title = 'Detalle del evento';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';
evt_schema();
require_permiso('eventos_ti', 'ver');

$id = (int)($_GET['id'] ?? 0);
$rows = find_by_sql("SELECT e.*,v.NombreVM,v.SistemaOperativo,v.IP_VM,m.Host,m.Tipo_Activo,a.NombreAplicacion,a.Ambiente,
    t.Folio,t.Titulo TituloIncidencia,t.Estado EstadoIncidencia,t.Prioridad PrioridadIncidencia,u.name RevisadoPorNombre
    FROM evt_eventos e
    LEFT JOIN maquina_virtual v ON v.IdVM=e.IdVM
    LEFT JOIN maquina m ON m.IdMaquina=COALESCE(e.IdMaquina,v.IdMaquina)
    LEFT JOIN aplicacion a ON a.IdAplicacion=e.IdAplicacion
    LEFT JOIN inc_tickets t ON t.IdTicket=e.IdIncidencia
    LEFT JOIN users u ON u.id=e.RevisadoPor
    WHERE e.IdEvento={$id} LIMIT 1");
if (!$rows) evt_redirect('El evento solicitado no existe.', 'd');
$event = $rows[0];
$related = find_by_sql("SELECT e.IdEvento,e.FechaEvento,e.Severidad,e.Titulo,e.Estado,t.Folio FROM evt_eventos e LEFT JOIN inc_tickets t ON t.IdTicket=e.IdIncidencia WHERE e.ClaveAgrupacion='".$db->escape($event['ClaveAgrupacion'])."' AND e.IdEvento<>{$id} ORDER BY e.FechaEvento DESC LIMIT 12");
$tickets = find_by_sql("SELECT IdTicket,Folio,Titulo,Prioridad,Estado FROM inc_tickets WHERE Estado NOT IN ('CERRADO','CANCELADO') ORDER BY FechaRegistro DESC LIMIT 250");
$canEdit = tiene_permiso('eventos_ti','editar');
$canCreateIncident = tiene_permiso('incidencias','crear');
$extraCss = [BASE_URL.'/assets/css/eventos_ti.css'];
include_once __DIR__.'/../../layouts/header.php';
?>

<section class="content-header pb-1"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><h1 class="h4 mb-0"><i class="fas fa-wave-square mr-2 text-info"></i>Detalle del evento</h1><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="index.php">Eventos TI</a></li><li class="breadcrumb-item active">EVT-<?=str_pad((string)$id,7,'0',STR_PAD_LEFT)?></li></ol></div></div></section>
<section class="content"><div class="container-fluid">
    <div class="card evt-panel mb-3"><div class="card-header"><div class="evt-detail-head"><div class="evt-detail-title"><div><span class="evt-severity <?=evt_h($event['Severidad'])?>"><?=evt_severity_label($event['Severidad'])?></span><span class="evt-state <?=evt_h($event['Estado'])?> ml-1"><?=evt_status_label($event['Estado'])?></span></div><h2><?=evt_h($event['Titulo'])?></h2><small class="text-muted">EVT-<?=str_pad((string)$id,7,'0',STR_PAD_LEFT)?> · <?=date('d/m/Y H:i:s',strtotime($event['FechaEvento']))?></small></div><div class="evt-detail-actions"><a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a><?php if($event['IdIncidencia']):?><a href="../incidencias/detalle.php?id=<?=(int)$event['IdIncidencia']?>" class="btn btn-primary btn-sm"><i class="fas fa-headset mr-1"></i>Abrir <?=evt_h($event['Folio'])?></a><?php elseif($canCreateIncident):?><form method="post" action="acciones.php" class="d-inline"><?=csrf_input()?><input type="hidden" name="action" value="crear_incidencia"><input type="hidden" name="IdEvento" value="<?=$id?>"><button class="btn btn-danger btn-sm" onclick="return confirm('Este evento técnico se convertirá en un ticket tipo Incidente para su atención. ¿Desea continuar?')"><i class="fas fa-bolt mr-1"></i>Crear ticket de incidencia</button></form><?php endif;?></div></div></div></div>

    <div class="row"><div class="col-xl-8">
        <div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle mr-2"></i><b>Evento versus incidencia:</b> este registro conserva la evidencia automática del log. Use <b>Crear ticket de incidencia</b> solamente si el evento produjo una falla, interrupción o necesita una acción de Soporte TI. El sistema reutiliza una incidencia abierta del mismo problema para evitar tickets duplicados.</div><div class="card evt-panel mb-3"><div class="card-header"><h3 class="card-title"><i class="fas fa-file-code mr-2 text-info"></i>Evidencia técnica</h3></div><div class="card-body"><div class="evt-message"><?=evt_h($event['Mensaje'])?></div><?php if($event['ArchivoOrigen']):?><small class="d-block text-muted mt-2"><i class="fas fa-file mr-1"></i>Archivo: <?=evt_h($event['ArchivoOrigen'])?></small><?php endif;?></div></div>
        <div class="card evt-panel mb-3"><div class="card-header"><h3 class="card-title">Ocurrencias relacionadas</h3><small class="float-right text-muted">Mismo origen, código y título normalizado</small></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover evt-table mb-0"><thead><tr><th>Fecha</th><th>Severidad</th><th>Título</th><th>Estado</th><th>Incidencia</th><th></th></tr></thead><tbody><?php foreach($related as $item):?><tr><td><?=date('d/m/Y H:i:s',strtotime($item['FechaEvento']))?></td><td><span class="evt-severity <?=evt_h($item['Severidad'])?>"><?=evt_severity_label($item['Severidad'])?></span></td><td><?=evt_h($item['Titulo'])?></td><td><?=evt_status_label($item['Estado'])?></td><td><?=evt_h($item['Folio']?:'-')?></td><td><a href="detalle.php?id=<?=(int)$item['IdEvento']?>" class="btn btn-outline-info btn-sm"><i class="fas fa-eye"></i></a></td></tr><?php endforeach;?></tbody></table><?php if(!$related):?><div class="evt-empty py-4"><i class="fas fa-check-circle"></i>No existen otras ocurrencias de este problema.</div><?php endif;?></div></div></div>
    </div><div class="col-xl-4">
        <div class="card evt-panel mb-3"><div class="card-header"><h3 class="card-title">Contexto</h3></div><div class="card-body"><dl class="evt-facts"><dt>Fuente</dt><dd><?=evt_h($event['Fuente'])?></dd><dt>Proveedor</dt><dd><?=evt_h($event['Proveedor']?:'-')?></dd><dt>Código</dt><dd><span class="evt-code"><?=evt_h($event['CodigoEvento']?:'-')?></span></dd><dt>Categoría</dt><dd><?=evt_h($event['Categoria'])?></dd><dt>VM</dt><dd><?=evt_h($event['NombreVM']?:'-')?></dd><dt>Servidor físico</dt><dd><?=evt_h($event['Host']?:'-')?></dd><dt>IP informada</dt><dd><?=evt_h($event['IPOrigen']?:$event['IP_VM']?:'-')?></dd><dt>Aplicación</dt><dd><?=evt_h($event['NombreAplicacion']?:'-')?></dd><dt>Canal</dt><dd><?=evt_h($event['Canal'])?></dd><dt>Requiere incidencia</dt><dd><?=$event['RequiereIncidencia']?'<span class="badge badge-danger">Sí, evaluar</span>':'<span class="badge badge-light">No obligatoria</span>'?></dd></dl></div></div>

        <?php if($event['IdIncidencia']):?><div class="card evt-panel mb-3"><div class="card-header"><h3 class="card-title"><i class="fas fa-link mr-2 text-primary"></i>Incidencia vinculada</h3></div><div class="card-body"><strong class="d-block text-primary"><?=evt_h($event['Folio'])?></strong><span><?=evt_h($event['TituloIncidencia'])?></span><div class="mt-2"><span class="badge badge-danger"><?=evt_h($event['PrioridadIncidencia'])?></span> <span class="badge badge-light"><?=inc_status_label($event['EstadoIncidencia'])?></span></div></div></div><?php elseif($canEdit):?><div class="card evt-panel mb-3"><div class="card-header"><h3 class="card-title"><i class="fas fa-link mr-2 text-info"></i>Vincular incidencia existente</h3></div><div class="card-body"><form method="post" action="acciones.php"><?=csrf_input()?><input type="hidden" name="action" value="vincular_incidencia"><input type="hidden" name="IdEvento" value="<?=$id?>"><div class="form-group"><label class="small font-weight-bold text-muted">INCIDENCIA ABIERTA</label><select name="IdIncidencia" class="form-control select2-ticket" required><option value="">Seleccione...</option><?php foreach($tickets as $ticket):?><option value="<?=(int)$ticket['IdTicket']?>"><?=evt_h($ticket['Folio'].' · '.$ticket['Prioridad'].' · '.$ticket['Titulo'])?></option><?php endforeach;?></select></div><button class="btn btn-outline-info btn-block"><i class="fas fa-link mr-1"></i>Vincular sin crear otra</button></form></div></div><?php endif;?>

        <?php if($canEdit):?><div class="card evt-panel mb-3"><div class="card-header"><h3 class="card-title"><i class="fas fa-user-check mr-2 text-success"></i>Revisión técnica</h3></div><div class="card-body"><form method="post" action="acciones.php"><?=csrf_input()?><input type="hidden" name="action" value="revisar_evento"><input type="hidden" name="IdEvento" value="<?=$id?>"><div class="form-group"><label>Resultado</label><select name="Estado" class="form-control"><option value="EN_REVISION" <?=$event['Estado']==='EN_REVISION'?'selected':''?>>En revisión</option><option value="REVISADO" <?=$event['Estado']==='REVISADO'?'selected':''?>>Revisado, sin incidencia</option><option value="DESCARTADO" <?=$event['Estado']==='DESCARTADO'?'selected':''?>>Descartado / falso positivo</option></select></div><div class="form-group"><label>Comentario *</label><textarea name="ComentarioRevision" class="form-control" rows="4" required><?=evt_h($event['ComentarioRevision'])?></textarea></div><button class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i>Guardar revisión</button></form><?php if($event['FechaRevision']):?><small class="d-block text-muted mt-2">Última revisión: <?=date('d/m/Y H:i',strtotime($event['FechaRevision']))?> · <?=evt_h($event['RevisadoPorNombre']?:'Sistema')?></small><?php endif;?></div></div><?php endif;?>
    </div></div>
</div></section>

<?php
$pageScripts = <<<'JS'
$(function(){ $('.select2-ticket').select2({theme:'bootstrap4',width:'100%'}); });
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>
