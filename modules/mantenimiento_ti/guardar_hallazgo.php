<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');
ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'editar');
csrf_require();
mti_chk_require_schema();

$maintenanceId = (int)($_POST['mantenimiento_id'] ?? 0);
$responseId = (int)($_POST['checklist_respuesta_id'] ?? 0);
$order = mti_chk_order($maintenanceId);
if (!$order) {
    $session->msg('d', 'La orden indicada no existe.');
    redirect('index.php', false);
}
if ($responseId > 0) {
    $belongs = $db->fetch_assoc($db->query(
        "SELECT r.id,r.tipo_respuesta_snapshot,r.descripcion_snapshot FROM activos_mantenimiento_checklist_respuestas r"
        . " INNER JOIN activos_mantenimiento_checklists c ON c.id=r.checklist_id"
        . " WHERE r.id={$responseId} AND c.mantenimiento_id={$maintenanceId} LIMIT 1"
    ));
    if (!$belongs) {
        $session->msg('d', 'La respuesta no pertenece a esta orden.');
        redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
    }
    $failureValue = $belongs['tipo_respuesta_snapshot'] === 'SI_NO_NA' ? 'NO' : 'NO_CUMPLE';
    $submittedValue = trim((string)($_POST['respuesta_estado'] ?? ''));
    if ($submittedValue !== $failureValue) {
        $session->msg('d', 'Primero marque el componente como Con falla para registrar el hallazgo.');
        redirect('detalle.php?id=' . $maintenanceId . '#checklists', false);
    }
}
$component = trim((string)($_POST['componente'] ?? ''));
$title = trim((string)($_POST['titulo'] ?? ''));
$description = trim((string)($_POST['descripcion'] ?? ''));
$criticality = (string)($_POST['criticidad'] ?? 'MEDIA');
$stop = isset($_POST['requiere_parada']) ? 1 : 0;
$immediate = trim((string)($_POST['accion_inmediata'] ?? ''));
if ($title === '' || $description === '' || !in_array($criticality, ['BAJA','MEDIA','ALTA','CRITICA'], true)) {
    $session->msg('d', 'Complete titulo, descripcion y criticidad del hallazgo.');
    redirect('detalle.php?id=' . $maintenanceId . '#hallazgos', false);
}
if (($criticality === 'CRITICA' || $stop) && $immediate === '') {
    $session->msg('d', 'Un hallazgo critico o con parada requiere una medida de control inmediata.');
    redirect('detalle.php?id=' . $maintenanceId . '#hallazgos', false);
}
$user = current_user();
$userId = (int)($user['id'] ?? 0);
$fileStored = null;
$db->query('START TRANSACTION');
try {
    if ($responseId > 0) {
        $db->query(
            "UPDATE activos_mantenimiento_checklist_respuestas"
            . " SET respuesta_valor=" . mti_chk_q($failureValue)
            . ",respondido_por={$userId},fecha_respuesta=NOW() WHERE id={$responseId} LIMIT 1"
        );
    }
    $responseSql = $responseId > 0 ? (string)$responseId : 'NULL';
    $db->query(
        "INSERT INTO activos_mantenimiento_hallazgos"
        . " (mantenimiento_id,checklist_respuesta_id,componente,titulo,descripcion,criticidad,estado,requiere_parada,detectado_por)"
        . " VALUES ({$maintenanceId},{$responseSql}," . mti_chk_nullable($component) . ','
        . mti_chk_q($title) . ',' . mti_chk_q($description) . ',' . mti_chk_q($criticality)
        . ",'ABIERTO',{$stop},{$userId})"
    );
    $findingId = (int)$db->insert_id();
    $code = 'HAL-TI-' . str_pad((string)$findingId, 6, '0', STR_PAD_LEFT);
    $db->query("UPDATE activos_mantenimiento_hallazgos SET codigo=" . mti_chk_q($code) . " WHERE id={$findingId}");
    mti_chk_hallazgo_history($findingId, 'CREACION', null, 'ABIERTO', 'Hallazgo registrado', $userId);
    if ($immediate !== '') {
        $db->query(
            "INSERT INTO activos_mantenimiento_hallazgo_acciones"
            . " (hallazgo_id,tipo,descripcion,estado,creado_por)"
            . " VALUES ({$findingId},'ACCION_INMEDIATA'," . mti_chk_q($immediate) . ",'EN_PROCESO',{$userId})"
        );
        $actionId = (int)$db->insert_id();
        mti_chk_hallazgo_history($findingId, 'ACCION_CREADA', null, 'EN_PROCESO', $immediate, $userId, $actionId);
    }
    if (!empty($_FILES['evidencia']['name'])) {
        $fileStored = mti_chk_store_upload($_FILES['evidencia'], $findingId, 'DETECCION');
        $db->query(
            "INSERT INTO activos_mantenimiento_hallazgo_evidencias"
            . " (hallazgo_id,etapa,tipo,archivo_ruta,archivo_nombre_original,archivo_mime,descripcion,subido_por)"
            . " VALUES ({$findingId},'DETECCION'," . mti_chk_q($fileStored['type']) . ','
            . mti_chk_q($fileStored['relative']) . ',' . mti_chk_q($fileStored['original']) . ','
            . mti_chk_q($fileStored['mime']) . ",'Evidencia inicial',{$userId})"
        );
    }
    $db->query('COMMIT');
    $session->msg('s', 'Hallazgo ' . $code . ' registrado.');
} catch (Throwable $e) {
    $db->query('ROLLBACK');
    if ($fileStored) @unlink(mti_chk_evidence_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileStored['relative']));
    $session->msg('d', 'No se pudo registrar el hallazgo: ' . $e->getMessage());
}
redirect('detalle.php?id=' . $maintenanceId . '#hallazgos', false);
