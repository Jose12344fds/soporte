<?php
/**
 * Sistema de Enrutamiento Centralizado
 * Sistema de Soporte TI - TAMA
 * 
 * Este archivo controla todas las rutas y previene
 * el acceso directo a archivos de módulos.
 */

class Router {
    private $routes = [];
    private $current_module = null;
    private $current_action = null;

    public function __construct() {
        $this->register_routes();
    }

    /**
     * Registrar todas las rutas del sistema
     */
    private function register_routes() {
        // Módulo Licencias
        $this->routes['licencias'] = [
            'index' => [
                'file' => 'modules/licencias/index.php',
                'permission' => 'licencias|ver'
            ],
            'office' => [
                'file' => 'modules/licencias/office.php',
                'permission' => 'licencias|ver'
            ],
            'sap' => [
                'file' => 'modules/licencias/sap.php',
                'permission' => 'licencias|ver'
            ],
            'agregar_office' => [
                'file' => 'modules/licencias/agregar_office.php',
                'permission' => 'licencias|crear'
            ],
            'editar_office' => [
                'file' => 'modules/licencias/editar_office.php',
                'permission' => 'licencias|editar'
            ],
            'agregar_sap' => [
                'file' => 'modules/licencias/agregar_sap.php',
                'permission' => 'licencias|crear'
            ],
            'editar_sap' => [
                'file' => 'modules/licencias/editar_sap.php',
                'permission' => 'licencias|editar'
            ]
        ];

        // Módulo Activos
        $this->routes['activos'] = [
            'index' => [
                'file' => 'modules/activos/index.php',
                'permission' => 'activos|ver'
            ],
            'agregar' => [
                'file' => 'modules/activos/agregar.php',
                'permission' => 'activos|crear'
            ],
            'editar' => [
                'file' => 'modules/activos/editar.php',
                'permission' => 'activos|editar'
            ]
        ];

        // Módulo Gestión de Capacidad TI
        $this->routes['capacidad_ti'] = [
            'index' => [
                'file' => 'modules/capacidad_ti/index.php',
                'permission' => 'capacidad_ti|ver'
            ]
        ];

        // Módulo Gestión de Eventos TI
        $this->routes['eventos_ti'] = [
            'index' => [
                'file' => 'modules/eventos_ti/index.php',
                'permission' => 'eventos_ti|ver'
            ],
            'detalle' => [
                'file' => 'modules/eventos_ti/detalle.php',
                'permission' => 'eventos_ti|ver'
            ]
        ];

        // Módulo Celulares
        $this->routes['celulares'] = [
            'index' => [
                'file' => 'modules/celulares/index.php',
                'permission' => 'celulares|ver'
            ],
            'agregar' => [
                'file' => 'modules/celulares/agregar.php',
                'permission' => 'celulares|crear'
            ],
            'editar' => [
                'file' => 'modules/celulares/editar.php',
                'permission' => 'celulares|editar'
            ],
            'asignar' => [
                'file' => 'modules/celulares/asignar.php',
                'permission' => 'celulares|editar'
            ]
        ];

        // Módulo VPN
        $this->routes['vpn'] = [
            'index' => [
                'file' => 'modules/vpn/index.php',
                'permission' => 'vpn|ver'
            ]
        ];

        // Módulo Usuarios
        $this->routes['usuarios'] = [
            'index' => [
                'file' => 'modules/usuarios/index.php',
                'permission' => 'usuarios|ver'
            ],
            'agregar' => [
                'file' => 'modules/usuarios/agregar.php',
                'permission' => 'usuarios|crear'
            ],
            'editar' => [
                'file' => 'modules/usuarios/editar.php',
                'permission' => 'usuarios|editar'
            ],
            'eliminar' => [
                'file' => 'modules/usuarios/eliminar.php',
                'permission' => 'usuarios|eliminar'
            ],
            'grupos' => [
                'file' => 'modules/usuarios/grupos.php',
                'permission' => 'grupos|ver'
            ]
        ];

        // Módulo Administración de Módulos (requiere acceso de Admin)
        $this->routes['admin_modulos'] = [
            'index' => [
                'file' => 'modules/admin_modulos/index.php',
                'permission' => 'admin_modulos|ver'
            ]
        ];

        // Módulo Reportes
        $this->routes['reportes'] = [
            'activos' => [
                'file' => 'modules/reportes/activos.php',
                'permission' => 'reportes|ver'
            ],
            'vpn' => [
                'file' => 'modules/reportes/vpn.php',
                'permission' => 'reportes|ver'
            ]
        ];
    }

    /**
     * Procesar una solicitud de ruta
     * @param string $module Módulo solicitado
     * @param string $action Acción a realizar
     * @return bool
     */
    public function dispatch($module, $action = 'index') {
        $this->current_module = $module;
        $this->current_action = $action;

        // Validar que el módulo existe
        if (!isset($this->routes[$module])) {
            $this->handle_not_found();
            return false;
        }

        // Validar que la acción existe
        if (!isset($this->routes[$module][$action])) {
            $this->handle_not_found();
            return false;
        }

        $route = $this->routes[$module][$action];

        // Obtener archivo a cargar
        $file_path = SITE_ROOT . DS . '..' . DS . $route['file'];

        // Validar que el archivo existe
        if (!file_exists($file_path)) {
            $this->handle_not_found();
            return false;
        }

        // Verificar permisos
        if (!$this->check_permission($route['permission'])) {
            $this->handle_unauthorized();
            return false;
        }

        // Cargar fichero
        require_once($file_path);
        return true;
    }

    /**
     * Verificar permisos
     * @param string $permission Formato: "modulo|accion"
     * @return bool
     */
    private function check_permission($permission) {
        list($module, $action) = explode('|', $permission);
        
        // Admin (nivel 1) siempre tiene acceso a admin_modulos
        if ($module === 'admin_modulos') {
            $user = current_user();
            if ($user && $user['user_level'] <= 1) {
                return true;
            }
        }
        
        return tiene_permiso($module, $action);
    }

    /**
     * Manejar ruta no encontrada
     */
    private function handle_not_found() {
        global $session;
        $session->msg('d', 'La página solicitada no existe.');
        redirect('/soporte/home.php', false);
        exit;
    }

    /**
     * Manejar acceso no autorizado
     */
    private function handle_unauthorized() {
        global $session;
        $session->msg('d', 'No tiene permiso para acceder a esta sección.');
        redirect('/soporte/home.php', false);
        exit;
    }

    /**
     * Obtener ruta URL para navigation
     * @param string $module
     * @param string $action
     * @return string
     */
    public function url($module, $action = 'index') {
        return "index.php?module={$module}&action={$action}";
    }

    /**
     * Obtener módulo actual
     */
    public function get_current_module() {
        return $this->current_module;
    }

    /**
     * Obtener acción actual
     */
    public function get_current_action() {
        return $this->current_action;
    }
}

// Instancia global del router
$router = new Router();
?>
