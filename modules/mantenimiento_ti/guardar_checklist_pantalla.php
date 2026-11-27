<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist_pantalla.php');

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'crear');
csrf_require();

$maintenanceId = (int)($_POST['mantenimiento_id'] ?? 0);
$order = $maintenanceId > 0 ? mti_cp_order($maintenanceId) : null;
$criteria = mti_cp_criteria();
$current = current_user();
$userId = (int)($current['id'] ?? 0);
$userName = trim((string)($current['name'] ?? ''));
$inspectedUser = trim((string)($_POST['usuario_inspeccionado'] ?? ''));
$area = trim((string)($_POST['area'] ?? ''));
$states = $_POST['estado'] ?? [];
$observations = $_POST['observacion'] ?? [];
$photos = mti_cp_normalize_files($_FILES['fotos'] ?? []);

try {
    if (!$order) throw new RuntimeException('Seleccione una orden de mantenimiento válida.');
    if ($inspectedUser === '' || mb_strlen($inspectedUser) > 150) throw new RuntimeException('Ingrese un usuario inspeccionado válido.');
    if ($area === '' || mb_strlen($area) > 100) throw new RuntimeException('Ingrese el área del usuario inspeccionado.');
    if ($userId <= 0 || $userName === '') throw new RuntimeException('No se pudo identificar al responsable de la inspección.');
    $duplicate = $db->fetch_assoc($db->query("SELECT id FROM mti_checklist_pantalla WHERE mantenimiento_id={$maintenanceId} LIMIT 1"));
    if ($duplicate) throw new RuntimeException('Esta orden ya tiene un checklist de escritorio y pantalla limpia.');

    foreach ($criteria as $number => $criterion) {
        $state = (string)($states[$number] ?? '');
        $observation = trim((string)($observations[$number] ?? ''));
        if (!in_array($state, ['CUMPLE','NO_CUMPLE','NO_APLICA'], true)) {
            throw new RuntimeException('Complete el estado de los 12 criterios.');
        }
        if ($state === 'NO_CUMPLE' && $observation === '') {
            throw new RuntimeException('El criterio ' . $number . ' requiere una observación porque está marcado como No cumple.');
        }
        if (mb_strlen($observation) > 1000) throw new RuntimeException('La observación del criterio ' . $number . ' es demasiado extensa.');
    }
    if (count($photos) > 4) throw new RuntimeException('La inspección permite como máximo 4 fotografías generales.');
} catch (Throwable $error) {
    $session->msg('d', $error->getMessage());
    redirect('checklist_pantalla_form.php?mantenimiento_id=' . $maintenanceId, false);
}

$storedFiles = [];
$db->query('START TRANSACTION');
try {
    $db->query(
        "INSERT INTO mti_checklist_pantalla "
        . "(mantenimiento_id,fecha_inspeccion,usuario_inspeccionado,area,equipo_nombre,responsable_id,responsable_nombre,creado_por) VALUES ("
        . $maintenanceId . ",NOW()," . mti_cp_q($inspectedUser) . ',' . mti_cp_q($area) . ',' . mti_cp_q($order['Host']) . ','
        . $userId . ',' . mti_cp_q($userName) . ',' . $userId . ')'
    );
    $checklistId = (int)$db->insert_id();

    foreach ($criteria as $number => $criterion) {
        $state = (string)$states[$number];
        $observation = trim((string)($observations[$number] ?? ''));
        $observationSql = $observation === '' ? 'NULL' : mti_cp_q($observation);
        $db->query(
            "INSERT INTO mti_checklist_pantalla_respuestas (checklist_id,numero,criterio_snapshot,estado,observacion) VALUES ("
            . $checklistId . ',' . $number . ',' . mti_cp_q($criterion) . ',' . mti_cp_q($state) . ',' . $observationSql . ')'
        );
    }
    foreach ($photos as $file) {
        $stored = mti_cp_store_photo($file, $checklistId, 0);
        $storedFiles[] = mti_cp_storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $stored['ruta']);
        $db->query(
            "INSERT INTO mti_checklist_pantalla_fotos (checklist_id,respuesta_id,ruta,nombre_original,mime,tamano_bytes,creado_por) VALUES ("
            . $checklistId . ",NULL," . mti_cp_q($stored['ruta']) . ',' . mti_cp_q($stored['nombre_original']) . ','
            . mti_cp_q($stored['mime']) . ',' . (int)$stored['tamano'] . ',' . $userId . ')'
        );
    }
    $db->query('COMMIT');
    $session->msg('s', 'Checklist de escritorio y pantalla limpia guardado correctamente.');
    redirect('checklist_pantalla_detalle.php?id=' . $checklistId, false);
} catch (Throwable $error) {
    $db->query('ROLLBACK');
    foreach ($storedFiles as $path) if (is_file($path)) @unlink($path);
    $session->msg('d', 'No se pudo guardar el checklist: ' . $error->getMessage());
    redirect('checklist_pantalla_form.php?mantenimiento_id=' . $maintenanceId, false);
}
