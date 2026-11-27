<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'editar');

$maintenanceId = (int)($_POST['mantenimiento_id'] ?? 0);
$type = strtoupper(trim((string)($_POST['tipo'] ?? 'ACTIVIDAD')));
$description = mb_substr(trim((string)($_POST['descripcion'] ?? '')), 0, 500);
$resultText = mb_substr(trim((string)($_POST['resultado'] ?? '')), 0, 250);
$quantity = max(0, (float)str_replace(',', '.', (string)($_POST['cantidad'] ?? '1')));
$unitCost = max(0, (float)str_replace(',', '.', (string)($_POST['costo_unitario'] ?? '0')));
$allowed = ['ACTIVIDAD', 'REPUESTO', 'SERVICIO'];

$orderResult = $db->query("SELECT id, estado FROM activos_mantenimientos WHERE id = {$maintenanceId} LIMIT 1");
$order = $db->fetch_assoc($orderResult);
if (!$order || $order['estado'] === 'CANCELADO' || !in_array($type, $allowed, true) || $description === '' || $quantity <= 0) {
    $session->msg('d', 'No se pudo registrar el detalle. Revise la orden y los datos ingresados.');
    redirect('detalle.php?id=' . $maintenanceId, false);
}

$operator = current_user();
$operatorId = (int)($operator['id'] ?? 0);
$descriptionSql = $db->escape($description);
$resultSql = $resultText === '' ? 'NULL' : "'".$db->escape($resultText)."'";
$quantitySql = number_format($quantity, 2, '.', '');
$unitCostSql = number_format($unitCost, 2, '.', '');

$db->query("INSERT INTO activos_mantenimiento_detalle
  (mantenimiento_id, tipo, descripcion, cantidad, costo_unitario, resultado, creado_por)
  VALUES ({$maintenanceId}, '{$type}', '{$descriptionSql}', {$quantitySql}, {$unitCostSql}, {$resultSql}, {$operatorId})");
$historyText = $db->escape(mb_substr('Se agrego ' . $type . ': ' . $description, 0, 500));
$db->query("INSERT INTO activos_mantenimiento_historial
  (mantenimiento_id, accion, estado_anterior, estado_nuevo, comentario, registrado_por)
  VALUES ({$maintenanceId}, 'DETALLE_AGREGADO', '{$order['estado']}', '{$order['estado']}',
    '{$historyText}', {$operatorId})");

$session->msg('s', 'Detalle agregado correctamente.');
redirect('detalle.php?id=' . $maintenanceId, false);
?>
