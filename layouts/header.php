<?php
/**
 * Layout Header con AdminLTE 3
 * Sistema de Soporte TI
 */

// Cargar configuración solo si no está cargada
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}

// NO hacer session_start() aquí - ya fue hecho en load.php
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// Obtener usuario actual si está disponible
$user = function_exists('current_user') ? current_user() : null;
$srCanSeeNotifications=$user&&function_exists('tiene_permiso')&&tiene_permiso('activos','ver');
$srBase=defined('BASE_URL')?rtrim(BASE_URL,'/'):'/soporte';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php echo csrf_meta_tag(); ?>
    <meta name="app-base-url" content="<?=htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8')?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : (isset($page_title) ? $page_title . ' - ' : ''); ?><?php echo defined('APP_NAME') ? APP_NAME : 'Sistema de Soporte'; ?></title>
    
    <!-- Google Fonts -->
    <?php echo stylesheet_tag('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'); ?>
    
    <!-- Font Awesome Icons -->
    <?php echo stylesheet_tag('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'); ?>
    
    <!-- Bootstrap 4 -->
    <?php echo stylesheet_tag('https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'); ?>
    
    <!-- AdminLTE 3 -->
    <?php echo stylesheet_tag('https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'); ?>
    
    <!-- DataTables -->
    <?php echo stylesheet_tag('https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css'); ?>
    <?php echo stylesheet_tag('https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css'); ?>
    <?php echo stylesheet_tag('https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css'); ?>
    
    <!-- SweetAlert2 -->
    <?php echo stylesheet_tag('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'); ?>
    
    <!-- Custom CSS -->
    <?php if (defined('ASSETS_URL')): ?>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css">
    <?php endif; ?>
    
    <?php if (isset($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <?php echo stylesheet_tag($css); ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Global Module Management Functions -->
    <script type="text/javascript">
    // Función para abrir el modal de editar módulos
    window.abrirEditar = function(btn) {
        try {
            
            
            if (!btn) {
                console.error('Botón no encontrado');
                return;
            }
            
            // Obtener datos del botón
            var modulo_id = btn.getAttribute('data-id');
            var nombre = btn.getAttribute('data-nombre');
            var slug = btn.getAttribute('data-slug');
            var descripcion = btn.getAttribute('data-descripcion');
            var icono = btn.getAttribute('data-icono');
            var url = btn.getAttribute('data-url');
            
            
            
            // Rellenar el modal con los datos
            var elem_id = document.getElementById('edit_modulo_id');
            var elem_nombre = document.getElementById('edit_nombre');
            var elem_slug = document.getElementById('edit_slug');
            var elem_desc = document.getElementById('edit_descripcion');
            var elem_icono = document.getElementById('edit_icono');
            var elem_url = document.getElementById('edit_url_personalizada');
            
            if (elem_id) elem_id.value = modulo_id || '';
            if (elem_nombre) elem_nombre.value = nombre || '';
            if (elem_slug) elem_slug.value = slug || '';
            if (elem_desc) elem_desc.value = descripcion || '';
            if (elem_icono) elem_icono.value = icono || '';
            if (elem_url) elem_url.value = url || '';
            
            
            
            // Abrir el modal
            if (typeof jQuery !== 'undefined') {
                jQuery('#modal_editar').modal('show');
                
            } else if (typeof $ !== 'undefined') {
                $('#modal_editar').modal('show');
                
            } else {
                console.error('jQuery no está disponible');
                alert('Error: jQuery no está cargado');
            }
            
        } catch(e) {
            console.error('Error en abrirEditar:', e);
            alert('Error: ' + e.message);
        }
    };
    
    // Confirmar eliminación de módulo
    window.eliminarModulo = function(modulo_id, nombre_modulo) {
        try {
            var elem_id = document.getElementById('modulo_id_input');
            var elem_nombre = document.getElementById('nombre_modulo');
            
            if (elem_id) elem_id.value = modulo_id;
            if (elem_nombre) elem_nombre.textContent = nombre_modulo;
            
            if (typeof jQuery !== 'undefined') {
                jQuery('#modal_eliminar').modal('show');
            } else if (typeof $ !== 'undefined') {
                $('#modal_eliminar').modal('show');
            }
        } catch(e) {
            console.error('Error en eliminarModulo:', e);
        }
    };
    
    
    // Cargar permisos del módulo - mostrar tabla de todos los grupos
    window.cargarPermisosModulo = function() {
        try {
            
            
            var modulo = document.getElementById('perm_modulo') ? document.getElementById('perm_modulo').value : '';
            
            if (!modulo) {
                
                if (document.getElementById('tabla_permisos_contenedor')) {
                    document.getElementById('tabla_permisos_contenedor').style.display = 'none';
                }
                if (document.getElementById('editor_permisos')) {
                    document.getElementById('editor_permisos').style.display = 'none';
                }
                return;
            }
            
            // Mostrar tabla de permisos actuales
            if (document.getElementById('tabla_permisos_contenedor')) {
                document.getElementById('tabla_permisos_contenedor').style.display = 'block';
            }
            
            // Hacer AJAX para obtener permisos de todos los grupos
            if (typeof jQuery !== 'undefined') {
                jQuery.ajax({
                    url: '/soporte/api/get_grupos.php',
                    type: 'POST',
                    data: {
                        action: 'obtener_todos',
                        modulo: modulo
                    },
                    success: function(response) {
                        
                        try {
                            var data = JSON.parse(response);
                            var tbody = document.getElementById('tabla_permisos_cuerpo');
                            
                            if (!tbody) {
                                console.error('Tbody no encontrado');
                                return;
                            }
                            
                            // Limpiar tabla
                            tbody.innerHTML = '';
                            
                            // Generar filas dinámicamente
                            for (var nivel in data) {
                                var permisos = data[nivel];
                                var grupo_name = permisos.group_name || 'Grupo ' + nivel;
                                
                                var row = document.createElement('tr');
                                row.innerHTML = '<td><strong>' + grupo_name + '</strong></td>' +
                                    '<td>' + (permisos.puede_ver ? '<span class="badge badge-success">✓ SÍ</span>' : '<span class="badge badge-danger">✗ NO</span>') + '</td>' +
                                    '<td>' + (permisos.puede_crear ? '<span class="badge badge-success">✓ SÍ</span>' : '<span class="badge badge-danger">✗ NO</span>') + '</td>' +
                                    '<td>' + (permisos.puede_editar ? '<span class="badge badge-success">✓ SÍ</span>' : '<span class="badge badge-danger">✗ NO</span>') + '</td>' +
                                    '<td>' + (permisos.puede_eliminar ? '<span class="badge badge-success">✓ SÍ</span>' : '<span class="badge badge-danger">✗ NO</span>') + '</td>' +
                                    '<td><button type="button" class="btn btn-sm btn-primary" onclick="seleccionarNivel(' + nivel + ', \'' + grupo_name + '\')"><i class="fas fa-edit"></i></button></td>';
                                
                                tbody.appendChild(row);
                            }
                        } catch(e) {
                            console.error('Error parsing JSON:', e);
                        }
                    },
                    error: function(err) {
                        console.error('Error cargando permisos:', err);
                    }
                });
            }
        } catch(e) {
            console.error('Error en cargarPermisosModulo:', e);
        }
    };
    
    // Seleccionar un nivel para editar
    window.seleccionarNivel = function(grupo_level, grupo_name) {
        try {
            
            
            // Mostrar editor de permisos
            if (document.getElementById('editor_permisos')) {
                document.getElementById('editor_permisos').style.display = 'block';
            }
            
            // Actualizar etiqueta de grupo dinámicamente
            var elem_grupo = document.getElementById('grupo_seleccionado');
            if (elem_grupo) {
                elem_grupo.textContent = grupo_name;
            }
            
            // Setear select
            var select_level = document.getElementById('perm_level');
            if (select_level) {
                select_level.value = grupo_level;
                select_level.disabled = true;
            }
            
            // Hacer scroll al editor
            var editor = document.getElementById('editor_permisos');
            if (editor) {
                setTimeout(function() {
                    editor.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            }
            
            // Cargar permisos actuales de este grupo
            var modulo = document.getElementById('perm_modulo') ? document.getElementById('perm_modulo').value : '';
            
            if (typeof jQuery !== 'undefined') {
                jQuery.ajax({
                    url: '/soporte/api/get_grupos.php',
                    type: 'POST',
                    data: {
                        modulo: modulo,
                        group_level: grupo_level
                    },
                    success: function(response) {
                        
                        try {
                            var data = JSON.parse(response);
                            var chk_ver = document.getElementById('perm_ver');
                            var chk_crear = document.getElementById('perm_crear');
                            var chk_editar = document.getElementById('perm_editar');
                            var chk_eliminar = document.getElementById('perm_eliminar');
                            
                            if (chk_ver) chk_ver.checked = data.puede_ver ? true : false;
                            if (chk_crear) chk_crear.checked = data.puede_crear ? true : false;
                            if (chk_editar) chk_editar.checked = data.puede_editar ? true : false;
                            if (chk_eliminar) chk_eliminar.checked = data.puede_eliminar ? true : false;
                        } catch(e) {
                            console.error('Error parsing JSON:', e);
                        }
                    },
                    error: function(err) {
                        console.error('Error cargando permisos:', err);
                    }
                });
            }
        } catch(e) {
            console.error('Error en seleccionarNivel:', e);
        }
    };
    </script>
<?php if($srCanSeeNotifications): ?>
<link rel="stylesheet" href="<?=htmlspecialchars($srBase,ENT_QUOTES,'UTF-8')?>/assets/css/servicios_notificaciones.css?v=20261028">
<script src="<?=htmlspecialchars($srBase,ENT_QUOTES,'UTF-8')?>/assets/js/servicios_notificaciones.js?v=20261126" defer></script>
<?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-not-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__wobble" src="<?php echo defined('ASSETS_URL') ? ASSETS_URL : '/soporte/assets'; ?>/img/logo.png" alt="Logo" height="60" width="60" onerror="this.style.display='none'">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/soporte'; ?>/home.php" class="nav-link">Inicio</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <?php if($srCanSeeNotifications): ?>
            <li class="nav-item snb" id="serviceNotifications" data-base="<?=htmlspecialchars($srBase,ENT_QUOTES,'UTF-8')?>">
              <button type="button" class="nav-link snb-toggle" aria-label="Notificaciones de servicios" aria-expanded="false" aria-controls="serviceNotificationPanel"><i class="far fa-bell"></i><span class="snb-badge" hidden></span></button>
              <section class="snb-panel" id="serviceNotificationPanel" aria-label="Avisos de renovación" hidden><header><strong>Renovaciones de servicios</strong><span class="snb-total"></span></header><div class="snb-list">Consultando avisos…</div><footer><a href="<?=htmlspecialchars($srBase,ENT_QUOTES,'UTF-8')?>/modules/activos/renovaciones.php">Ver centro de renovaciones</a><button type="button" class="snb-read-all" hidden>Marcar todas como leídas</button></footer></section>
            </li>
            <?php endif; ?>
            <!-- Fullscreen Toggle -->
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            
            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="ml-1"><?php echo $user ? htmlspecialchars($user['name']) : 'Usuario'; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/soporte'; ?>/profile.php" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Mi Perfil
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/soporte'; ?>/logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <?php include __DIR__ . '/partials/sidebar_dinamico.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">

