<?php
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones.php');

$id = (int)($_POST['id'] ?? 0);
require_permiso('accesos_usuario', $id > 0 ? 'editar' : 'crear');

$text = function(string $key, int $max): string {
    return mb_substr(trim((string)($_POST[$key] ?? '')), 0, $max);
};
$date = function(string $key): ?string {
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '') return null;
    $parsed = DateTime::createFromFormat('!Y-m-d', $value);
    return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
};
$choice = function(string $key, array $allowed, string $default) use ($text): string {
    $value = $text($key, 100);
    return in_array($value, $allowed, true) ? $value : $default;
};

$userId = (int)($_POST['user_id'] ?? 0);
$positionId = max(0, (int)($_POST['puesto_id'] ?? 0));
$machineId = (int)($_POST['maquina_id'] ?? 0);
$sapAccountId = (int)($_POST['sap_account_id'] ?? 0);
$orbisAccountId = (int)($_POST['orbis_account_id'] ?? 0);
$registeredAt = $date('FechaRegistro');
$workStartedAt = $date('FechaLabores');
$type = $choice('TipoRegis', ['Nuevo','Modificado','Cesado'], 'Nuevo');
$name = $text('NomApe', 150);
$position = '';
$sambaUser = $text('UsuarioTamadom', 50);
$email = $text('Correo', 100);
$phone = $text('Celular_f8', 20);
$foldersWrite = $text('CarpetasCompartidasM', 10000);
$foldersRead = $text('CarpetasCompartidasV', 10000);
$mobileEmail = $choice('corrcelu_f8', ['SI','NO'], 'NO');
$usb = $choice('ausb_f8', ['SI','NO'], 'NO');
$navigation = $choice('ninavega_f8', ['SIN_ACCESO','Nivel_I','Nivel_II','Nivel_III'], 'SIN_ACCESO');
$mailLevel = $choice('nivcorr_f8', ['SIN_ACCESO','Nivel_I','Nivel_II','Nivel_III'], 'SIN_ACCESO');
$bridgeEmail = $text('corrpuen_f8', 100);
$basicSoftware = $choice('softwarebasi_f8', ['SI','NO'], 'NO');
$status = (int)($_POST['estado_f8'] ?? 1) === 1 ? 1 : 0;
$notes = $text('Observaciones', 255);

$applicationFields = [
    'sap' => ['enabled' => 'sap_f8', 'user' => 'ususap_f8', 'role' => 'saplicen_f8'],
    'rco' => ['enabled' => 'requerimient_f8', 'user' => 'usureque_f8', 'role' => 'requeutili_f8'],
    'orbis' => ['enabled' => 'orbis_f8', 'user' => 'usuorb_f8', 'role' => 'utilidadorb_f8'],
    'apptama' => ['enabled' => 'apptam_f8', 'user' => 'usuapptam_f8', 'role' => 'utilidapptam_f8'],
];
$apps = [];
foreach ($applicationFields as $key => $fields) {
    $enabled = $choice($fields['enabled'], ['SI','NO'], 'NO');
    $apps[$key] = [
        'enabled' => $enabled,
        'user' => $enabled === 'SI' ? $text($fields['user'], 30) : '',
        'role' => $enabled === 'SI' ? $text($fields['role'], 50) : '',
    ];
}

if ($userId <= 0 || $positionId <= 0 || !$registeredAt || $name === '' || $sambaUser === '') {
    $session->msg('d', 'Complete el colaborador, el cargo de la matriz, la fecha, el nombre y la cuenta Samba.');
    redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $session->msg('d', 'El correo indicado no tiene un formato valido.');
    redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
}

$user = $db->fetch_assoc($db->query("SELECT `id` FROM `users` WHERE `id`={$userId} LIMIT 1"));
if (!$user) {
    $session->msg('d', 'El colaborador seleccionado ya no existe.');
    redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
}
if ($positionId > 0) {
    $positionRecord = $db->fetch_assoc($db->query("SELECT `id`,`nombre` FROM `f08_puestos` WHERE `id`={$positionId} AND `activo`=1 LIMIT 1"));
    if (!$positionRecord) {
        $session->msg('d', 'El cargo seleccionado ya no esta disponible en la matriz de puestos.');
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
    $position = mb_substr(trim((string)$positionRecord['nombre']), 0, 150);
}

// Si se eligio una cuenta vinculada, sus datos prevalecen. Sin vinculacion se
// conserva el registro manual de la ficha; no se crea ni modifica Licencias SAP.
if ($sapAccountId > 0) {
    $sapAccount = $db->fetch_assoc($db->query("SELECT s.`IdUsuSap`,s.`UsuarioSap`,COALESCE(t.`Licencia`,'') AS `Licencia`
      FROM `usuariossap` s
      LEFT JOIN `usuariotreminalsap` t ON t.`IdUsuTerminal`=(
        SELECT MAX(t2.`IdUsuTerminal`) FROM `usuariotreminalsap` t2 WHERE t2.`UsuTerminal`=s.`CuentaTer`
      )
      WHERE s.`IdUsuSap`={$sapAccountId} AND s.`UserId`={$userId} AND s.`Estado`=1 LIMIT 1"));
    if (!$sapAccount) {
        $session->msg('d', 'La cuenta SAP seleccionada no pertenece al colaborador o ya no esta activa.');
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
    $apps['sap'] = [
        'enabled' => 'SI',
        'user' => mb_substr((string)$sapAccount['UsuarioSap'], 0, 30),
        'role' => mb_substr((string)$sapAccount['Licencia'], 0, 50),
    ];
} elseif ($apps['sap']['enabled'] === 'SI') {
    $sapAccountId = 0;
    if ($apps['sap']['user'] === '') {
        $session->msg('d', 'Indique el usuario SAP que utiliza el colaborador o seleccione una cuenta vinculada.');
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
} else {
    $sapAccountId = 0;
    $apps['sap'] = ['enabled' => 'NO', 'user' => '', 'role' => ''];
}

// Orbis se vincula por la coincidencia del nombre del colaborador. Si no se
// selecciona una cuenta de Licencias Orbis, se conserva lo escrito manualmente.
if ($orbisAccountId > 0) {
    $orbisAccount = $db->fetch_assoc($db->query("SELECT o.`IdUsuOrbis`,o.`CuentaOrbis`,o.`Utilidad`
      FROM `usuariosorbis` o
      INNER JOIN `users` u ON u.`id`=o.`UserId` OR (o.`UserId` IS NULL AND TRIM(LOWER(u.`name`))=TRIM(LOWER(o.`NomUsuario`)))
      WHERE o.`IdUsuOrbis`={$orbisAccountId} AND u.`id`={$userId} AND o.`Estado`=1 LIMIT 1"));
    if (!$orbisAccount) {
        $session->msg('d', 'La cuenta Orbis seleccionada no pertenece al colaborador o ya no esta activa.');
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
    $apps['orbis'] = [
        'enabled' => 'SI',
        'user' => mb_substr((string)$orbisAccount['CuentaOrbis'], 0, 30),
        'role' => mb_substr((string)$orbisAccount['Utilidad'], 0, 50),
    ];
} elseif ($apps['orbis']['enabled'] === 'SI') {
    $orbisAccountId = 0;
    if ($apps['orbis']['user'] === '') {
        $session->msg('d', 'Indique la cuenta Orbis que utiliza el colaborador o seleccione una cuenta vinculada.');
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
} else {
    $orbisAccountId = 0;
    $apps['orbis'] = ['enabled' => 'NO', 'user' => '', 'role' => ''];
}

$machineHost = '';
$machineSql = 'NULL';
if ($machineId > 0) {
    $machine = $db->fetch_assoc($db->query("SELECT `IdMaquina`,`Host` FROM `maquina` WHERE `IdMaquina`={$machineId} LIMIT 1"));
    if (!$machine) {
        $session->msg('d', 'El equipo seleccionado ya no existe.');
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
    $machineHost = (string)$machine['Host'];
    $machineSql = (string)$machineId;
}

$existing = $id > 0 ? f08_get_record($id) : null;
if ($id > 0 && !$existing) {
    $session->msg('d', 'La ficha que intenta editar ya no existe.');
    redirect('index.php', false);
}
if ($status === 1) {
    $duplicate = $db->fetch_assoc($db->query("SELECT `ID_Usuariosf08` FROM `usuariosf08`
      WHERE `user_id`={$userId} AND `estado_f8`=1 AND `ID_Usuariosf08`<>" . ($id > 0 ? $id : 0) . " LIMIT 1"));
    if ($duplicate) {
        $otherId = (int)$duplicate['ID_Usuariosf08'];
        $session->msg('d', "El colaborador ya tiene una ficha activa F08-" . str_pad((string)$otherId, 4, '0', STR_PAD_LEFT) . ". Edite o desactive esa ficha antes de crear otra.");
        redirect('formulario.php' . ($id > 0 ? '?id=' . $id : ''), false);
    }
}

$operator = current_user();
$operatorId = (int)($operator['id'] ?? 0);
$workDateSql = $workStartedAt ? f08_q($workStartedAt) : 'NULL';
$fields = [
    'user_id' => (string)$userId, 'puesto_id' => $positionId > 0 ? (string)$positionId : 'NULL', 'maquina_id' => $machineSql,
    'sap_account_id' => $sapAccountId > 0 ? (string)$sapAccountId : 'NULL',
    'orbis_account_id' => $orbisAccountId > 0 ? (string)$orbisAccountId : 'NULL',
    'FechaRegistro' => f08_q($registeredAt), 'TipoRegis' => f08_q($type),
    'NomApe' => f08_q($name), 'Cargo' => f08_q($position),
    'UsuarioTamadom' => f08_q($sambaUser), 'NumPC' => f08_q($machineHost),
    'Correo' => f08_q($email), 'FechaLabores' => $workDateSql, 'Celular_f8' => f08_q($phone),
    'CarpetasCompartidasM' => f08_q($foldersWrite), 'CarpetasCompartidasV' => f08_q($foldersRead),
    'corrcelu_f8' => f08_q($mobileEmail), 'ausb_f8' => f08_q($usb),
    'ninavega_f8' => f08_q($navigation), 'nivcorr_f8' => f08_q($mailLevel),
    'corrpuen_f8' => f08_q($bridgeEmail), 'softwarebasi_f8' => f08_q($basicSoftware),
    'sap_f8' => f08_q($apps['sap']['enabled']), 'saplicen_f8' => f08_q($apps['sap']['role']), 'ususap_f8' => f08_q($apps['sap']['user']),
    'requerimient_f8' => f08_q($apps['rco']['enabled']), 'requeutili_f8' => f08_q($apps['rco']['role']), 'usureque_f8' => f08_q($apps['rco']['user']),
    'orbis_f8' => f08_q($apps['orbis']['enabled']), 'utilidadorb_f8' => f08_q($apps['orbis']['role']), 'usuorb_f8' => f08_q($apps['orbis']['user']),
    'apptam_f8' => f08_q($apps['apptama']['enabled']), 'utilidapptam_f8' => f08_q($apps['apptama']['role']), 'usuapptam_f8' => f08_q($apps['apptama']['user']),
    'Observaciones' => f08_q($notes), 'estado_f8' => (string)$status,
];

$db->query('START TRANSACTION');
if ($existing) {
    $assignments = [];
    foreach ($fields as $column => $sqlValue) $assignments[] = "`{$column}`={$sqlValue}";
    $assignments[] = "`updated_by`={$operatorId}";
    $assignments[] = "`updated_at`=NOW()";
    $db->query("UPDATE `usuariosf08` SET " . implode(',', $assignments) . " WHERE `ID_Usuariosf08`={$id} LIMIT 1");

    $labels = ['user_id'=>'colaborador','puesto_id'=>'plantilla del puesto','maquina_id'=>'equipo','sap_account_id'=>'cuenta SAP vinculada','orbis_account_id'=>'cuenta Orbis vinculada','UsuarioTamadom'=>'cuenta Samba','CarpetasCompartidasM'=>'carpetas con modificacion','CarpetasCompartidasV'=>'carpetas de lectura','sap_f8'=>'SAP','requerimient_f8'=>'RCO','orbis_f8'=>'Orbis','apptam_f8'=>'App TAMA','estado_f8'=>'estado'];
    $changed = [];
    foreach ($labels as $column => $label) {
        $newComparable = $column === 'maquina_id' ? ($machineId ?: '') : ($column === 'puesto_id' ? ($positionId ?: '') : ($column === 'sap_account_id' ? ($sapAccountId ?: '') : ($column === 'orbis_account_id' ? ($orbisAccountId ?: '') : ($column === 'estado_f8' ? $status : ($column === 'user_id' ? $userId : trim((string)($_POST[$column] ?? '')))))));
        if ((string)($existing[$column] ?? '') !== (string)$newComparable) $changed[] = $label;
    }
    f08_audit($id, 'ACTUALIZACION', $changed ? 'Campos actualizados: ' . implode(', ', $changed) : 'Ficha guardada sin cambios relevantes', (int)$existing['estado_f8'], $status);
    $message = 'Ficha F08 actualizada correctamente.';
} else {
    $columns = array_keys($fields);
    $values = array_values($fields);
    $columns[] = 'created_by'; $values[] = (string)$operatorId;
    $columns[] = 'updated_by'; $values[] = (string)$operatorId;
    $columns[] = 'created_at'; $values[] = 'NOW()';
    $columns[] = 'updated_at'; $values[] = 'NOW()';
    $quotedColumns = array_map(function($column){ return "`{$column}`"; }, $columns);
    $db->query("INSERT INTO `usuariosf08` (" . implode(',', $quotedColumns) . ") VALUES (" . implode(',', $values) . ")");
    $id = (int)$db->insert_id();
    f08_audit($id, 'CREACION', 'Ficha T-TI-F-08 creada', null, $status);
    $message = 'Ficha F08 creada correctamente.';
}
$db->query('COMMIT');
$session->msg('s', $message);
redirect('index.php', false);
?>
