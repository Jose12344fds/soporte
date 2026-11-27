<?php
$page_title = 'Mi perfil y seguridad';
require_once __DIR__.'/includes/load.php';
require_once __DIR__.'/config/app.php';

if (!$session->isUserLoggedIn()) {
    $session->msg('d', 'Inicie sesión para acceder a su perfil.');
    redirect('index.php', false);
}

$profileUser = current_user();
if (!$profileUser) {
    $session->logout();
    redirect('index.php', false);
}

function profile_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function profile_password_errors(string $password, string $username, string $name): array
{
    $errors = [];
    if (mb_strlen($password, 'UTF-8') < 12) $errors[] = 'Debe tener al menos 12 caracteres.';
    if (mb_strlen($password, 'UTF-8') > 72) $errors[] = 'No puede superar 72 caracteres.';
    if (!preg_match('/\p{Lu}/u', $password)) $errors[] = 'Debe incluir una letra mayúscula.';
    if (!preg_match('/\p{Ll}/u', $password)) $errors[] = 'Debe incluir una letra minúscula.';
    if (!preg_match('/\p{N}/u', $password)) $errors[] = 'Debe incluir un número.';
    if (!preg_match('/[\p{P}\p{S}]/u', $password)) $errors[] = 'Debe incluir un símbolo.';
    if (preg_match('/\s/u', $password)) $errors[] = 'No debe contener espacios.';
    $lower = mb_strtolower($password, 'UTF-8');
    $userLower = mb_strtolower(trim($username), 'UTF-8');
    if (mb_strlen($userLower, 'UTF-8') >= 3 && str_contains($lower, $userLower)) $errors[] = 'No debe contener el nombre de usuario.';
    foreach (preg_split('/\s+/u', mb_strtolower(trim($name), 'UTF-8')) ?: [] as $part) {
        if (mb_strlen($part, 'UTF-8') >= 4 && str_contains($lower, $part)) { $errors[] = 'No debe contener su nombre o apellido.'; break; }
    }
    $compact = preg_replace('/[^a-z0-9]/', '', $lower);
    if (in_array($compact, ['password','password123','contrasena','contrasena123','administrador','qwerty123','123456789012'], true)) $errors[] = 'La contraseña elegida es demasiado común.';
    return $errors;
}

$passwordErrors = [];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmation = (string)($_POST['new_password_confirmation'] ?? '');
    if (!user_password_matches($currentPassword, (string)$profileUser['password'])) $passwordErrors[] = 'La contraseña actual no es correcta.';
    $passwordErrors = array_merge($passwordErrors, profile_password_errors($newPassword, (string)$profileUser['username'], (string)$profileUser['name']));
    if ($newPassword !== $confirmation) $passwordErrors[] = 'La confirmación no coincide con la nueva contraseña.';
    if ($currentPassword !== '' && hash_equals($currentPassword, $newPassword)) $passwordErrors[] = 'La nueva contraseña debe ser diferente de la actual.';
    $passwordErrors = array_values(array_unique($passwordErrors));
    if (!$passwordErrors) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password='".$db->escape($hash)."' WHERE id=".(int)$profileUser['id']." LIMIT 1");
        session_regenerate_id(true);
        $session->msg('s', 'Contraseña actualizada correctamente. Desde ahora se aplicará en su próximo inicio de sesión.');
        redirect('profile.php', false);
    }
}

$initials = '';
foreach (array_slice(preg_split('/\s+/u', trim((string)$profileUser['name'])) ?: [], 0, 2) as $part) $initials .= mb_substr($part, 0, 1, 'UTF-8');
include_once __DIR__.'/layouts/header.php';
?>
<style>
.profile-security-page { max-width:1180px; margin:0 auto; }
.profile-hero { color:#fff; border-radius:16px; padding:25px; background:linear-gradient(135deg,#123f55,#148ca0); box-shadow:0 12px 35px rgba(20,65,83,.2); }
.profile-avatar { width:78px;height:78px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);border:3px solid rgba(255,255,255,.72);font-size:27px;font-weight:800; }
.profile-card { border:0;border-radius:14px;box-shadow:0 9px 28px rgba(29,57,75,.10);overflow:hidden; }
.profile-card .card-header { background:#f6fafc;border-bottom:1px solid #dfe9ee;padding:16px 20px; }
.profile-field { padding:12px 0;border-bottom:1px solid #edf1f3; }
.profile-field:last-child { border-bottom:0; }
.profile-field span { display:block;color:#748690;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em; }
.profile-field strong { color:#263f4c; }
.password-rule { display:flex;align-items:center;gap:8px;margin:7px 0;color:#788991;font-size:12px; }
.password-rule i { width:16px;color:#aab6bc; }
.password-rule.valid { color:#21865b; }.password-rule.valid i { color:#21865b; }
.password-meter { height:7px;border-radius:8px;background:#e5ebee;overflow:hidden; }.password-meter span { display:block;width:0;height:100%;transition:.2s;background:#d9485d; }
.password-wrap { position:relative; }.password-wrap .form-control { padding-right:45px; }.password-toggle { position:absolute;right:6px;top:31px;border:0;background:transparent;color:#667d89;padding:7px; }
</style>
<section class="content"><div class="container-fluid py-3"><div class="profile-security-page">
    <?= display_msg($msg) ?>
    <div class="profile-hero mb-4"><div class="d-flex align-items-center flex-wrap"><div class="profile-avatar mr-3"><?= profile_h(mb_strtoupper($initials, 'UTF-8')) ?></div><div><small class="text-white-50 text-uppercase font-weight-bold">Cuenta personal</small><h1 class="h3 mb-1"><?= profile_h($profileUser['name']) ?></h1><div class="text-white-50"><i class="fas fa-user mr-1"></i><?= profile_h($profileUser['username']) ?><?php if (!empty($profileUser['correo'])): ?> · <i class="fas fa-envelope ml-1 mr-1"></i><?= profile_h($profileUser['correo']) ?><?php endif; ?></div></div></div></div>
    <div class="row">
        <div class="col-lg-4 mb-4"><div class="card profile-card h-100"><div class="card-header"><h3 class="h6 font-weight-bold mb-0"><i class="fas fa-id-card mr-2 text-info"></i>Información de la cuenta</h3></div><div class="card-body">
            <div class="profile-field"><span>Nombre</span><strong><?= profile_h($profileUser['name']) ?></strong></div>
            <div class="profile-field"><span>Área</span><strong><?= profile_h($profileUser['area'] ?: 'No registrada') ?></strong></div>
            <div class="profile-field"><span>Cargo</span><strong><?= profile_h($profileUser['cargo'] ?: 'No registrado') ?></strong></div>
            <div class="profile-field"><span>Correo</span><strong><?= profile_h($profileUser['correo'] ?: 'No registrado') ?></strong></div>
            <div class="profile-field"><span>Último ingreso</span><strong><?= profile_h(read_date($profileUser['last_login'])) ?></strong></div>
        </div></div></div>
        <div class="col-lg-8"><div class="card profile-card"><div class="card-header"><h3 class="h6 font-weight-bold mb-1"><i class="fas fa-shield-alt mr-2 text-success"></i>Cambiar contraseña</h3><small class="text-muted">Confirme su contraseña actual y cree una clave personal difícil de adivinar.</small></div><div class="card-body">
            <?php if ($passwordErrors): ?><div class="alert alert-danger"><strong>No se pudo cambiar la contraseña:</strong><ul class="mb-0 mt-2"><?php foreach ($passwordErrors as $error): ?><li><?= profile_h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" id="profilePasswordForm" autocomplete="off"><?= csrf_input() ?><input type="hidden" name="action" value="change_password">
                <div class="password-wrap form-group"><label>Contraseña actual *</label><input type="password" class="form-control" name="current_password" id="currentPassword" autocomplete="current-password" required><button type="button" class="password-toggle" data-target="currentPassword" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button></div>
                <div class="row"><div class="col-md-6"><div class="password-wrap form-group"><label>Nueva contraseña *</label><input type="password" class="form-control" name="new_password" id="newPassword" autocomplete="new-password" minlength="12" maxlength="72" required><button type="button" class="password-toggle" data-target="newPassword" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button></div></div><div class="col-md-6"><div class="password-wrap form-group"><label>Confirmar nueva contraseña *</label><input type="password" class="form-control" name="new_password_confirmation" id="confirmPassword" autocomplete="new-password" minlength="12" maxlength="72" required><button type="button" class="password-toggle" data-target="confirmPassword" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button></div></div></div>
                <div class="password-meter mb-2"><span id="passwordMeterBar"></span></div><div class="small font-weight-bold mb-3" id="passwordStrengthText">Ingrese una contraseña nueva.</div>
                <div class="row"><div class="col-sm-6"><div class="password-rule" data-rule="length"><i class="fas fa-circle"></i>12 caracteres como mínimo</div><div class="password-rule" data-rule="upper"><i class="fas fa-circle"></i>Una letra mayúscula</div><div class="password-rule" data-rule="lower"><i class="fas fa-circle"></i>Una letra minúscula</div></div><div class="col-sm-6"><div class="password-rule" data-rule="number"><i class="fas fa-circle"></i>Un número</div><div class="password-rule" data-rule="symbol"><i class="fas fa-circle"></i>Un símbolo</div><div class="password-rule" data-rule="spaces"><i class="fas fa-circle"></i>Sin espacios</div></div></div>
                <div class="text-right mt-3"><button class="btn btn-success px-4"><i class="fas fa-key mr-1"></i>Actualizar contraseña</button></div>
            </form>
        </div></div></div>
    </div>
</div></div></section>
<?php
$pageScripts = <<<'JS'
$(function(){
    $('.password-toggle').on('click',function(){var input=document.getElementById($(this).data('target'));var show=input.type==='password';input.type=show?'text':'password';$(this).find('i').toggleClass('fa-eye',!show).toggleClass('fa-eye-slash',show);});
    function updatePasswordStrength(){
        var value=$('#newPassword').val();
        var rules={length:value.length>=12,upper:/[A-ZÁÉÍÓÚÑ]/.test(value),lower:/[a-záéíóúñ]/.test(value),number:/\d/.test(value),symbol:/[^A-Za-z0-9\sÁÉÍÓÚÑáéíóúñ]/.test(value),spaces:value.length>0&&!/\s/.test(value)};
        var score=0;Object.keys(rules).forEach(function(key){var row=$('.password-rule[data-rule="'+key+'"]');row.toggleClass('valid',rules[key]);row.find('i').toggleClass('fa-circle',!rules[key]).toggleClass('fa-check-circle',rules[key]);if(rules[key])score++;});
        var colors=['#d9485d','#d9485d','#e47a32','#d59b20','#4f9b72','#21865b','#147d68'];var labels=['Muy débil','Muy débil','Débil','Regular','Buena','Fuerte','Muy fuerte'];
        $('#passwordMeterBar').css({width:(score/6*100)+'%',background:colors[score]});$('#passwordStrengthText').text(value?labels[score]:'Ingrese una contraseña nueva.').css('color',value?colors[score]:'#6c757d');
    }
    $('#newPassword').on('input',updatePasswordStrength);updatePasswordStrength();
    $('#profilePasswordForm').on('submit',function(event){if($('#newPassword').val()!==$('#confirmPassword').val()){event.preventDefault();Swal.fire('Las contraseñas no coinciden','Revise la confirmación de la nueva contraseña.','warning');}});
});
JS;
include_once __DIR__.'/layouts/footer.php';
?>
