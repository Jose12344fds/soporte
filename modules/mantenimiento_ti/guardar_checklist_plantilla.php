<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'editar');
csrf_require();
mti_chk_require_schema();

$action = (string)($_POST['accion'] ?? '');
$user = current_user();
$userId = (int)($user['id'] ?? 0);

if ($action === 'crear_plantilla') {
    $name = trim((string)($_POST['nombre'] ?? ''));
    $description = trim((string)($_POST['descripcion'] ?? ''));
    $planId = (int)($_POST['plan_id'] ?? 0);
    if ($name === '' || mb_strlen($name) > 200) {
        $session->msg('d', 'Ingrese un nombre valido para la plantilla.');
        redirect('checklist_plantillas.php', false);
    }
    $planSql = 'NULL';
    if ($planId > 0) {
        $plan = $db->fetch_assoc($db->query(
            "SELECT id FROM activos_mantenimiento_planes WHERE id={$planId} LIMIT 1"
        ));
        if (!$plan) {
            $session->msg('d', 'El plan seleccionado no existe.');
            redirect('checklist_plantillas.php', false);
        }
        $planSql = (string)$planId;
    }
    $db->query(
        "INSERT INTO activos_mantenimiento_checklist_plantillas"
        . " (plan_id,nombre,descripcion,version,activo,creado_por,actualizado_por)"
        . " VALUES ({$planSql}," . mti_chk_q($name) . ',' . mti_chk_nullable($description)
        . ",1,1,{$userId},{$userId})"
    );
    $id = (int)$db->insert_id();
    $db->query(
        "UPDATE activos_mantenimiento_checklist_plantillas SET codigo="
        . mti_chk_q('CHK-TI-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT))
        . " WHERE id={$id} LIMIT 1"
    );
    $session->msg('s', 'Plantilla creada. Ahora agregue sus preguntas.');
    redirect('checklist_plantillas.php?plantilla=' . $id, false);
}

$templateId = (int)($_POST['plantilla_id'] ?? 0);
$template = $db->fetch_assoc($db->query(
    "SELECT * FROM activos_mantenimiento_checklist_plantillas WHERE id={$templateId} LIMIT 1"
));
if (!$template) {
    $session->msg('d', 'La plantilla indicada no existe.');
    redirect('checklist_plantillas.php', false);
}

if ($action === 'toggle_plantilla') {
    $active = (int)$template['activo'] ? 0 : 1;
    $db->query(
        "UPDATE activos_mantenimiento_checklist_plantillas"
        . " SET activo={$active},actualizado_por={$userId} WHERE id={$templateId} LIMIT 1"
    );
    $session->msg('s', $active ? 'Plantilla activada.' : 'Plantilla desactivada.');
    redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
}

if ($action === 'actualizar_plantilla') {
    if (mti_chk_template_used($templateId)) {
        $session->msg('d', 'La plantilla ya fue usada. Cree una nueva version para modificarla.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $name = trim((string)($_POST['nombre'] ?? ''));
    $description = trim((string)($_POST['descripcion'] ?? ''));
    $planId = (int)($_POST['plan_id'] ?? 0);
    if ($name === '' || mb_strlen($name) > 200) {
        $session->msg('d', 'Ingrese un nombre valido.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $planSql = 'NULL';
    if ($planId > 0) {
        $plan = $db->fetch_assoc($db->query(
            "SELECT id FROM activos_mantenimiento_planes WHERE id={$planId} LIMIT 1"
        ));
        if (!$plan) {
            $session->msg('d', 'El plan seleccionado no existe.');
            redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
        }
        $planSql = (string)$planId;
    }
    $db->query(
        "UPDATE activos_mantenimiento_checklist_plantillas SET plan_id={$planSql},"
        . " nombre=" . mti_chk_q($name) . ',descripcion=' . mti_chk_nullable($description)
        . ",actualizado_por={$userId} WHERE id={$templateId} LIMIT 1"
    );
    $session->msg('s', 'Plantilla actualizada.');
    redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
}

if ($action === 'agregar_item') {
    $description = trim((string)($_POST['descripcion'] ?? ''));
    $type = (string)($_POST['tipo_respuesta'] ?? '');
    $types = ['SI_NO_NA','CUMPLE_NO_CUMPLE_NA','NUMERICO','TEXTO'];
    if ($description === '' || mb_strlen($description) > 500 || !in_array($type, $types, true)) {
        $session->msg('d', 'Revise la pregunta y su tipo de respuesta.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $order = max(0, (int)($_POST['orden'] ?? 0));
    $required = isset($_POST['obligatorio']) ? 1 : 0;
    $evidence = isset($_POST['requiere_evidencia']) ? 1 : 0;
    $db->query(
        "INSERT INTO activos_mantenimiento_checklist_items"
        . " (plantilla_id,descripcion,tipo_respuesta,obligatorio,requiere_evidencia_si_falla,orden,activo)"
        . " VALUES ({$templateId}," . mti_chk_q($description) . ',' . mti_chk_q($type)
        . ",{$required},{$evidence},{$order},1)"
    );
    $itemId = (int)$db->insert_id();
    $db->query(
        "UPDATE activos_mantenimiento_checklist_items SET codigo="
        . mti_chk_q('ITM-' . str_pad((string)$itemId, 6, '0', STR_PAD_LEFT))
        . " WHERE id={$itemId} LIMIT 1"
    );
    $db->query(
        "INSERT INTO activos_mantenimiento_checklist_respuestas"
        . " (checklist_id,item_id,descripcion_snapshot,tipo_respuesta_snapshot,"
        . " obligatorio_snapshot,requiere_evidencia_si_falla_snapshot,orden_snapshot)"
        . " SELECT c.id,{$itemId}," . mti_chk_q($description) . ',' . mti_chk_q($type)
        . ",{$required},{$evidence},{$order}"
        . " FROM activos_mantenimiento_checklists c"
        . " WHERE c.plantilla_id={$templateId} AND c.estado IN ('PENDIENTE','EN_PROCESO')"
    );
    $session->msg('s', 'Punto agregado al checklist padre y a sus ejecuciones pendientes o en proceso.');
    redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
}

if ($action === 'toggle_item') {
    if (mti_chk_template_used($templateId)) {
        $session->msg('d', 'No se puede cambiar una plantilla que ya tiene ejecuciones.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $itemId = (int)($_POST['item_id'] ?? 0);
    $item = $db->fetch_assoc($db->query(
        "SELECT activo FROM activos_mantenimiento_checklist_items"
        . " WHERE id={$itemId} AND plantilla_id={$templateId} LIMIT 1"
    ));
    if (!$item) {
        $session->msg('d', 'La pregunta no pertenece a la plantilla.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $active = (int)$item['activo'] ? 0 : 1;
    $db->query(
        "UPDATE activos_mantenimiento_checklist_items SET activo={$active} WHERE id={$itemId} LIMIT 1"
    );
    $session->msg('s', $active ? 'Pregunta activada.' : 'Pregunta desactivada.');
    redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
}

if ($action === 'actualizar_item') {
    if (mti_chk_template_used($templateId)) {
        $session->msg('d', 'No se puede editar una plantilla que ya tiene ejecuciones.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $itemId = (int)($_POST['item_id'] ?? 0);
    $item = $db->fetch_assoc($db->query(
        "SELECT id FROM activos_mantenimiento_checklist_items"
        . " WHERE id={$itemId} AND plantilla_id={$templateId} LIMIT 1"
    ));
    $description = trim((string)($_POST['descripcion'] ?? ''));
    $type = (string)($_POST['tipo_respuesta'] ?? '');
    $types = ['SI_NO_NA','CUMPLE_NO_CUMPLE_NA','NUMERICO','TEXTO'];
    if (!$item || $description === '' || mb_strlen($description) > 500 || !in_array($type, $types, true)) {
        $session->msg('d', 'Revise los datos de la pregunta.');
        redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
    }
    $order = max(0, (int)($_POST['orden'] ?? 0));
    $required = isset($_POST['obligatorio']) ? 1 : 0;
    $evidence = isset($_POST['requiere_evidencia']) ? 1 : 0;
    $db->query(
        "UPDATE activos_mantenimiento_checklist_items SET descripcion=" . mti_chk_q($description)
        . ",tipo_respuesta=" . mti_chk_q($type) . ",obligatorio={$required},"
        . "requiere_evidencia_si_falla={$evidence},orden={$order} WHERE id={$itemId} LIMIT 1"
    );
    $session->msg('s', 'Pregunta actualizada.');
    redirect('checklist_plantillas.php?plantilla=' . $templateId, false);
}

$session->msg('d', 'Accion no valida.');
redirect('checklist_plantillas.php', false);
