<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones.php');
require_permiso('accesos_usuario', 'editar');

$id = (int)($_POST['id'] ?? 0);
$status = (int)($_POST['estado'] ?? -1);
if ($id <= 0 || !in_array($status, [0,1], true)) {
    $session->msg('d', 'La solicitud de cambio de estado no es valida.');
    redirect('index.php', false);
}
$record = f08_get_record($id);
if (!$record) {
    $session->msg('d', 'La ficha ya no existe.');
    redirect('index.php', false);
}
if ((int)$record['estado_f8'] === $status) {
    $session->msg('i', 'La ficha ya se encuentra en ese estado.');
    redirect('index.php', false);
}
if ($status === 1 && !empty($record['user_id'])) {
    $userId = (int)$record['user_id'];
    $duplicate = $db->fetch_assoc($db->query("SELECT `ID_Usuariosf08` FROM `usuariosf08`
      WHERE `user_id`={$userId} AND `estado_f8`=1 AND `ID_Usuariosf08`<>{$id} LIMIT 1"));
    if ($duplicate) {
        $session->msg('d', 'No se puede reactivar: el colaborador ya tiene otra ficha activa.');
        redirect('index.php', false);
    }
}

$operator = current_user();
$operatorId = (int)($operator['id'] ?? 0);
$oldStatus = (int)$record['estado_f8'];
$db->query('START TRANSACTION');
$db->query("UPDATE `usuariosf08` SET `estado_f8`={$status},`updated_by`={$operatorId},`updated_at`=NOW() WHERE `ID_Usuariosf08`={$id} LIMIT 1");
f08_audit($id, $status === 1 ? 'REACTIVACION' : 'DESACTIVACION', $status === 1 ? 'Ficha reactivada' : 'Ficha desactivada sin eliminar el historial', $oldStatus, $status);
$db->query('COMMIT');
$session->msg('s', $status === 1 ? 'Ficha reactivada correctamente.' : 'Ficha desactivada. El historial se conserva.');
redirect('index.php', false);
?>
