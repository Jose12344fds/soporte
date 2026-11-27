<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');
ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'editar');
csrf_require();
mti_chk_require_schema();

$maintenanceId = (int)($_POST['mantenimiento_id'] ?? 0);
$templateId = (int)($_POST['plantilla_id'] ?? 0);
$order = mti_chk_order($maintenanceId);
$plan = mti_chk_plan_for_order($maintenanceId);
if (!$order) {
    $session->msg('d', 'La orden no existe.');
    redirect('index.php', false);
}
$template = $db->fetch_assoc($db->query(
    "SELECT * FROM activos_mantenimiento_checklist_plantillas"
    . " WHERE id={$templateId} AND activo=1 LIMIT 1"
));
$allowed = $template && (
    ($plan && (int)$template['plan_id'] === (int)$plan['plan_id'])
    || ($plan && empty($template['plan_id']))
    || (!$plan && empty($template['plan_id']))
);
if (!$allowed) {
    $session->msg('d', 'La plantilla no corresponde al plan o tipo de esta orden.');
    redirect('detalle.php?id=' . $maintenanceId, false);
}
$items = find_by_sql(
    "SELECT * FROM activos_mantenimiento_checklist_items"
    . " WHERE plantilla_id={$templateId} AND activo=1 ORDER BY orden,id"
);
if (!$items) {
    $session->msg('d', 'La plantilla no tiene preguntas activas.');
    redirect('detalle.php?id=' . $maintenanceId, false);
}
$user = current_user();
$userId = (int)($user['id'] ?? 0);
$db->query('START TRANSACTION');
$db->query(
    "INSERT IGNORE INTO activos_mantenimiento_checklists"
    . " (mantenimiento_id,plantilla_id,nombre_snapshot,descripcion_snapshot,version_snapshot,estado,asignado_por)"
    . " VALUES ({$maintenanceId},{$templateId}," . mti_chk_q($template['nombre']) . ','
    . mti_chk_nullable($template['descripcion']) . ',' . (int)$template['version']
    . ",'PENDIENTE',{$userId})"
);
$checklistId = (int)$db->insert_id();
if ($checklistId < 1) {
    $db->query('ROLLBACK');
    $session->msg('i', 'La plantilla ya estaba asignada a esta orden.');
    redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
}
foreach ($items as $item) {
    $db->query(
        "INSERT INTO activos_mantenimiento_checklist_respuestas"
        . " (checklist_id,item_id,descripcion_snapshot,tipo_respuesta_snapshot,"
        . " obligatorio_snapshot,requiere_evidencia_si_falla_snapshot,orden_snapshot)"
        . " VALUES ({$checklistId}," . (int)$item['id'] . ','
        . mti_chk_q($item['descripcion']) . ',' . mti_chk_q($item['tipo_respuesta']) . ','
        . (int)$item['obligatorio'] . ',' . (int)$item['requiere_evidencia_si_falla'] . ','
        . (int)$item['orden'] . ')'
    );
}
$db->query('COMMIT');
$session->msg('s', 'Checklist asignado con una copia independiente de sus preguntas.');
redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
