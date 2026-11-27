<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_plan.php');
require_permiso('mantenimiento_ti', 'editar');

$id = (int)($_POST['plan_id'] ?? 0);
$plan = mti_plan_find($id);
if (!$plan || !(int)$plan['activo']) {
    $session->msg('d', 'El plan no existe o esta inactivo.');
    redirect('planes.php', false);
}
$created = mti_generate_occurrences($plan);
$session->msg($created > 0 ? 's' : 'i', $created > 0
    ? "Se generaron {$created} nueva(s) ocurrencia(s)."
    : 'El plan ya tiene todas sus ocurrencias dentro del horizonte configurado.');
redirect('planes.php', false);
