<?php
declare(strict_types=1);

$page_title = 'Gestión de Eventos TI';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';
evt_schema();
require_permiso('eventos_ti', 'ver');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php', false);

function evt_post(string $name, string $default = ''): string
{
    return trim((string)($_POST[$name] ?? $default));
}

function evt_find(int $id): ?array
{
    $rows = find_by_sql("SELECT e.*,v.NombreVM,v.IdMaquina IdMaquinaVM,m.Host FROM evt_eventos e
        LEFT JOIN maquina_virtual v ON v.IdVM=e.IdVM
        LEFT JOIN maquina m ON m.IdMaquina=COALESCE(e.IdMaquina,v.IdMaquina)
        WHERE e.IdEvento={$id} LIMIT 1");
    return $rows[0] ?? null;
}

$action = evt_post('action');
$user = evt_user();
$userId = (int)($user['id'] ?? 0);
$userName = trim((string)($user['name'] ?? $user['username'] ?? 'Usuario TI'));

if ($action === 'guardar_evento') {
    if (!tiene_permiso('eventos_ti', 'crear')) evt_redirect('No tiene permiso para registrar eventos.', 'd');
    $title = evt_post('Titulo');
    $message = evt_post('Mensaje');
    $source = strtoupper(evt_post('Fuente', 'OTRO'));
    $severity = strtoupper(evt_post('Severidad', 'INFORMATIVO'));
    $date = evt_post('FechaEvento', date('Y-m-d\TH:i'));
    $idVm = (int)($_POST['IdVM'] ?? 0);
    $idApplication = (int)($_POST['IdAplicacion'] ?? 0);
    if (mb_strlen($title) < 4 || mb_strlen($title) > 200) evt_redirect('El título debe tener entre 4 y 200 caracteres.', 'd');
    if (mb_strlen($message) < 5) evt_redirect('Escriba el detalle del evento.', 'd');
    if (!in_array($severity, ['INFORMATIVO','ADVERTENCIA','ERROR','CRITICO'], true)) evt_redirect('La severidad no es válida.', 'd');
    if ($idVm > 0 && !find_by_sql("SELECT IdVM FROM maquina_virtual WHERE IdVM={$idVm} LIMIT 1")) evt_redirect('La VM seleccionada no existe.', 'd');
    if ($idApplication > 0 && !find_by_sql("SELECT IdAplicacion FROM aplicacion WHERE IdAplicacion={$idApplication} LIMIT 1")) evt_redirect('La aplicación seleccionada no existe.', 'd');
    $result = evt_insert_event([
        'Fuente'=>$source,
        'Proveedor'=>evt_post('Proveedor'),
        'Canal'=>'MANUAL',
        'CodigoEvento'=>evt_post('CodigoEvento'),
        'Categoria'=>strtoupper(evt_post('Categoria', 'SISTEMA')),
        'Severidad'=>$severity,
        'FechaEvento'=>$date,
        'Titulo'=>$title,
        'Mensaje'=>$message,
        'IdVM'=>$idVm,
        'IdAplicacion'=>$idApplication,
        'RequiereIncidencia'=>!empty($_POST['RequiereIncidencia']),
    ], '', $userId);
    if (!$result['inserted']) evt_redirect('El evento ya estaba registrado.', 'w', 'detalle.php?id='.$result['id']);
    evt_redirect('Evento registrado correctamente.', 's', 'detalle.php?id='.$result['id']);
}

if ($action === 'revisar_evento') {
    if (!tiene_permiso('eventos_ti', 'editar')) evt_redirect('No tiene permiso para revisar eventos.', 'd');
    $id = (int)($_POST['IdEvento'] ?? 0);
    $event = evt_find($id);
    if (!$event) evt_redirect('El evento no existe.', 'd');
    $status = strtoupper(evt_post('Estado', 'REVISADO'));
    $comment = evt_post('ComentarioRevision');
    if (!in_array($status, ['EN_REVISION','REVISADO','DESCARTADO'], true)) evt_redirect('El estado seleccionado no es válido.', 'd', 'detalle.php?id='.$id);
    if (mb_strlen($comment) < 5) evt_redirect('Registre un comentario de revisión.', 'd', 'detalle.php?id='.$id);
    $db->query("UPDATE evt_eventos SET Estado='".$db->escape($status)."',ComentarioRevision='".$db->escape($comment)."',RevisadoPor={$userId},FechaRevision=NOW() WHERE IdEvento={$id}");
    evt_redirect('La revisión del evento quedó registrada.', 's', 'detalle.php?id='.$id);
}

if ($action === 'vincular_incidencia') {
    if (!tiene_permiso('eventos_ti', 'editar')) evt_redirect('No tiene permiso para vincular incidencias.', 'd');
    $id = (int)($_POST['IdEvento'] ?? 0);
    $ticketId = (int)($_POST['IdIncidencia'] ?? 0);
    $event = evt_find($id);
    $tickets = $ticketId ? find_by_sql("SELECT IdTicket,Folio,Estado FROM inc_tickets WHERE IdTicket={$ticketId} LIMIT 1") : [];
    if (!$event || !$tickets) evt_redirect('El evento o la incidencia seleccionada no existe.', 'd', 'detalle.php?id='.$id);
    $db->query("UPDATE evt_eventos SET IdIncidencia={$ticketId},Estado='VINCULADO',RevisadoPor={$userId},FechaRevision=NOW() WHERE IdEvento={$id}");
    inc_add_timeline($ticketId, 'EVENTO_MONITOREO', 'Se vinculó el evento técnico EVT-'.str_pad((string)$id, 7, '0', STR_PAD_LEFT).': '.$event['Titulo'].'.', false);
    evt_redirect('Evento vinculado a '.$tickets[0]['Folio'].'.', 's', 'detalle.php?id='.$id);
}

if ($action === 'crear_incidencia') {
    if (!tiene_permiso('incidencias', 'crear')) evt_redirect('No tiene permiso para crear incidencias.', 'd');
    $id = (int)($_POST['IdEvento'] ?? 0);
    $event = evt_find($id);
    if (!$event) evt_redirect('El evento no existe.', 'd');
    if ((int)$event['IdIncidencia'] > 0) evt_redirect('El evento ya está vinculado a una incidencia.', 'w', 'detalle.php?id='.$id);

    $groupSql = $db->escape((string)$event['ClaveAgrupacion']);
    $open = find_by_sql("SELECT t.IdTicket,t.Folio FROM evt_eventos e INNER JOIN inc_tickets t ON t.IdTicket=e.IdIncidencia
        WHERE e.ClaveAgrupacion='{$groupSql}' AND t.Estado NOT IN ('RESUELTO','CERRADO','CANCELADO') ORDER BY t.IdTicket DESC LIMIT 1");
    if ($open) {
        $ticketId = (int)$open[0]['IdTicket'];
        $db->query("UPDATE evt_eventos SET IdIncidencia={$ticketId},Estado='VINCULADO',RevisadoPor={$userId},FechaRevision=NOW() WHERE IdEvento={$id}");
        inc_add_timeline($ticketId, 'EVENTO_MONITOREO', 'Nueva ocurrencia vinculada: EVT-'.str_pad((string)$id, 7, '0', STR_PAD_LEFT).' — '.$event['Titulo'].'.', false);
        evt_redirect('Ya existía una incidencia abierta para este problema. El evento fue agregado a '.$open[0]['Folio'].'.', 'w', 'detalle.php?id='.$id);
    }

    $categoryCode = 'EVT';
    $categories = find_by_sql("SELECT IdCategoria FROM inc_categorias WHERE Codigo='".$db->escape($categoryCode)."' AND Activa=1 LIMIT 1");
    if (!$categories) $categories = find_by_sql("SELECT IdCategoria FROM inc_categorias WHERE Activa=1 ORDER BY IdCategoria LIMIT 1");
    if (!$categories) evt_redirect('No existe una categoría activa en Mesa de Ayuda.', 'd', 'detalle.php?id='.$id);

    $map = [
        'CRITICO'=>[4,4],
        'ERROR'=>[3,3],
        'ADVERTENCIA'=>[2,2],
        'INFORMATIVO'=>[1,1],
    ];
    [$impact,$urgency] = $map[$event['Severidad']] ?? [2,2];
    $priority = inc_priority($impact, $urgency);
    $sla = inc_sla_for_priority($priority);
    if (!$sla) evt_redirect('No existe una política SLA para la prioridad '.$priority.'.', 'd', 'detalle.php?id='.$id);

    $title = mb_substr('['.$event['Fuente'].'] '.$event['Titulo'], 0, 180);
    $description = "Evento técnico originado desde Gestión de Eventos TI.\n\n".
        'Evento: EVT-'.str_pad((string)$id, 7, '0', STR_PAD_LEFT)."\n".
        'Fecha del evento: '.$event['FechaEvento']."\n".
        'Severidad: '.evt_severity_label($event['Severidad'])."\n".
        'Origen: '.($event['NombreVM'] ?: $event['Host'] ?: $event['HostOrigen'] ?: 'No identificado')."\n".
        'Código: '.($event['CodigoEvento'] ?: '-')."\n\n".$event['Mensaje'];
    $machineId = (int)($event['IdMaquina'] ?: $event['IdMaquinaVM']);
    $requesterNameSql = $db->escape($userName);
    $requesterEmailSql = $db->escape((string)($user['correo'] ?? ''));
    $requesterAreaSql = $db->escape((string)($user['area'] ?? 'TI'));
    $now = time();
    $responseDue = date('Y-m-d H:i:s', $now + (int)$sla['MinutosRespuesta'] * 60);
    $resolutionDue = date('Y-m-d H:i:s', $now + (int)$sla['MinutosResolucion'] * 60);
    $db->query('START TRANSACTION');
    try {
        $db->query("INSERT INTO inc_tickets
            (Tipo,Canal,Titulo,Descripcion,IdCategoria,IdMaquina,SolicitanteId,SolicitanteNombre,SolicitanteCorreo,SolicitanteArea,Impacto,Urgencia,Prioridad,IdSLA,Estado,GrupoAsignacion,FechaObjetivoRespuesta,FechaObjetivoResolucion,CreadoPor,ActualizadoPor)
            VALUES ('INCIDENTE','MONITOREO','".$db->escape($title)."','".$db->escape($description)."',".(int)$categories[0]['IdCategoria'].",".($machineId ?: 'NULL').",{$userId},'{$requesterNameSql}','{$requesterEmailSql}','{$requesterAreaSql}',{$impact},{$urgency},'{$priority}',".(int)$sla['IdSLA'].",'NUEVO','SOPORTE TI','{$responseDue}','{$resolutionDue}',{$userId},{$userId})");
        $ticketId = (int)$db->insert_id();
        $folio = 'INC-'.date('Y').'-'.str_pad((string)$ticketId, 6, '0', STR_PAD_LEFT);
        $db->query("UPDATE inc_tickets SET Folio='".$db->escape($folio)."' WHERE IdTicket={$ticketId}");
        $db->query("UPDATE evt_eventos SET IdIncidencia={$ticketId},Estado='VINCULADO',RevisadoPor={$userId},FechaRevision=NOW() WHERE IdEvento={$id}");
        inc_add_timeline($ticketId, 'CREACION', 'Ticket de evento creado desde Gestión de Eventos TI por EVT-'.str_pad((string)$id, 7, '0', STR_PAD_LEFT).'.', true, null, 'NUEVO');
        $db->query('COMMIT');
    } catch (Throwable $error) {
        $db->query('ROLLBACK');
        throw $error;
    }
    evt_redirect($folio.' fue creada como incidencia y vinculada al evento.', 's', 'detalle.php?id='.$id);
}

if ($action === 'guardar_regla') {
    if (!tiene_permiso('eventos_ti', 'editar')) evt_redirect('No tiene permiso para administrar reglas.', 'd');
    $id = (int)($_POST['IdRegla'] ?? 0);
    $name = evt_post('Nombre');
    $source = strtoupper(evt_post('Fuente', 'TODOS'));
    $code = evt_post('CodigoEvento');
    $pattern = evt_post('PatronTexto');
    $category = strtoupper(evt_post('Categoria', 'SISTEMA'));
    $severity = strtoupper(evt_post('Severidad', 'ADVERTENCIA'));
    $requires = !empty($_POST['RequiereIncidencia']) ? 1 : 0;
    $active = !empty($_POST['Activa']) ? 1 : 0;
    $order = max(1, min(999, (int)($_POST['Orden'] ?? 100)));
    if (mb_strlen($name) < 4 || ($code === '' && $pattern === '')) evt_redirect('Indique el nombre y al menos un código o patrón.', 'd', 'index.php#reglas');
    $fields = array_map([$db,'escape'], [$name,$source,$code,$pattern,$category,$severity,evt_post('Descripcion')]);
    if ($id > 0) {
        $db->query("UPDATE evt_reglas SET Nombre='{$fields[0]}',Fuente='{$fields[1]}',CodigoEvento=".($code===''?'NULL':"'{$fields[2]}'").",PatronTexto=".($pattern===''?'NULL':"'{$fields[3]}'").",Categoria='{$fields[4]}',Severidad='{$fields[5]}',RequiereIncidencia={$requires},Activa={$active},Orden={$order},Descripcion='{$fields[6]}' WHERE IdRegla={$id}");
    } else {
        $db->query("INSERT INTO evt_reglas (Nombre,Fuente,CodigoEvento,PatronTexto,Categoria,Severidad,RequiereIncidencia,Activa,Orden,Descripcion)
            VALUES ('{$fields[0]}','{$fields[1]}',".($code===''?'NULL':"'{$fields[2]}'").",".($pattern===''?'NULL':"'{$fields[3]}'").",'{$fields[4]}','{$fields[5]}',{$requires},{$active},{$order},'{$fields[6]}')");
    }
    evt_redirect('Regla guardada correctamente.', 's', 'index.php#reglas');
}

evt_redirect('Acción no reconocida.', 'd');
