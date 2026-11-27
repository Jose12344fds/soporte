<?php
declare(strict_types=1);

$page_title = 'Importación histórica de tickets';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/../incidencias/bootstrap.php';
require_once __DIR__.'/lib.php';

inc_schema();
hist_schema();
require_permiso('incidencias', 'editar');
if (!inc_is_agent()) redirect('../incidencias/index.php', false);

function hist_download(string $type): void
{
    $tickets = [
        ['codigo_historico','solicitante','responsable','tipo','canal','categoria_codigo','titulo','descripcion','impacto','urgencia','estado','fecha_creacion','fecha_primera_respuesta','fecha_resolucion','fecha_cierre','solucion','causa_raiz','codigo_cierre'],
        ['ANT-0001','usuario@tama.pe','admin','INCIDENTE','CORREO','SW','Ejemplo de ticket histórico','Descripción original del caso registrado anteriormente.',2,2,'CERRADO','15/01/2025 08:30','15/01/2025 09:00','15/01/2025 11:15','15/01/2025 11:30','Se corrigió la configuración.','Configuración incorrecta.','SOLUCION_APLICADA'],
    ];
    $activity = [
        ['codigo_historico','fecha','autor','tipo_actividad','es_publico','comentario'],
        ['ANT-0001','15/01/2025 09:00','admin','COMENTARIO','SI','Se inició la revisión del incidente.'],
        ['ANT-0001','15/01/2025 11:15','admin','RESOLUCION','SI','Se aplicó la solución y se validó el servicio.'],
    ];
    $rows = $type === 'actividad' ? $activity : $tickets;
    $name = $type === 'actividad' ? 'Plantilla_Actividad_Historica.csv' : 'Plantilla_Tickets_Historicos.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$name.'"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'wb');
    foreach ($rows as $row) fputcsv($out, $row, ';');
    fclose($out);
    exit;
}

if (isset($_GET['plantilla'])) hist_download((string)$_GET['plantilla']);

$message = ''; $messageType = 'info'; $preview = $_SESSION['inc_hist_preview'] ?? null;
$action = (string)($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preview') {
    unset($_SESSION['inc_hist_preview']); $preview = null;
    try {
        if (empty($_FILES['tickets_csv']['tmp_name']) || (int)$_FILES['tickets_csv']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Seleccione la plantilla de Tickets.');
        $tickets = hist_csv((string)$_FILES['tickets_csv']['tmp_name']);
        $activities = [];
        if (!empty($_FILES['actividad_csv']['tmp_name']) && (int)$_FILES['actividad_csv']['error'] === UPLOAD_ERR_OK) $activities = hist_csv((string)$_FILES['actividad_csv']['tmp_name']);
        $result = hist_validate($tickets, $activities);
        $preview = ['token'=>bin2hex(random_bytes(20)),'file'=>(string)$_FILES['tickets_csv']['name'],'result'=>$result,'created'=>time()];
        $_SESSION['inc_hist_preview'] = $preview;
        if ($result['errors']) { $message = 'La validación encontró '.count($result['errors']).' observación(es). No se guardó información.'; $messageType = 'danger'; }
        else { $message = 'Validación correcta. Revise el resumen y confirme la importación.'; $messageType = 'success'; }
    } catch (Throwable $e) { $message = $e->getMessage(); $messageType = 'danger'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'commit') {
    try {
        if (!$preview || !hash_equals((string)$preview['token'], (string)($_POST['preview_token'] ?? ''))) throw new RuntimeException('La vista previa venció. Vuelva a validar los archivos.');
        if (!empty($preview['result']['errors'])) throw new RuntimeException('La importación contiene observaciones pendientes.');
        $user = inc_user(); $userId = (int)$user['id']; $userName = (string)($user['name'] ?? $user['username']);
        $batchCode = 'HIST-'.date('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $db->query('START TRANSACTION');
        $db->query("INSERT INTO inc_import_lotes (CodigoLote,NombreArchivo,CreadoPor,CreadoPorNombre) VALUES ('".$db->escape($batchCode)."','".$db->escape((string)$preview['file'])."',{$userId},'".$db->escape($userName)."')");
        $batchId = (int)$db->insert_id(); $ticketIds = [];
        foreach ($preview['result']['tickets'] as $ticket) {
            $source = $ticket['source']; $sla = $ticket['sla'];
            $responseDue = date('Y-m-d H:i:s', strtotime($ticket['created']) + (int)$sla['MinutosRespuesta'] * 60);
            $resolutionDue = date('Y-m-d H:i:s', strtotime($ticket['created']) + (int)$sla['MinutosResolucion'] * 60);
            $requester = $ticket['requester']; $assignee = $ticket['assignee'];
            $assigneeId = $assignee ? (int)$assignee['id'] : null;
            $db->query("INSERT INTO inc_tickets
                (ClaveSolicitud,Tipo,Canal,Titulo,Descripcion,IdCategoria,SolicitanteId,SolicitanteNombre,SolicitanteCorreo,SolicitanteArea,Impacto,Urgencia,Prioridad,IdSLA,Estado,AsignadoA,AsignadoNombre,FechaRegistro,FechaPrimeraRespuesta,FechaObjetivoRespuesta,FechaObjetivoResolucion,FechaResolucion,FechaCierre,Resolucion,CausaRaiz,CodigoCierre,CreadoPor,ActualizadoPor)
                VALUES ('HIST:".substr(hash('sha256', $batchCode.':'.$ticket['code']), 0, 59)."','".$db->escape($ticket['type'])."','".$db->escape($ticket['channel'])."','".$db->escape($ticket['title'])."','".$db->escape($ticket['description'])."',".(int)$ticket['category'][0]['IdCategoria'].",".(int)$requester['id'].",'".$db->escape((string)$requester['name'])."','".$db->escape((string)($requester['correo'] ?? ''))."','".$db->escape((string)($requester['area'] ?? ''))."',".(int)$ticket['impact'].",".(int)$ticket['urgency'].",'".$ticket['priority']."',".(int)$sla['IdSLA'].",'".$ticket['state']."',".($assigneeId ?: 'NULL').",".hist_sql($assignee ? (string)$assignee['name'] : null).",'".$ticket['created']."',".hist_sql($ticket['first']).",'{$responseDue}','{$resolutionDue}',".hist_sql($ticket['resolved']).",".hist_sql($ticket['closed']).",".hist_sql(trim((string)($source['solucion'] ?? ''))).",".hist_sql(trim((string)($source['causa_raiz'] ?? ''))).",".hist_sql(trim((string)($source['codigo_cierre'] ?? ''))).",{$userId},{$userId})");
            $ticketId = (int)$db->insert_id(); $ticketIds[$ticket['code']] = $ticketId;
            $folio = 'INC-'.substr($ticket['created'], 0, 4).'-'.str_pad((string)$ticketId, 6, '0', STR_PAD_LEFT);
            $db->query("UPDATE inc_tickets SET Folio='".$db->escape($folio)."',updated_at='".$ticket['created']."' WHERE IdTicket={$ticketId}");
            $db->query("INSERT INTO inc_import_lote_tickets (IdLote,IdTicket,CodigoHistorico) VALUES ({$batchId},{$ticketId},'".$db->escape($ticket['code'])."')");
            $db->query("INSERT INTO inc_seguimiento (IdTicket,UsuarioId,UsuarioNombre,Tipo,Comentario,EsPublico,EstadoNuevo,Fecha) VALUES ({$ticketId},{$userId},'".$db->escape($userName)."','IMPORTACION_HISTORICA','Registro migrado desde el histórico ".$db->escape($ticket['code']).".',0,'".$ticket['state']."','".$ticket['created']."')");
        }
        foreach ($preview['result']['activities'] as $activity) {
            $ticketId = $ticketIds[$activity['code']]; $author = $activity['author'];
            $db->query("INSERT INTO inc_seguimiento (IdTicket,UsuarioId,UsuarioNombre,Tipo,Comentario,EsPublico,Fecha) VALUES ({$ticketId},".(int)$author['id'].",'".$db->escape((string)$author['name'])."','".$db->escape(substr($activity['type'],0,30))."','".$db->escape($activity['comment'])."',".($activity['public'] ? 1 : 0).",'".$activity['date']."')");
        }
        $ticketTotal = count($ticketIds); $activityTotal = count($preview['result']['activities']);
        $db->query("UPDATE inc_import_lotes SET TotalTickets={$ticketTotal},TotalActividades={$activityTotal} WHERE IdLote={$batchId}");
        $db->query('COMMIT'); unset($_SESSION['inc_hist_preview']); $preview = null;
        $message = "Lote {$batchCode} importado: {$ticketTotal} ticket(s) y {$activityTotal} actividad(es). No se enviaron correos."; $messageType = 'success';
    } catch (Throwable $e) { $db->query('ROLLBACK'); $message = $e->getMessage(); $messageType = 'danger'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'revert') {
    $batchId = (int)($_POST['batch_id'] ?? 0);
    try {
        $batch = find_by_sql("SELECT * FROM inc_import_lotes WHERE IdLote={$batchId} AND Estado='IMPORTADO' LIMIT 1");
        if (!$batch) throw new RuntimeException('El lote no existe o ya fue revertido.');
        $user = inc_user(); $userId = (int)$user['id'];
        $db->query('START TRANSACTION');
        $db->query("DELETE t FROM inc_tickets t INNER JOIN inc_import_lote_tickets m ON m.IdTicket=t.IdTicket WHERE m.IdLote={$batchId}");
        $db->query("UPDATE inc_import_lotes SET Estado='REVERTIDO',FechaReversion=NOW(),RevertidoPor={$userId} WHERE IdLote={$batchId}");
        $db->query('COMMIT'); $message = 'El lote histórico fue revertido correctamente.'; $messageType = 'success';
    } catch (Throwable $e) { $db->query('ROLLBACK'); $message = $e->getMessage(); $messageType = 'danger'; }
}

$batches = find_by_sql("SELECT l.*,COUNT(m.IdTicket) TicketsVigentes FROM inc_import_lotes l LEFT JOIN inc_import_lote_tickets m ON m.IdLote=l.IdLote GROUP BY l.IdLote ORDER BY l.IdLote DESC LIMIT 30");
include_once __DIR__.'/../../layouts/header.php';
?>
<style>
.hist-page{--ink:#123b4b;--teal:#087f8c;--mint:#dff4ef;--amber:#f7b731;background:linear-gradient(135deg,#eef6f7,#f8fbfc 55%,#e9f5f0);min-height:calc(100vh - 56px)}
.hist-hero{background:linear-gradient(115deg,#103d50,#078593 70%,#22a784);color:#fff;border-radius:18px;padding:24px 28px;box-shadow:0 12px 28px rgba(16,61,80,.18)}
.hist-card{background:#fff;border:1px solid #d9e7ea;border-radius:16px;box-shadow:0 8px 22px rgba(18,59,75,.07);overflow:hidden}.hist-head{padding:17px 20px;border-bottom:1px solid #e1ecee;font-weight:800;color:var(--ink)}.hist-body{padding:20px}
.hist-step{display:flex;gap:14px;padding:14px;border-radius:13px;background:#f5fafb;height:100%}.hist-step b{display:grid;place-items:center;min-width:34px;height:34px;border-radius:50%;background:var(--teal);color:#fff}.hist-drop{border:2px dashed #9fcbd0;border-radius:14px;padding:18px;background:#f8fcfc}.hist-table th{background:#123f50;color:#fff;font-size:12px}.hist-pill{border-radius:50px;padding:4px 9px;font-size:11px;font-weight:800}.hist-note{background:#fff7df;border-left:4px solid var(--amber);padding:12px;border-radius:8px}
</style>
<section class="content hist-page"><div class="container-fluid py-3">
<div class="hist-hero mb-3"><div class="d-flex flex-wrap justify-content-between align-items-center"><div><small class="font-weight-bold">MIGRACIÓN CONTROLADA</small><h2 class="mb-1"><i class="fas fa-history mr-2"></i>Tickets históricos</h2><p class="mb-0">Recupere la trazabilidad anterior sin modificar ni detener la Mesa de Ayuda actual.</p></div><a href="../incidencias/index.php" class="btn btn-outline-light"><i class="fas fa-arrow-left mr-1"></i>Mesa de Ayuda</a></div></div>
<?php if ($message): ?><div class="alert alert-<?= $messageType ?> shadow-sm"><?= inc_h($message) ?></div><?php endif; ?>
<div class="row mb-3">
<?php foreach ([['Descargue','las dos plantillas CSV y ábralas con Excel.'],['Complete','usuarios, fechas reales, solución y cada actividad.'],['Valide','el sistema no guarda nada si encuentra errores.'],['Confirme','se crea un lote auditable y reversible.']] as $i=>$step): ?><div class="col-md-3 mb-2"><div class="hist-step"><b><?= $i+1 ?></b><span><strong><?= $step[0] ?></strong><br><small class="text-muted"><?= $step[1] ?></small></span></div></div><?php endforeach; ?>
</div>
<div class="row"><div class="col-xl-7 mb-3"><div class="hist-card"><div class="hist-head"><i class="fas fa-file-import mr-2 text-info"></i>Preparar y validar lote</div><div class="hist-body">
<div class="d-flex flex-wrap mb-3"><a class="btn btn-outline-success mr-2 mb-2" href="?plantilla=tickets"><i class="fas fa-file-excel mr-1"></i>Plantilla Tickets</a><a class="btn btn-outline-info mb-2" href="?plantilla=actividad"><i class="fas fa-comments mr-1"></i>Plantilla Actividad</a></div>
<form method="post" enctype="multipart/form-data"><?= csrf_input() ?><input type="hidden" name="action" value="preview"><div class="hist-drop mb-3"><div class="form-group"><label><b>Tickets CSV *</b></label><input type="file" name="tickets_csv" class="form-control-file" accept=".csv" required></div><div class="form-group mb-0"><label><b>Actividad CSV</b> <small class="text-muted">opcional</small></label><input type="file" name="actividad_csv" class="form-control-file" accept=".csv"></div></div><button class="btn btn-info btn-lg"><i class="fas fa-search mr-1"></i>Validar y mostrar vista previa</button></form>
<div class="hist-note mt-3"><b>No se notificará a los usuarios.</b> La carga histórica no enviará correos ni alertas; los tickets sí aparecerán en reportes según sus fechas originales.</div>
</div></div></div>
<div class="col-xl-5 mb-3"><div class="hist-card"><div class="hist-head"><i class="fas fa-shield-alt mr-2 text-success"></i>Controles aplicados</div><div class="hist-body"><ul class="mb-0 pl-3"><li>Usuarios y responsables validados contra el sistema.</li><li>Categorías y SLA existentes.</li><li>Prioridad recalculada por impacto y urgencia.</li><li>Control de fechas y códigos duplicados.</li><li>Transacción completa: todo se guarda o nada se guarda.</li><li>Identificación permanente del origen histórico.</li></ul></div></div></div></div>
<?php if ($preview): $result=$preview['result']; ?><div class="hist-card mb-3"><div class="hist-head d-flex justify-content-between"><span>Vista previa del lote</span><span><?= count($result['tickets']) ?> tickets / <?= count($result['activities']) ?> actividades</span></div><div class="hist-body">
<?php if ($result['errors']): ?><div class="alert alert-danger"><b>Corrija el archivo y vuelva a validarlo:</b><ul class="mb-0 mt-2"><?php foreach (array_slice($result['errors'],0,100) as $error): ?><li><?= inc_h($error) ?></li><?php endforeach; ?></ul></div><?php else: ?>
<div class="table-responsive"><table class="table table-sm hist-table"><thead><tr><th>Código</th><th>Fecha</th><th>Solicitante</th><th>Asunto</th><th>Prioridad</th><th>Estado</th></tr></thead><tbody><?php foreach (array_slice($result['tickets'],0,50) as $row): ?><tr><td><?= inc_h($row['code']) ?></td><td><?= inc_h(inc_datetime($row['created'])) ?></td><td><?= inc_h($row['requester']['name']) ?></td><td><?= inc_h($row['title']) ?></td><td><?= inc_h($row['priority']) ?></td><td><?= inc_h(inc_status_label($row['state'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
<form method="post" onsubmit="return confirm('¿Importar definitivamente este lote histórico?');"><?= csrf_input() ?><input type="hidden" name="action" value="commit"><input type="hidden" name="preview_token" value="<?= inc_h($preview['token']) ?>"><button class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-1"></i>Confirmar importación</button></form><?php endif; ?>
</div></div><?php endif; ?>
<div class="hist-card"><div class="hist-head"><i class="fas fa-layer-group mr-2"></i>Lotes importados</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Lote</th><th>Archivo</th><th>Resultado</th><th>Responsable</th><th>Fecha</th><th>Estado</th><th></th></tr></thead><tbody><?php if (!$batches): ?><tr><td colspan="7" class="text-center text-muted py-4">Todavía no existen cargas históricas.</td></tr><?php endif; ?><?php foreach ($batches as $batch): ?><tr><td><b><?= inc_h($batch['CodigoLote']) ?></b></td><td><?= inc_h($batch['NombreArchivo']) ?></td><td><?= (int)$batch['TotalTickets'] ?> tickets / <?= (int)$batch['TotalActividades'] ?> actividades</td><td><?= inc_h($batch['CreadoPorNombre']) ?></td><td><?= inc_h(inc_datetime($batch['FechaImportacion'])) ?></td><td><span class="hist-pill <?= $batch['Estado']==='IMPORTADO'?'bg-success text-white':'bg-secondary text-white' ?>"><?= inc_h($batch['Estado']) ?></span></td><td><?php if ($batch['Estado']==='IMPORTADO'): ?><form method="post" onsubmit="return confirm('Esta acción eliminará solamente los tickets creados por este lote. ¿Continuar?');"><?= csrf_input() ?><input type="hidden" name="action" value="revert"><input type="hidden" name="batch_id" value="<?= (int)$batch['IdLote'] ?>"><button class="btn btn-sm btn-outline-danger">Revertir</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</div></section>
<?php include_once __DIR__.'/../../layouts/footer.php'; ?>
