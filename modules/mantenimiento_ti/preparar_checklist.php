<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'editar');
csrf_validate_or_fail();
mti_chk_require_schema();

$maintenanceId = (int)($_POST['mantenimiento_id'] ?? 0);
$order = mti_chk_order($maintenanceId);
if (!$order) {
    $session->msg('d', 'La orden de mantenimiento no existe.');
    redirect('index.php', false);
}

$existing = $db->fetch_assoc($db->query(
    "SELECT id FROM activos_mantenimiento_checklists"
    . " WHERE mantenimiento_id={$maintenanceId} ORDER BY id LIMIT 1"
));
if ($existing) {
    redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
}

$plan = mti_chk_plan_for_order($maintenanceId);
$planId = $plan ? (int)$plan['plan_id'] : 0;
$templateCondition = $planId > 0
    ? "(p.plan_id={$planId} OR p.plan_id IS NULL)"
    : 'p.plan_id IS NULL';
$templateOrder = $planId > 0
    ? "CASE WHEN p.plan_id={$planId} THEN 0 ELSE 1 END,p.id DESC"
    : 'p.id DESC';
$template = $db->fetch_assoc($db->query(
    "SELECT p.* FROM activos_mantenimiento_checklist_plantillas p"
    . " WHERE p.activo=1 AND {$templateCondition}"
    . " AND EXISTS (SELECT 1 FROM activos_mantenimiento_checklist_items i"
    . " WHERE i.plantilla_id=p.id AND i.activo=1)"
    . " ORDER BY {$templateOrder} LIMIT 1"
));
if (!$template) {
    $session->msg('d', 'No existe un checklist predefinido para esta orden. Cree uno general o vinculado a su plan.');
    redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
}

$templateId = (int)$template['id'];
$items = find_by_sql(
    "SELECT * FROM activos_mantenimiento_checklist_items"
    . " WHERE plantilla_id={$templateId} AND activo=1 ORDER BY orden,id"
);
$user = current_user();
$userId = (int)($user['id'] ?? 0);
$db->query('START TRANSACTION');
try {
    $db->query(
        "INSERT INTO activos_mantenimiento_checklists"
        . " (mantenimiento_id,plantilla_id,nombre_snapshot,descripcion_snapshot,version_snapshot,estado,asignado_por)"
        . " VALUES ({$maintenanceId},{$templateId}," . mti_chk_q($template['nombre']) . ','
        . mti_chk_nullable($template['descripcion']) . ',' . (int)$template['version']
        . ",'PENDIENTE',{$userId})"
    );
    $checklistId = (int)$db->insert_id();
    foreach ($items as $item) {
        $db->query(
            "INSERT INTO activos_mantenimiento_checklist_respuestas"
            . " (checklist_id,item_id,descripcion_snapshot,tipo_respuesta_snapshot,"
            . " obligatorio_snapshot,requiere_evidencia_si_falla_snapshot,orden_snapshot)"
            . " VALUES ({$checklistId}," . (int)$item['id'] . ',' . mti_chk_q($item['descripcion']) . ','
            . mti_chk_q($item['tipo_respuesta']) . ',' . (int)$item['obligatorio'] . ','
            . (int)$item['requiere_evidencia_si_falla'] . ',' . (int)$item['orden'] . ')'
        );
    }
    $db->query('COMMIT');
    $session->msg('s', 'Checklist predefinido cargado. Verifique los puntos y cambie solamente los que presentan falla.');
} catch (Throwable $error) {
    $db->query('ROLLBACK');
    $session->msg('d', 'No se pudo preparar el checklist: ' . $error->getMessage());
}
redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
