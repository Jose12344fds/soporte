<?php
$page_title = 'Nuevo acceso VPN';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('vpn', 'crear');

function vpn_add_value(string $name, int $max = 255): string {
    return mb_substr(trim((string)($_POST[$name] ?? '')), 0, $max, 'UTF-8');
}
function vpn_add_time(string $value): bool {
    return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value);
}
function vpn_add_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$values = [
    'Solicitante' => '', 'Empresa' => '', 'Fecha' => date('Y-m-d'), 'Hora' => date('H:i'),
    'Nivel_Usu' => 'BASICO', 'Tiempo' => '01:00', 'Culminado' => 'NO', 'Token' => '',
    'Permiso' => 'SI', 'Responsable' => '', 'Motivo' => '',
];
$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $field) $values[$field] = vpn_add_value($field, $field === 'Motivo' ? 2000 : 255);
    if ($values['Solicitante'] === '') $formErrors[] = 'Ingrese el solicitante.';
    if ($values['Empresa'] === '') $formErrors[] = 'Ingrese la empresa.';
    if ($values['Responsable'] === '') $formErrors[] = 'Ingrese el responsable.';
    if ($values['Motivo'] === '') $formErrors[] = 'Ingrese el motivo del acceso.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['Fecha'])) $formErrors[] = 'La fecha no es valida.';
    if (!vpn_add_time($values['Hora'])) $formErrors[] = 'La hora no es valida.';
    if (!vpn_add_time($values['Tiempo'])) $formErrors[] = 'La duracion no es valida.';
    if (!in_array($values['Nivel_Usu'], ['BASICO','INTERMEDIO','ADMINISTRADOR'], true)) $formErrors[] = 'Seleccione un nivel valido.';
    if (!in_array($values['Culminado'], ['SI','NO'], true)) $values['Culminado'] = 'NO';
    if (!in_array($values['Permiso'], ['SI','NO'], true)) $values['Permiso'] = 'NO';
    if ($values['Token'] === '' || !ctype_digit($values['Token'])) $formErrors[] = 'Ingrese un token numerico.';

    if (!$formErrors) {
        $columns = ['Solicitante','Empresa','Fecha','Hora','Nivel_Usu','Tiempo','Culminado','Token','Permiso','Responsable','Motivo'];
        $sqlValues = [];
        foreach ($columns as $column) {
            $sqlValues[] = "'".$db->escape($values[$column])."'";
        }
        try {
            $db->query('INSERT INTO vpn (`'.implode('`,`', $columns).'`) VALUES ('.implode(',', $sqlValues).')');
            $session->msg('s', 'Acceso VPN registrado correctamente.');
            redirect('index.php', false);
        } catch (Throwable $error) {
            error_log('[VPN agregar] '.$error->getMessage());
            $formErrors[] = 'No se pudo guardar el acceso VPN. Revise los datos e intentelo nuevamente.';
        }
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
.vpn-hero{border:0;border-radius:16px;color:#fff;background:linear-gradient(115deg,#173a5e,#137c8b 70%,#e29135);box-shadow:0 14px 34px rgba(23,58,94,.18)}.vpn-card{border:1px solid #dbe7ea;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(23,58,94,.07)}.vpn-card .card-header{border:0;background:#edf6f6;color:#173a5e}.vpn-label{font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#536b7c}.vpn-help{font-size:11px;color:#728493}.vpn-box{padding:14px;border:1px solid #dce8ea;border-radius:12px;background:#f8fbfb}.vpn-token{font-family:Consolas,monospace;letter-spacing:.08em}
</style>
<section class="content-header pb-1"><div class="container-fluid"><div class="card vpn-hero mb-3"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="small text-uppercase font-weight-bold opacity-75">Control de accesos remotos</div><h1 class="h4 font-weight-bold mb-1"><i class="fas fa-shield-alt mr-2"></i>Nuevo acceso VPN</h1><div class="small">Registre el solicitante, vigencia, autorizacion y responsable.</div></div><a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div></div></div></section>
<section class="content"><div class="container-fluid"><div class="row justify-content-center"><div class="col-xl-10">
<?php if ($formErrors):?><div class="alert alert-danger"><strong>No se pudo guardar:</strong><ul class="mb-0 mt-1"><?php foreach($formErrors as $error):?><li><?=vpn_add_h($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" action="agregar.php" class="card vpn-card"><?=csrf_input()?>
<div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-user-lock mr-2 text-info"></i>Informacion del acceso</h3></div><div class="card-body">
<div class="vpn-box mb-3"><div class="row"><div class="col-md-6 form-group"><label class="vpn-label">Solicitante *</label><input name="Solicitante" class="form-control" maxlength="255" value="<?=vpn_add_h($values['Solicitante'])?>" required placeholder="Nombre del usuario solicitante"></div><div class="col-md-6 form-group"><label class="vpn-label">Empresa *</label><input name="Empresa" class="form-control" maxlength="255" value="<?=vpn_add_h($values['Empresa'])?>" required placeholder="Empresa o proveedor"></div><div class="col-md-6 form-group mb-md-0"><label class="vpn-label">Responsable *</label><input name="Responsable" class="form-control" maxlength="255" value="<?=vpn_add_h($values['Responsable'])?>" required placeholder="Quien autoriza o supervisa"></div><div class="col-md-6 form-group mb-0"><label class="vpn-label">Nivel de usuario</label><select name="Nivel_Usu" class="form-control"><?php foreach(['BASICO'=>'Basico','INTERMEDIO'=>'Intermedio','ADMINISTRADOR'=>'Administrador'] as $key=>$label):?><option value="<?=$key?>" <?=$values['Nivel_Usu']===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div></div></div>
<div class="row"><div class="col-lg-7"><div class="vpn-box h-100"><div class="row"><div class="col-sm-4 form-group"><label class="vpn-label">Fecha *</label><input type="date" name="Fecha" class="form-control" value="<?=vpn_add_h($values['Fecha'])?>" required></div><div class="col-sm-4 form-group"><label class="vpn-label">Hora *</label><input type="time" name="Hora" class="form-control" value="<?=vpn_add_h(substr($values['Hora'],0,5))?>" required></div><div class="col-sm-4 form-group"><label class="vpn-label">Duracion *</label><input type="time" name="Tiempo" class="form-control" value="<?=vpn_add_h(substr($values['Tiempo'],0,5))?>" required><div class="vpn-help">Ejemplo: 01:30</div></div><div class="col-sm-6 form-group mb-sm-0"><label class="vpn-label">Permiso concedido</label><select name="Permiso" class="form-control"><option value="SI" <?=$values['Permiso']==='SI'?'selected':''?>>Si</option><option value="NO" <?=$values['Permiso']==='NO'?'selected':''?>>No</option></select></div><div class="col-sm-6 form-group mb-0"><label class="vpn-label">Acceso culminado</label><select name="Culminado" class="form-control"><option value="NO" <?=$values['Culminado']==='NO'?'selected':''?>>No, sigue vigente</option><option value="SI" <?=$values['Culminado']==='SI'?'selected':''?>>Si, culminado</option></select></div></div></div></div><div class="col-lg-5 mt-3 mt-lg-0"><div class="vpn-box h-100"><div class="form-group"><label class="vpn-label">Token *</label><input name="Token" inputmode="numeric" class="form-control vpn-token" maxlength="20" value="<?=vpn_add_h($values['Token'])?>" placeholder="Codigo numerico" required><div class="vpn-help">Token entregado para la conexion VPN.</div></div><div class="form-group mb-0"><label class="vpn-label">Motivo del acceso *</label><textarea name="Motivo" class="form-control" rows="5" maxlength="2000" required placeholder="Actividad que realizara mediante VPN"><?=vpn_add_h($values['Motivo'])?></textarea></div></div></div></div>
</div><div class="card-footer bg-white d-flex justify-content-between"><a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-times mr-1"></i>Cancelar</a><button class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar acceso VPN</button></div></form>
</div></div></div></section>
<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
