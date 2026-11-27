<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');

ensure_mantenimiento_ti_schema();
ensure_users_tipo_registro();
csrf_require();
$id = (int)($_POST['id'] ?? 0);
$isNewOrder = $id === 0;
$findingId = $isNewOrder ? (int)($_POST['hallazgo_id'] ?? 0) : 0;
require_permiso('mantenimiento_ti', $id > 0 ? 'editar' : 'crear');

$allowedTypes = ['PREVENTIVO', 'CORRECTIVO', 'DIAGNOSTICO', 'MEJORA'];
$allowedPriorities = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
$allowedStates = ['PROGRAMADO', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'];

function mti_post_text($key, $max) {
    return mb_substr(trim((string)($_POST[$key] ?? '')), 0, $max);
}
function mti_date_or_null($key) {
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '') return null;
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}
function mti_photo_uploads($key) {
    if (!isset($_FILES[$key])) return [];
    $source = $_FILES[$key];
    $names = is_array($source['name'] ?? null) ? $source['name'] : [$source['name'] ?? ''];
    $errors = is_array($source['error'] ?? null) ? $source['error'] : [$source['error'] ?? UPLOAD_ERR_NO_FILE];
    $sizes = is_array($source['size'] ?? null) ? $source['size'] : [$source['size'] ?? 0];
    $temps = is_array($source['tmp_name'] ?? null) ? $source['tmp_name'] : [$source['tmp_name'] ?? ''];
    $uploads = [];
    $label = $key === 'fotos_antes' ? 'Antes' : 'Despues';
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($names as $index => $name) {
        $error = (int)($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) throw new InvalidArgumentException("No se pudo recibir una foto {$label}.");
        $size = (int)($sizes[$index] ?? 0);
        if ($size <= 0 || $size > 15 * 1024 * 1024) throw new InvalidArgumentException('Cada foto debe pesar como maximo 15 MB.');
        $tmp = (string)($temps[$index] ?? '');
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset($extensions[$mime])) throw new InvalidArgumentException('Las evidencias deben ser imagenes JPG, PNG o WEBP.');
        $uploads[] = ['tmp'=>$tmp,'extension'=>$extensions[$mime],'original'=>basename((string)$name),'mime'=>$mime,'size'=>$size];
    }
    if (count($uploads) > 5) throw new InvalidArgumentException("Solo puede seleccionar hasta 5 fotos {$label}.");
    return $uploads;
}
function mti_store_photo(array $upload, string $scheduled, string $folio, string $kind): array {
    $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'mantenimiento_ti';
    $safeFolio = preg_replace('/[^A-Za-z0-9_-]/', '_', $folio);
    $relativeDir = substr($scheduled, 0, 4) . '/' . substr($scheduled, 5, 2) . '/' . $safeFolio;
    $directory = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo crear la carpeta de evidencias TI.');
    }
    $name = $kind . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.' . $upload['extension'];
    $absolute = $directory . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($upload['tmp'], $absolute)) {
        throw new RuntimeException('No se pudo guardar la foto ' . ($kind === 'antes' ? 'Antes' : 'Despues') . '.');
    }
    return ['relative' => $relativeDir . '/' . $name, 'absolute' => $absolute];
}
function mti_delete_photo(?string $relative): void {
    if (!$relative) return;
    $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'mantenimiento_ti';
    $rootReal = realpath($root);
    $fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative));
    if ($rootReal && $fileReal && str_starts_with($fileReal, $rootReal . DIRECTORY_SEPARATOR)) @unlink($fileReal);
}

$machineId = (int)($_POST['IdMaquina'] ?? 0);
$occurrenceId = (int)($_POST['ocurrencia_id'] ?? 0);
$type = strtoupper(mti_post_text('tipo', 20));
$priority = strtoupper(mti_post_text('prioridad', 15));
$state = strtoupper(mti_post_text('estado', 20));
$scheduled = mti_date_or_null('fecha_programada');
$executed = mti_date_or_null('fecha_ejecucion');
$reason = mti_post_text('motivo', 2000);
$diagnosis = mti_post_text('diagnostico', 3000);
$work = mti_post_text('trabajo_realizado', 3000);
$technician = mti_post_text('tecnico_responsable', 120);
$correctiveFinding = null;
if ($findingId > 0) {
    if (!mti_chk_schema_ready()) {
        $session->msg('d', 'No se puede vincular el hallazgo porque falta la migracion de checklists.');
        redirect('index.php', false);
    }
    $correctiveFinding = $db->fetch_assoc($db->query(
        "SELECT h.*,m.IdMaquina FROM activos_mantenimiento_hallazgos h"
        . " INNER JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id"
        . " WHERE h.id={$findingId} AND h.estado NOT IN ('CERRADO','ANULADO') LIMIT 1"
    ));
    if (!$correctiveFinding || (int)$correctiveFinding['IdMaquina'] !== $machineId) {
        $session->msg('d', 'El hallazgo no corresponde al equipo de la nueva orden.');
        redirect('index.php', false);
    }
}
try {
    $photoUploads = [
        'ANTES' => mti_photo_uploads('fotos_antes'),
        'DESPUES' => mti_photo_uploads('fotos_despues'),
    ];
} catch (InvalidArgumentException $error) {
    $session->msg('d', $error->getMessage());
    redirect('index.php', false);
}

// Una fecha realizada significa que el trabajo fue ejecutado.
if ($executed && $state !== 'CANCELADO') $state = 'COMPLETADO';

if ($technician !== '') {
    $technicianQ = "'" . $db->escape($technician) . "'";
    $technicianResult = $db->query(
        "SELECT id,name FROM users WHERE name={$technicianQ} AND status=1"
        . " AND tipo_registro='USUARIO' AND es_personal_ti=1 LIMIT 1"
    );
    $validTechnician = $db->fetch_assoc($technicianResult);
    if (!$validTechnician) {
        $session->msg('d', 'El responsable seleccionado no esta habilitado como Personal de TI.');
        redirect('index.php', false);
    }
    $technician = (string)$validTechnician['name'];
}

if (!in_array($type, $allowedTypes, true) || !in_array($priority, $allowedPriorities, true) || !in_array($state, $allowedStates, true)) {
    $session->msg('d', 'Tipo, prioridad o estado no valido.');
    redirect('index.php', false);
}
if (!$scheduled || $reason === '') {
    $session->msg('d', 'La fecha programada y el motivo son obligatorios.');
    redirect('index.php', false);
}
if ($state === 'COMPLETADO') {
    if ($work === '') {
        $session->msg('d', 'Registre el trabajo realizado antes de completar la orden.');
        redirect('index.php', false);
    }
    if (!$executed) {
        $session->msg('d', 'Registre la fecha real en que se realizo el mantenimiento.');
        redirect('index.php', false);
    }
}

$operator = current_user();
$operatorId = (int)($operator['id'] ?? 0);
$oldState = null;
$existing = null;

if ($id > 0) {
    $result = $db->query("SELECT * FROM activos_mantenimientos WHERE id = {$id} LIMIT 1");
    $existing = $db->fetch_assoc($result);
    if (!$existing) {
        $session->msg('d', 'La orden no existe.');
        redirect('index.php', false);
    }
    $machineId = (int)$existing['IdMaquina'];
    $oldState = $existing['estado'];
    $folio = (string)$existing['folio'];
} else {
    $machine = $db->query("SELECT IdMaquina FROM maquina WHERE IdMaquina = {$machineId} LIMIT 1");
    if ($machineId <= 0 || $db->num_rows($machine) === 0) {
        $session->msg('d', 'Seleccione un equipo valido.');
        redirect('index.php', false);
    }
}

foreach ($photoUploads as $stage => $uploads) {
    if (!$uploads) continue;
    $countRow = $db->fetch_assoc($db->query(
        "SELECT COUNT(*) total FROM activos_mantenimiento_fotos WHERE mantenimiento_id={$id} AND etapa='" . $db->escape($stage) . "'"
    ));
    if ((int)($countRow['total'] ?? 0) + count($uploads) > 5) {
        $session->msg('d', 'La orden admite como maximo 5 fotos ' . ($stage === 'ANTES' ? 'Antes' : 'Despues') . '. Ya existen ' . (int)$countRow['total'] . '.');
        redirect('index.php', false);
    }
}

$assignedUserId = null;
$assignedUserName = null;
if ($id === 0) {
    $assignedResult = $db->query(
        "SELECT a.id AS user_id,u.name FROM asig_maquina a"
        . " LEFT JOIN users u ON u.id=a.id"
        . " WHERE a.IdMaquina={$machineId} AND a.Estado=1"
        . " ORDER BY a.IdAsMaq DESC LIMIT 1"
    );
    $assigned = $db->fetch_assoc($assignedResult);
    if ($assigned) {
        $assignedUserId = (int)$assigned['user_id'];
        $assignedUserName = trim((string)($assigned['name'] ?? '')) ?: null;
    }
}

$currentOccurrence = null;
if ($id > 0) {
    $currentResult = $db->query("SELECT id FROM activos_mantenimiento_ocurrencias WHERE mantenimiento_id={$id} LIMIT 1");
    $currentOccurrence = $db->fetch_assoc($currentResult) ?: null;
    if ($currentOccurrence && $occurrenceId !== (int)$currentOccurrence['id']) {
        $session->msg('d', 'La programacion vinculada no puede cambiarse porque forma parte de la trazabilidad de la orden.');
        redirect('index.php', false);
    }
}

$selectedOccurrence = null;
if ($occurrenceId > 0) {
    $selectedResult = $db->query(
        "SELECT o.id,o.codigo,o.estado,o.fecha_programada,o.mantenimiento_id,p.IdMaquina"
        . " FROM activos_mantenimiento_ocurrencias o"
        . " INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
        . " WHERE o.id={$occurrenceId} LIMIT 1"
    );
    $selectedOccurrence = $db->fetch_assoc($selectedResult);
    if (!$selectedOccurrence || (int)$selectedOccurrence['IdMaquina'] !== $machineId) {
        $session->msg('d', 'La ocurrencia seleccionada no pertenece al equipo de esta orden.');
        redirect('index.php', false);
    }
    $scheduled = (string)$selectedOccurrence['fecha_programada'];
    $linkedOrder = (int)($selectedOccurrence['mantenimiento_id'] ?? 0);
    if (($linkedOrder > 0 && $linkedOrder !== $id)
        || ($linkedOrder === 0 && $selectedOccurrence['estado'] !== 'PENDIENTE')) {
        $session->msg('d', 'La ocurrencia seleccionada ya no esta disponible para vincularse.');
        redirect('index.php', false);
    }
}

$q = function($value) use ($db) { return "'" . $db->escape($value) . "'"; };
$nullable = function($value) use ($q) { return $value === null || $value === '' ? 'NULL' : $q($value); };

$db->query("START TRANSACTION");

if ($id > 0) {
    $db->query("UPDATE activos_mantenimientos SET
      tipo=".$q($type).", prioridad=".$q($priority).", estado=".$q($state).",
      fecha_programada=".$q($scheduled).", fecha_ejecucion=".$nullable($executed).",
      motivo=".$q($reason).", diagnostico=".$nullable($diagnosis).",
      trabajo_realizado=".$nullable($work).", tecnico_responsable=".$nullable($technician).",
      actualizado_por={$operatorId} WHERE id={$id} LIMIT 1");
    $action = $oldState !== $state ? 'CAMBIO_ESTADO' : 'ACTUALIZACION';
    $message = 'Orden actualizada correctamente.';
} else {
    $db->query("INSERT INTO activos_mantenimientos
      (IdMaquina, tipo, prioridad, estado, fecha_programada, fecha_ejecucion,
       motivo, diagnostico, trabajo_realizado, tecnico_responsable,
       usuario_asignado_id, usuario_asignado_nombre, creado_por, actualizado_por)
      VALUES ({$machineId}, ".$q($type).", ".$q($priority).", ".$q($state).", ".$q($scheduled).", ".$nullable($executed).",
       ".$q($reason).", ".$nullable($diagnosis).", ".$nullable($work).", ".$nullable($technician).",
       ".($assignedUserId === null ? 'NULL' : $assignedUserId).", ".$nullable($assignedUserName).", {$operatorId}, {$operatorId})");
    $id = (int)$db->insert_id();
    $folio = 'MTI-' . date('Y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    $db->query("UPDATE activos_mantenimientos SET folio=".$q($folio)." WHERE id={$id} LIMIT 1");
    $action = 'CREACION';
    $message = 'Orden de mantenimiento creada correctamente.';
}

if ($selectedOccurrence && empty($selectedOccurrence['mantenimiento_id'])) {
    $occurrenceState = $state === 'COMPLETADO' ? 'EJECUTADA'
        : ($state === 'CANCELADO' ? 'CANCELADA' : 'OT_GENERADA');
    $db->query(
        "UPDATE activos_mantenimiento_ocurrencias SET mantenimiento_id={$id},estado=".$q($occurrenceState)
        .",actualizado_por={$operatorId} WHERE id={$occurrenceId} AND mantenimiento_id IS NULL AND estado='PENDIENTE' LIMIT 1"
    );
    if ($db->affected_rows() !== 1) {
        $db->query("ROLLBACK");
        $session->msg('d', 'Otro usuario vinculo esta ocurrencia mientras se guardaba la orden. Seleccione otra programacion.');
        redirect('index.php', false);
    }
    $db->query(
        "INSERT INTO activos_mantenimiento_ocurrencia_historial"
        . " (ocurrencia_id,accion,fecha_nueva,estado_anterior,estado_nuevo,comentario,registrado_por) VALUES ("
        . "{$occurrenceId},'VINCULAR_OT',".$q($selectedOccurrence['fecha_programada']).",'PENDIENTE',"
        . $q($occurrenceState).','.$q('Vinculada manualmente a la orden de mantenimiento').",{$operatorId})"
    );
}

$oldSql = $oldState === null ? 'NULL' : $q($oldState);
$comment = $action === 'CREACION' ? 'Orden registrada' : ($action === 'CAMBIO_ESTADO' ? 'Estado actualizado' : 'Datos de la orden actualizados');
$db->query("INSERT INTO activos_mantenimiento_historial
  (mantenimiento_id, accion, estado_anterior, estado_nuevo, comentario, registrado_por)
  VALUES ({$id}, ".$q($action).", {$oldSql}, ".$q($state).", ".$q($comment).", {$operatorId})");

if ($correctiveFinding && $isNewOrder) {
    $actionDescription = 'OT correctiva ' . $folio . ' generada para ' . $correctiveFinding['codigo'];
    $db->query(
        "INSERT INTO activos_mantenimiento_hallazgo_acciones"
        . " (hallazgo_id,tipo,descripcion,responsable_id,fecha_inicio,estado,mantenimiento_correctivo_id,creado_por)"
        . " VALUES ({$findingId},'ACCION_CORRECTIVA'," . $q($actionDescription)
        . ",{$operatorId},NOW(),'EN_PROCESO',{$id},{$operatorId})"
    );
    $findingActionId = (int)$db->insert_id();
    $previousFindingState = (string)$correctiveFinding['estado'];
    if ($previousFindingState === 'ABIERTO') {
        $db->query("UPDATE activos_mantenimiento_hallazgos SET estado='EN_ATENCION' WHERE id={$findingId}");
    }
    mti_chk_hallazgo_history(
        $findingId,
        'OT_CORRECTIVA_CREADA',
        $previousFindingState,
        $previousFindingState === 'ABIERTO' ? 'EN_ATENCION' : $previousFindingState,
        $actionDescription,
        $operatorId,
        $findingActionId
    );
}

// Las ordenes creadas desde programacion mantienen sincronizada su ocurrencia.
$occurrenceResult = $db->query(
    "SELECT id,estado,fecha_programada FROM activos_mantenimiento_ocurrencias"
    . " WHERE mantenimiento_id={$id} LIMIT 1"
);
$occurrence = $db->fetch_assoc($occurrenceResult);
if ($occurrence) {
    $occurrenceState = $state === 'COMPLETADO' ? 'EJECUTADA'
        : ($state === 'CANCELADO' ? 'CANCELADA' : 'OT_GENERADA');
    if ($occurrenceState !== $occurrence['estado']) {
        $occurrenceId = (int)$occurrence['id'];
        $db->query(
            "UPDATE activos_mantenimiento_ocurrencias SET estado=".$q($occurrenceState)
            .", actualizado_por={$operatorId} WHERE id={$occurrenceId} LIMIT 1"
        );
        $db->query(
            "INSERT INTO activos_mantenimiento_ocurrencia_historial"
            . " (ocurrencia_id,accion,fecha_nueva,estado_anterior,estado_nuevo,comentario,registrado_por) VALUES ("
            . "{$occurrenceId},'SINCRONIZAR_OT',".$q($occurrence['fecha_programada']).','.$q($occurrence['estado']).','
            . $q($occurrenceState).','.$q('Estado actualizado desde la orden de trabajo').",{$operatorId})"
        );
    }
}

$newPhotoFiles = [];
foreach ($photoUploads as $stage => $uploads) {
    foreach ($uploads as $upload) {
        try {
            $kind = strtolower($stage);
            $stored = mti_store_photo($upload, $scheduled, $folio, $kind);
            $newPhotoFiles[] = $stored['absolute'];
            $db->query(
                "INSERT INTO activos_mantenimiento_fotos (mantenimiento_id,etapa,ruta,nombre_original,mime,tamano_bytes,creado_por) VALUES ("
                . "{$id},".$q($stage).','.$q($stored['relative']).','.$q($upload['original']).','.$q($upload['mime']).','.(int)$upload['size'].",{$operatorId})"
            );
            $legacyColumn = $stage === 'ANTES' ? 'foto_antes' : 'foto_despues';
            $db->query(
                "UPDATE activos_mantenimientos SET {$legacyColumn}=COALESCE(NULLIF({$legacyColumn},''),".$q($stored['relative']).")"
                . ", actualizado_por={$operatorId} WHERE id={$id} LIMIT 1"
            );
        } catch (Throwable $error) {
            $db->query("ROLLBACK");
            foreach ($newPhotoFiles as $newPhotoFile) @unlink($newPhotoFile);
            $session->msg('d', $error->getMessage());
            redirect('index.php', false);
        }
    }
}

$db->query("COMMIT");

$session->msg('s', $message);
redirect($correctiveFinding ? 'hallazgo.php?id=' . $findingId : 'detalle.php?id=' . $id, false);
?>
