<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_plan.php');

$id = (int)($_POST['id'] ?? 0);
require_permiso('mantenimiento_ti', $id > 0 ? 'editar' : 'crear');

$text = function(string $key, int $max): string {
    return mb_substr(trim((string)($_POST[$key] ?? '')), 0, $max);
};
$date = function(string $key): ?string {
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '') return null;
    $parsed = DateTime::createFromFormat('!Y-m-d', $value);
    return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
};

$machineId = (int)($_POST['IdMaquina'] ?? 0);
$name = $text('nombre', 150);
$type = strtoupper($text('tipo', 20));
$priority = strtoupper($text('prioridad', 15));
$frequency = strtoupper($text('frecuencia', 20));
$frequencyMap = [
    'DIARIO' => [1, 'DIA'],
    'SEMANAL' => [1, 'SEMANA'],
    'MENSUAL' => [1, 'MES'],
    'TRIMESTRAL' => [3, 'MES'],
    'CUATRIMESTRAL' => [4, 'MES'],
    'SEMESTRAL' => [6, 'MES'],
    'ANUAL' => [1, 'ANIO'],
];
$interval = $frequencyMap[$frequency][0] ?? 0;
$unit = $frequencyMap[$frequency][1] ?? '';
$start = $date('fecha_inicio');
$firstScheduled = $date('primera_fecha_programada');
$finish = $date('fecha_fin');
$instruction = $text('instruccion', 20000);
$automatic = isset($_POST['generar_automatico']) ? 1 : 0;
$active = isset($_POST['activo']) ? 1 : 0;

$types = ['PREVENTIVO','CORRECTIVO','DIAGNOSTICO','MEJORA'];
$priorities = ['BAJA','MEDIA','ALTA','CRITICA'];
$frequencies = array_keys($frequencyMap);
if ($name === '' || $instruction === '' || !$start || !$firstScheduled || $machineId <= 0
    || !in_array($type, $types, true) || !in_array($priority, $priorities, true)
    || !in_array($frequency, $frequencies, true)
    || ($finish && $finish < $start)
    || $firstScheduled < $start
    || ($finish && $firstScheduled > $finish)) {
    $session->msg('d', 'Revise los datos obligatorios y el rango de fechas del plan.');
    redirect('planes.php', false);
}

$machine = $db->query("SELECT IdMaquina FROM maquina WHERE IdMaquina={$machineId} LIMIT 1");
if ($db->num_rows($machine) === 0) {
    $session->msg('d', 'El equipo seleccionado no existe.');
    redirect('planes.php', false);
}

$user = current_user();
$userId = (int)($user['id'] ?? 0);
$existing = $id > 0 ? mti_plan_find($id) : null;
if ($id > 0 && !$existing) {
    $session->msg('d', 'El plan no existe.');
    redirect('planes.php', false);
}

if ($existing) {
    $countResult = $db->query("SELECT COUNT(*) AS total FROM activos_mantenimiento_ocurrencias WHERE plan_id={$id}");
    $countRow = $db->fetch_assoc($countResult);
    $occurrenceCount = (int)$countRow['total'];
    if ($occurrenceCount > 0) {
        $machineId = (int)$existing['IdMaquina'];
        $frequency = $existing['frecuencia'];
        $interval = (int)$existing['intervalo'];
        $unit = $existing['unidad_intervalo'];
        $start = $existing['fecha_inicio'];
        $firstScheduled = $existing['primera_fecha_programada'] ?: $existing['fecha_inicio'];
        $finish = $existing['fecha_fin'];
    }
    $db->query(
        "UPDATE activos_mantenimiento_planes SET IdMaquina={$machineId},nombre=" . mti_plan_q($name)
        . ",tipo=" . mti_plan_q($type) . ",prioridad=" . mti_plan_q($priority)
        . ",frecuencia=" . mti_plan_q($frequency) . ",intervalo={$interval},unidad_intervalo=" . mti_plan_q($unit)
        . ",fecha_inicio=" . mti_plan_q($start)
        . ",primera_fecha_programada=" . mti_plan_q($firstScheduled)
        . ",fecha_fin=" . mti_plan_nullable($finish)
        . ",instruccion=" . mti_plan_q($instruction) . ",tecnico_predeterminado=NULL"
        . ",generar_automatico={$automatic},activo={$active},actualizado_por={$userId} WHERE id={$id} LIMIT 1"
    );
    $created = 0;
    if ($occurrenceCount === 0 && $active === 1) {
        $db->query(
            "UPDATE activos_mantenimiento_planes SET ultima_fecha_generada=NULL WHERE id={$id} LIMIT 1"
        );
        $updatedPlan = mti_plan_find($id);
        $created = $updatedPlan ? mti_generate_occurrences($updatedPlan) : 0;
    }
    $message = $created > 0
        ? "Plan actualizado y {$created} ocurrencia(s) generada(s) con la nueva frecuencia."
        : 'Plan actualizado correctamente.';
    $session->msg('s', $message);
} else {
    $db->query(
        "INSERT INTO activos_mantenimiento_planes"
        . " (IdMaquina,nombre,tipo,prioridad,frecuencia,intervalo,unidad_intervalo,fecha_inicio,primera_fecha_programada,fecha_fin,instruccion,"
        . "tecnico_predeterminado,generar_automatico,activo,creado_por,actualizado_por) VALUES ("
        . "{$machineId}," . mti_plan_q($name) . ',' . mti_plan_q($type) . ',' . mti_plan_q($priority) . ','
        . mti_plan_q($frequency) . ",{$interval}," . mti_plan_q($unit) . ',' . mti_plan_q($start) . ','
        . mti_plan_q($firstScheduled) . ','
        . mti_plan_nullable($finish) . ',' . mti_plan_q($instruction) . ',NULL'
        . ",{$automatic},{$active},{$userId},{$userId})"
    );
    $id = (int)$db->insert_id();
    $code = 'MTIP-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    $db->query("UPDATE activos_mantenimiento_planes SET codigo=" . mti_plan_q($code) . " WHERE id={$id} LIMIT 1");
    $plan = mti_plan_find($id);
    $created = $active === 1 && $plan ? mti_generate_occurrences($plan) : 0;
    $message = $active === 1
        ? "Plan creado y {$created} ocurrencia(s) generada(s)."
        : 'Plan creado como inactivo, sin generar ocurrencias.';
    $session->msg('s', $message);
}
redirect('planes.php', false);
