<?php
/**
 * Acceso principal - Sistema de Soporte TI
 * TAMA Ingenieros S.A.C.
 */
ob_start();
require_once __DIR__.'/includes/load.php';

if ($session->isUserLoggedIn(true)) {
    redirect('home.php', false);
}

$baseUrl = defined('BASE_URL') ? BASE_URL : '/soporte';
$assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : $baseUrl.'/assets';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d3845">
    <?php echo csrf_meta_tag(); ?>
    <title>Acceso | Sistema de Soporte TI</title>
    <?php echo stylesheet_tag('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'); ?>
    <?php echo stylesheet_tag('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'); ?>
    <style>
        :root{--navy:#0d3845;--navy-2:#135261;--teal:#1393a5;--green:#29a66a;--ink:#17313a;--muted:#687d85;--line:#dce8eb;--surface:#fff;--soft:#f3f8f9}
        *{box-sizing:border-box}
        html,body{margin:0;min-height:100%;font-family:Inter,Arial,sans-serif;color:var(--ink)}
        body{min-height:100vh;background:#eaf2f3;overflow-x:hidden}
        .login-shell{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.16fr) minmax(390px,.84fr);position:relative}
        .brand-panel{position:relative;display:flex;align-items:center;padding:64px clamp(42px,7vw,108px);overflow:hidden;background:linear-gradient(145deg,#0b303b 0%,#0f4857 58%,#087f88 100%);color:#fff}
        .brand-panel:before,.brand-panel:after{content:"";position:absolute;border-radius:50%;pointer-events:none}
        .brand-panel:before{width:520px;height:520px;right:-240px;top:-240px;border:1px solid rgba(255,255,255,.13);box-shadow:0 0 0 65px rgba(255,255,255,.035),0 0 0 135px rgba(255,255,255,.025)}
        .brand-panel:after{width:320px;height:320px;left:-170px;bottom:-175px;background:rgba(21,171,171,.18)}
        .brand-content{position:relative;z-index:2;max-width:690px}
        .brand-logo{display:inline-flex;align-items:center;gap:14px;padding:10px 16px 10px 10px;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:rgba(255,255,255,.1);backdrop-filter:blur(10px)}
        .brand-logo img{width:56px;height:56px;object-fit:contain;padding:5px;border-radius:10px;background:#fff}
        .brand-logo span{display:block;font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;opacity:.78}
        .brand-logo strong{display:block;margin-top:3px;font-size:16px}
        .eyebrow{margin:48px 0 12px;color:#77e2d0;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
        .brand-content h1{max-width:660px;margin:0;font-size:clamp(38px,5vw,66px);line-height:1.04;letter-spacing:-.045em;font-weight:800}
        .brand-content>p{max-width:610px;margin:24px 0 0;color:rgba(255,255,255,.76);font-size:17px;line-height:1.75}
        .feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-top:42px}
        .feature{min-height:108px;padding:18px;border:1px solid rgba(255,255,255,.14);border-radius:16px;background:rgba(255,255,255,.075)}
        .feature i{color:#69d9c6;font-size:20px}.feature strong{display:block;margin:13px 0 5px;font-size:13px}.feature span{color:rgba(255,255,255,.64);font-size:11px;line-height:1.5}
        .access-panel{display:flex;align-items:center;justify-content:center;padding:42px clamp(28px,5vw,76px);background:linear-gradient(180deg,#f9fcfc 0%,#eef5f6 100%)}
        .login-card{width:100%;max-width:465px}
        .mobile-brand{display:none;align-items:center;gap:12px;margin-bottom:28px}.mobile-brand img{width:48px;height:48px;object-fit:contain}.mobile-brand strong{display:block}.mobile-brand span{color:var(--muted);font-size:12px}
        .access-kicker{display:flex;align-items:center;gap:8px;color:var(--teal);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.access-kicker:before{content:"";width:24px;height:3px;border-radius:4px;background:var(--teal)}
        .login-card h2{margin:14px 0 10px;font-size:34px;letter-spacing:-.035em}.intro{margin:0 0 32px;color:var(--muted);font-size:14px;line-height:1.65}
        #loginAlert,.alert{margin-bottom:18px;padding:12px 14px;border:0;border-radius:11px;font-size:13px}#loginAlert{display:none}.alert-danger{color:#8e2932;background:#fdebed}.alert-success{color:#12603b;background:#e5f7ed}.alert-warning{color:#76520c;background:#fff5d9}
        .field{margin-bottom:19px}.field-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}.field label{font-size:12px;font-weight:700;color:#294751}.field-hint{font-size:11px;color:#8ba0a7}
        .input-wrap{position:relative}.input-wrap>i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#789198;font-size:15px}.input-wrap input{width:100%;height:54px;padding:0 48px 0 45px;border:1px solid #d4e2e5;border-radius:12px;outline:0;background:#fff;color:var(--ink);font:500 14px Inter,Arial,sans-serif;transition:.2s}.input-wrap input::placeholder{color:#a0b0b5}.input-wrap input:focus{border-color:var(--teal);box-shadow:0 0 0 4px rgba(19,147,165,.11)}
        .toggle-password{position:absolute;right:9px;top:8px;width:38px;height:38px;border:0;border-radius:9px;background:transparent;color:#769098;cursor:pointer}.toggle-password:hover{background:#edf5f6;color:var(--teal)}
        .login-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;height:54px;margin-top:8px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--navy-2),var(--teal));box-shadow:0 12px 28px rgba(13,89,103,.22);color:#fff;font:700 14px Inter,Arial,sans-serif;cursor:pointer;transition:.2s}.login-btn:hover{transform:translateY(-2px);box-shadow:0 16px 32px rgba(13,89,103,.29)}.login-btn:disabled{opacity:.7;cursor:wait;transform:none}
        .secure-note{display:flex;align-items:flex-start;gap:11px;margin-top:25px;padding:14px;border:1px solid var(--line);border-radius:12px;background:rgba(255,255,255,.58)}.secure-note i{margin-top:2px;color:var(--green)}.secure-note strong{display:block;font-size:12px}.secure-note span{display:block;margin-top:3px;color:var(--muted);font-size:11px;line-height:1.45}
        .access-footer{margin-top:32px;text-align:center;color:#879a9f;font-size:11px}.access-footer b{color:#54717a}
        @media(max-width:960px){.login-shell{grid-template-columns:1fr}.brand-panel{display:none}.access-panel{min-height:100vh;padding:34px 22px}.mobile-brand{display:flex}.login-card{max-width:440px}}
        @media(max-width:480px){.access-panel{align-items:flex-start;padding-top:38px}.login-card h2{font-size:29px}.intro{margin-bottom:26px}}
        @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
<main class="login-shell">
    <section class="brand-panel" aria-label="Presentación del sistema">
        <div class="brand-content">
            <div class="brand-logo">
                <img src="<?php echo htmlspecialchars($assetsUrl); ?>/img/logo.png" alt="Logo TAMA">
                <div><span>TAMA Ingenieros S.A.C.</span><strong>Plataforma de Tecnología</strong></div>
            </div>
            <div class="eyebrow">Gestión centralizada de TI</div>
            <h1>Soporte claro, rápido y trazable.</h1>
            <p>Un solo espacio para gestionar activos, solicitudes, incidencias, mantenimiento, capacidad y servicios tecnológicos de la organización.</p>
            <div class="feature-grid">
                <div class="feature"><i class="fas fa-headset"></i><strong>Mesa de ayuda</strong><span>Seguimiento completo de tickets y atención.</span></div>
                <div class="feature"><i class="fas fa-laptop"></i><strong>Activos TI</strong><span>Inventario, asignaciones y ciclo de vida.</span></div>
                <div class="feature"><i class="fas fa-chart-line"></i><strong>Control operativo</strong><span>Capacidad, eventos y mantenimiento.</span></div>
            </div>
        </div>
    </section>

    <section class="access-panel">
        <div class="login-card">
            <div class="mobile-brand"><img src="<?php echo htmlspecialchars($assetsUrl); ?>/img/logo.png" alt="Logo TAMA"><div><strong>Sistema de Soporte TI</strong><span>TAMA Ingenieros S.A.C.</span></div></div>
            <div class="access-kicker">Portal interno</div>
            <h2>Bienvenido</h2>
            <p class="intro">Ingrese con sus credenciales corporativas para acceder a las herramientas de gestión de Tecnología de Información.</p>

            <div id="loginAlert" role="alert" aria-live="polite"></div>
            <?php echo display_msg($msg); ?>

            <form id="loginForm" method="post" autocomplete="on">
                <?php echo csrf_input(); ?>
                <div class="field">
                    <div class="field-row"><label for="username">Usuario</label><span class="field-hint">Cuenta corporativa</span></div>
                    <div class="input-wrap"><i class="fas fa-user" aria-hidden="true"></i><input type="text" id="username" name="username" placeholder="Ingrese su usuario" autocomplete="username" required autofocus></div>
                </div>
                <div class="field">
                    <div class="field-row"><label for="password">Contraseña</label><span class="field-hint">Acceso seguro</span></div>
                    <div class="input-wrap"><i class="fas fa-lock" aria-hidden="true"></i><input type="password" id="password" name="password" placeholder="Ingrese su contraseña" autocomplete="current-password" required><button class="toggle-password" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="fas fa-eye"></i></button></div>
                </div>
                <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i><span>Ingresar al sistema</span></button>
            </form>

            <div class="secure-note"><i class="fas fa-shield-alt"></i><div><strong>Acceso protegido</strong><span>El uso del sistema está reservado al personal autorizado. Las acciones realizadas quedan registradas.</span></div></div>
            <div class="access-footer">© <?php echo date('Y'); ?> <b>TAMA Ingenieros S.A.C.</b> · Tecnología de Información</div>
        </div>
    </section>
</main>
<script src="<?php echo htmlspecialchars($assetsUrl); ?>/js/auth.js"></script>
<script>
document.querySelector('.toggle-password').addEventListener('click', function () {
    var field = document.getElementById('password');
    var visible = field.type === 'text';
    field.type = visible ? 'password' : 'text';
    this.innerHTML = '<i class="fas fa-eye' + (visible ? '' : '-slash') + '"></i>';
    this.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
});
</script>
</body>
</html>