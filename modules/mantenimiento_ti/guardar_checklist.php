<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');
ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'editar');
csrf_require();
mti_chk_require_schema();

$checklistId = (int)($_POST['checklist_id'] ?? 0);
$complete = ($_POST['modo'] ?? '') === 'completar';
$checklist = $db->fetch_assoc($db->query(
    "SELECT * FROM activos_mantenimiento_checklists WHERE id={$checklistId} LIMIT 1"
));
if (!$checklist) {
    $session->msg('d', 'El checklist no existe.');
    redirect('index.php', false);
}
$maintenanceId = (int)$checklist['mantenimiento_id'];
if ($checklist['estado'] === 'COMPLETADO') {
    $session->msg('d', 'El checklist completado esta bloqueado para edicion.');
    redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
}
$responses = find_by_sql(
    "SELECT r.*,COUNT(h.id) hallazgos,"
    . " SUM(CASE WHEN e.id IS NOT NULL THEN 1 ELSE 0 END) evidencias"
    . " FROM activos_mantenimiento_checklist_respuestas r"
    . " LEFT JOIN activos_mantenimiento_hallazgos h ON h.checklist_respuesta_id=r.id AND h.estado<>'ANULADO'"
    . " LEFT JOIN activos_mantenimiento_hallazgo_evidencias e ON e.hallazgo_id=h.id"
    . " WHERE r.checklist_id={$checklistId} GROUP BY r.id ORDER BY r.orden_snapshot,r.id"
);
$values = is_array($_POST['respuesta'] ?? null) ? $_POST['respuesta'] : [];
$notes = is_array($_POST['observacion'] ?? null) ? $_POST['observacion'] : [];
$errors = [];
$normalized = [];
foreach ($responses as $response) {
    $id = (int)$response['id'];
    $value = trim((string)($values[$id] ?? ''));
    $note = trim((string)($notes[$id] ?? ''));
    if ($response['tipo_respuesta_snapshot'] === 'NUMERICO') $value = str_replace(',', '.', $value);
    if (!mti_chk_value_valid($response['tipo_respuesta_snapshot'], $value)) {
        $errors[] = 'Respuesta invalida: ' . $response['descripcion_snapshot'];
    }
    if ($complete && (int)$response['obligatorio_snapshot'] === 1 && $value === '') {
        $errors[] = 'Falta responder: ' . $response['descripcion_snapshot'];
    }
    if ($complete && $value === 'NA' && $note === '') {
        $errors[] = 'NA requiere justificacion: ' . $response['descripcion_snapshot'];
    }
    if ((int)$response['hallazgos'] > 0
        && in_array($value, ['SI', 'CUMPLE'], true)) {
        $errors[] = 'No puede marcar Conforme mientras existan hallazgos activos: ' . $response['descripcion_snapshot'];
    }
    if ($complete && mti_chk_is_failure($response['tipo_respuesta_snapshot'], $value)) {
        if ((int)$response['hallazgos'] < 1) {
            $errors[] = 'La respuesta negativa requiere levantar un hallazgo: ' . $response['descripcion_snapshot'];
        }
        if ((int)$response['requiere_evidencia_si_falla_snapshot'] === 1
            && ((int)$response['hallazgos'] < 1 || (int)$response['evidencias'] < 1)) {
            $errors[] = 'La falla requiere hallazgo con evidencia: ' . $response['descripcion_snapshot'];
        }
    }
    $normalized[$id] = [$value, $note];
}
if ($errors) {
    $session->msg('d', implode(' | ', $errors));
    redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
}
$user = current_user();
$userId = (int)($user['id'] ?? 0);
$db->query('START TRANSACTION');
foreach ($normalized as $id => [$value, $note]) {
    $valueSql = $value === '' ? 'NULL' : mti_chk_q($value);
    $noteSql = $note === '' ? 'NULL' : mti_chk_q($note);
    $answered = $value === '' ? 'NULL' : (string)$userId;
    $date = $value === '' ? 'NULL' : 'NOW()';
    $db->query(
        "UPDATE activos_mantenimiento_checklist_respuestas"
        . " SET respuesta_valor={$valueSql},observacion={$noteSql},respondido_por={$answered},fecha_respuesta={$date}"
        . " WHERE id={$id} AND checklist_id={$checklistId} LIMIT 1"
    );
}
$state = $complete ? 'COMPLETADO' : 'EN_PROCESO';
$finish = $complete ? ',fecha_fin=NOW()' : '';
$db->query(
    "UPDATE activos_mantenimiento_checklists SET estado=" . mti_chk_q($state)
    . ",ejecutado_por={$userId},fecha_inicio=COALESCE(fecha_inicio,NOW()){$finish}"
    . " WHERE id={$checklistId} LIMIT 1"
);
$db->query('COMMIT');
$session->msg('s', $complete ? 'Checklist completado y bloqueado.' : 'Avance guardado.');
redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
