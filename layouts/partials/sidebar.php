<?php
/**
 * Sidebar Navigation - AdminLTE
 * Sistema de Soporte TAMA
 */

// Obtener la página actual para marcar el menú activo
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];

// Obtener usuario actual si la función existe
if (!isset($user) && function_exists('current_user')) {
    $user = current_user();
}
$userLevel = isset($user['user_level']) ? $user['user_level'] : '3';

// Definir URLs base si no están definidas
$baseUrl = defined('BASE_URL') ? BASE_URL : '/soporte';
$assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : '/soporte/assets';
$appName = defined('APP_NAME') ? APP_NAME : 'Sistema Soporte';

if (!function_exists('sidebar_svg_data_uri')) {
    function sidebar_svg_data_uri(string $svg): string {
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}

if (!function_exists('sidebar_logo_fallback')) {
    function sidebar_logo_fallback(string $label = 'ST'): string {
        $text = htmlspecialchars(mb_strtoupper(substr(trim($label), 0, 2)), ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'
            . '<rect width="120" height="120" rx="60" fill="#1f7a45"/>'
            . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" font-weight="700" fill="#ffffff">'
            . $text
            . '</text></svg>';
        return sidebar_svg_data_uri($svg);
    }
}

if (!function_exists('sidebar_user_avatar')) {
    function sidebar_user_avatar(?string $name): string {
        $name = trim((string)$name);
        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= mb_substr($part, 0, 1);
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }
        if ($initials === '') {
            $initials = 'US';
        }
        $initials = htmlspecialchars(mb_strtoupper($initials), ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'
            . '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">'
            . '<stop offset="0%" stop-color="#1f7a45"/><stop offset="100%" stop-color="#2fb36a"/>'
            . '</linearGradient></defs>'
            . '<circle cx="60" cy="60" r="60" fill="url(#g)"/>'
            . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, sans-serif" font-size="38" font-weight="700" fill="#ffffff">'
            . $initials
            . '</text></svg>';
        return sidebar_svg_data_uri($svg);
    }
}

$userName = isset($user['name']) ? (string)$user['name'] : 'Usuario';
$logoFallback = sidebar_logo_fallback($appName);
$userAvatar = sidebar_user_avatar($userName);

// Función para verificar si estamos en un módulo específico
function enModulo($modulo) {
    return strpos($_SERVER['PHP_SELF'], "/modules/{$modulo}/") !== false;
}
?>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?php echo $baseUrl; ?>/home.php" class="brand-link">
            <img src="<?php echo $assetsUrl; ?>/img/logo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8" onerror="this.onerror=null;this.src='<?php echo $logoFallback; ?>';">
            <span class="brand-text font-weight-light"><?php echo $appName; ?></span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="<?php echo $userAvatar; ?>" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="<?php echo $baseUrl; ?>/profile.php" class="d-block">
                        <?php echo htmlspecialchars($userName); ?>
                    </a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>/home.php" class="nav-link <?php echo in_array($currentPage, ['admin.php', 'home.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <?php if (function_exists('tiene_permiso') && tiene_permiso('usuarios', 'ver')): ?>
                    <!-- Usuarios -->
                    <li class="nav-item <?php echo enModulo('usuarios') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('usuarios') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Usuarios
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/usuarios/index.php" class="nav-link <?php echo enModulo('usuarios') && $currentPage == 'index.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista de Usuarios</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/usuarios/grupos.php" class="nav-link <?php echo $currentPage == 'grupos.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Grupos y Permisos</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (!function_exists('tiene_permiso') || tiene_permiso('activos', 'ver')): ?>
                    <!-- Activos -->
                    <li class="nav-item <?php echo enModulo('activos') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('activos') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-desktop"></i>
                            <p>
                                Activos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/activos/index.php" class="nav-link <?php echo enModulo('activos') && $currentPage == 'index.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista de Activos</p>
                                </a>
                            </li>
                            <?php if (!function_exists('tiene_permiso') || tiene_permiso('activos', 'crear')): ?>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/activos/agregar.php" class="nav-link <?php echo enModulo('activos') && $currentPage == 'agregar.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Agregar Activo</p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (!function_exists('tiene_permiso') || tiene_permiso('mantenimientos', 'ver')): ?>
                    <!-- Mantenimientos -->
                    <li class="nav-item <?php echo enModulo('mantenimientos') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('mantenimientos') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tools"></i>
                            <p>
                                Mantenimientos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/mantenimientos/index.php" class="nav-link <?php echo enModulo('mantenimientos') && $currentPage == 'index.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista Mantenimientos</p>
                                </a>
                            </li>
                            <?php if (!function_exists('tiene_permiso') || tiene_permiso('mantenimientos', 'crear')): ?>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/mantenimientos/agregar.php" class="nav-link <?php echo enModulo('mantenimientos') && $currentPage == 'agregar.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Nuevo Mantenimiento</p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (!function_exists('tiene_permiso') || tiene_permiso('celulares', 'ver')): ?>
                    <!-- Celulares -->
                    <li class="nav-item <?php echo enModulo('celulares') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('celulares') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-mobile-alt"></i>
                            <p>
                                Celulares
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/celulares/index.php" class="nav-link <?php echo enModulo('celulares') && $currentPage == 'index.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Gestionar Celulares</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (!function_exists('tiene_permiso') || tiene_permiso('licencias', 'ver')): ?>
                    <!-- Licencias -->
                    <li class="nav-item <?php echo enModulo('licencias') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('licencias') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-key"></i>
                            <p>
                                Licencias
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/licencias/index.php" class="nav-link <?php echo enModulo('licencias') && $currentPage == 'index.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Inicio Licencias</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/licencias/LicenciasOffice.php" class="nav-link">
                                    <i class="fab fa-microsoft nav-icon"></i>
                                    <p>Licencias Office</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/licencias/LicenciasSap.php" class="nav-link">
                                    <i class="fas fa-database nav-icon"></i>
                                    <p>Licencias SAP</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/licencias/LicenciasOrbis.php" class="nav-link">
                                    <i class="fas fa-circle-notch nav-icon"></i>
                                    <p>Cuentas Orbis</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (!function_exists('tiene_permiso') || tiene_permiso('vpn', 'ver')): ?>
                    <!-- VPN -->
                    <li class="nav-item <?php echo enModulo('vpn') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('vpn') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-network-wired"></i>
                            <p>
                                Control VPN
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/vpn/index.php" class="nav-link <?php echo enModulo('vpn') && $currentPage == 'index.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista VPN</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (!function_exists('tiene_permiso') || tiene_permiso('reportes', 'ver')): ?>
                    <!-- Divider -->
                    <li class="nav-header">REPORTES</li>

                    <!-- Reportes -->
                    <li class="nav-item <?php echo enModulo('reportes') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo enModulo('reportes') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                Reportes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/reportes/activos.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Reporte Activos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $baseUrl; ?>/modules/reportes/vpn.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Reporte VPN</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Divider -->
                    <li class="nav-header">CONFIGURACIÓN</li>

                    <!-- Gestión de Módulos -->
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>/modules/admin_modulos/index.php" class="nav-link">
                            <i class="nav-icon fas fa-cube"></i>
                            <p>Gestión de Módulos</p>
                        </a>
                    </li>

                    <!-- Mi Cuenta -->
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>/edit_account.php" class="nav-link <?php echo $currentPage == 'edit_account.php' ? 'active' : ''; ?>">>
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>Mi Cuenta</p>
                        </a>
                    </li>

                    <!-- Cerrar Sesión -->
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>/logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
                            <p>Cerrar Sesión</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>
