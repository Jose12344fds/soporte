<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_plan.php');
require_permiso('mantenimiento_ti', 'editar');

$id = (int)($_POST['ocurrencia_id'] ?? 0);
if ($id <= 0) {
    $session->msg('d', 'La ocurrencia indicada no es valida.');
    redirect('planes.php', false);
}

$result = $db->query(
    "SELECT id,plan_id,codigo,estado,mantenimiento_id FROM activos_mantenimiento_ocurrencias"
    . " WHERE id={$id} LIMIT 1"
);
$occurrence = $db->fetch_assoc($result);
if (!$occurrence) {
    $session->msg('d', 'La ocurrencia ya no existe.');
    redirect('planes.php', false);
}
if (!empty($occurrence['mantenimiento_id']) || $occurrence['estado'] !== 'PENDIENTE') {
    $session->msg('d', 'Solo se pueden eliminar ocurrencias pendientes y sin una orden vinculada.');
    redirect('planes.php', false);
}

$planId = (int)$occurrence['plan_id'];
$db->query('START TRANSACTION');
$db->query("DELETE FROM activos_mantenimiento_ocurrencia_historial WHERE ocurrencia_id={$id}");
$db->query(
    "DELETE FROM activos_mantenimiento_ocurrencias"
    . " WHERE id={$id} AND mantenimiento_id IS NULL AND estado='PENDIENTE' LIMIT 1"
);
if ($db->affected_rows() !== 1) {
    $db->query('ROLLBACK');
    $session->msg('d', 'La ocurrencia cambio mientras se procesaba y no fue eliminada.');
    redirect('planes.php', false);
}
$last = $db->fetch_assoc($db->query(
    "SELECT MAX(fecha_original) AS fecha FROM activos_mantenimiento_ocurrencias WHERE plan_id={$planId}"
));
$lastSql = empty($last['fecha']) ? 'NULL' : mti_plan_q($last['fecha']);
$db->query("UPDATE activos_mantenimiento_planes SET ultima_fecha_generada={$lastSql} WHERE id={$planId} LIMIT 1");
$db->query('COMMIT');

$session->msg('s', 'Ocurrencia ' . ($occurrence['codigo'] ?: '#' . $id) . ' eliminada correctamente.');
redirect('planes.php', false);
