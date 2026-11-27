<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones.php');
require_permiso('accesos_usuario', 'ver');
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0 || !f08_get_record($id)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Ficha no encontrada.']);
    exit;
}
$rows = find_by_sql("SELECT h.`accion`,h.`detalle`,h.`estado_anterior`,h.`estado_nuevo`,
  DATE_FORMAT(h.`created_at`,'%d/%m/%Y %H:%i') AS created_at,u.`name` AS registrado_por_nombre
  FROM `usuariosf08_historial` h LEFT JOIN `users` u ON u.`id`=h.`registrado_por`
  WHERE h.`registro_id`={$id} ORDER BY h.`id` DESC LIMIT 30");
echo json_encode(['success' => true, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
?>
