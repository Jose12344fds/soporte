<?php
$page_title = 'Editar acceso VPN';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('vpn', 'editar');

function vpn_edit_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function vpn_edit_value(string $name, int $max = 255): string { return mb_substr(trim((string)($_POST[$name] ?? '')), 0, $max, 'UTF-8'); }
function vpn_edit_time(string $value): bool { return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value); }

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) { $session->msg('d','Registro VPN no valido.'); redirect('index.php',false); }
$record = $db->fetch_assoc($db->query("SELECT * FROM vpn WHERE Id_IngVpn={$id} LIMIT 1"));
if (!$record) { $session->msg('d','El registro VPN no existe.'); redirect('index.php',false); }
$values = $record;
$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['Solicitante','Empresa','Fecha','Hora','Nivel_Usu','Tiempo','Culminado','Token','Permiso','Responsable','Motivo'] as $field) $values[$field] = vpn_edit_value($field, $field === 'Motivo' ? 2000 : 255);
    foreach (['Solicitante'=>'solicitante','Empresa'=>'empresa','Responsable'=>'responsable','Motivo'=>'motivo'] as $field=>$label) if ($values[$field] === '') $formErrors[]='Ingrese el '.$label.'.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$values['Fecha'])) $formErrors[]='La fecha no es valida.';
    if (!vpn_edit_time($values['Hora']) || !vpn_edit_time($values['Tiempo'])) $formErrors[]='Revise la hora y duracion.';
    if (!in_array($values['Nivel_Usu'],['BASICO','INTERMEDIO','ADMINISTRADOR'],true)) $formErrors[]='Nivel no valido.';
    if (!in_array($values['Culminado'],['SI','NO'],true)) $values['Culminado']='NO';
    if (!in_array($values['Permiso'],['SI','NO'],true)) $values['Permiso']='NO';
    if ($values['Token'] === '' || !ctype_digit($values['Token'])) $formErrors[]='Ingrese un token numerico.';
    if (!$formErrors) {
        $sets=[];
        foreach (['Solicitante','Empresa','Fecha','Hora','Nivel_Usu','Tiempo','Culminado','Token','Permiso','Responsable','Motivo'] as $field) $sets[]="`{$field}`='".$db->escape($values[$field])."'";
        try {
            $db->query('UPDATE vpn SET '.implode(',',$sets)." WHERE Id_IngVpn={$id} LIMIT 1");
            $session->msg('s','Acceso VPN actualizado correctamente.');
            redirect('index.php',false);
        } catch(Throwable $error) {
            error_log('[VPN editar] '.$error->getMessage());
            $formErrors[]='No se pudo actualizar el acceso VPN.';
        }
    }
}
include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>.vpne-hero{border:0;border-radius:14px;color:#fff;background:linear-gradient(115deg,#173a5e,#137c8b 70%,#e29135)}.vpne-card{border:1px solid #dbe7ea;border-radius:14px;overflow:hidden}.vpne-card .card-header{background:#edf6f6}.vpne-label{font-size:11px;font-weight:800;text-transform:uppercase;color:#536b7c}.vpne-box{padding:14px;border:1px solid #dce8ea;border-radius:12px;background:#f8fbfb}</style>
<section class="content-header pb-1"><div class="container-fluid"><div class="card vpne-hero"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="small text-uppercase font-weight-bold">Control de accesos remotos</div><h1 class="h4 font-weight-bold mb-0"><i class="fas fa-user-shield mr-2"></i>Editar acceso VPN #<?=$id?></h1></div><a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div></div></div></section>
<section class="content"><div class="container-fluid"><div class="row justify-content-center"><div class="col-xl-10"><?php if($formErrors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($formErrors as $error):?><li><?=vpn_edit_h($error)?></li><?php endforeach;?></ul></div><?php endif;?><form method="post" class="card vpne-card"><?=csrf_input()?><input type="hidden" name="id" value="<?=$id?>"><div class="card-header font-weight-bold"><i class="fas fa-edit mr-2 text-warning"></i>Datos registrados</div><div class="card-body"><div class="vpne-box mb-3"><div class="row"><div class="col-md-6 form-group"><label class="vpne-label">Solicitante *</label><input name="Solicitante" class="form-control" value="<?=vpn_edit_h($values['Solicitante'])?>" required></div><div class="col-md-6 form-group"><label class="vpne-label">Empresa *</label><input name="Empresa" class="form-control" value="<?=vpn_edit_h($values['Empresa'])?>" required></div><div class="col-md-6 form-group mb-md-0"><label class="vpne-label">Responsable *</label><input name="Responsable" class="form-control" value="<?=vpn_edit_h($values['Responsable'])?>" required></div><div class="col-md-6 form-group mb-0"><label class="vpne-label">Nivel</label><select name="Nivel_Usu" class="form-control"><?php foreach(['BASICO'=>'Basico','INTERMEDIO'=>'Intermedio','ADMINISTRADOR'=>'Administrador'] as $key=>$label):?><option value="<?=$key?>" <?=$values['Nivel_Usu']===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div></div></div><div class="row"><div class="col-lg-7"><div class="vpne-box h-100"><div class="row"><div class="col-sm-4 form-group"><label class="vpne-label">Fecha</label><input type="date" name="Fecha" class="form-control" value="<?=vpn_edit_h(substr((string)$values['Fecha'],0,10))?>" required></div><div class="col-sm-4 form-group"><label class="vpne-label">Hora</label><input type="time" name="Hora" class="form-control" value="<?=vpn_edit_h(substr((string)$values['Hora'],0,5))?>" required></div><div class="col-sm-4 form-group"><label class="vpne-label">Duracion</label><input type="time" name="Tiempo" class="form-control" value="<?=vpn_edit_h(substr((string)$values['Tiempo'],0,5))?>" required></div><div class="col-sm-6 form-group mb-0"><label class="vpne-label">Permiso</label><select name="Permiso" class="form-control"><option value="SI" <?=$values['Permiso']==='SI'?'selected':''?>>Si</option><option value="NO" <?=$values['Permiso']==='NO'?'selected':''?>>No</option></select></div><div class="col-sm-6 form-group mb-0"><label class="vpne-label">Culminado</label><select name="Culminado" class="form-control"><option value="NO" <?=$values['Culminado']==='NO'?'selected':''?>>No</option><option value="SI" <?=$values['Culminado']==='SI'?'selected':''?>>Si</option></select></div></div></div></div><div class="col-lg-5 mt-3 mt-lg-0"><div class="vpne-box h-100"><div class="form-group"><label class="vpne-label">Token *</label><input name="Token" inputmode="numeric" class="form-control" value="<?=vpn_edit_h($values['Token'])?>" required></div><div class="form-group mb-0"><label class="vpne-label">Motivo *</label><textarea name="Motivo" class="form-control" rows="5" required><?=vpn_edit_h($values['Motivo'])?></textarea></div></div></div></div></div><div class="card-footer bg-white d-flex justify-content-between"><a href="index.php" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i>Actualizar acceso</button></div></form></div></div></div></section>
<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
