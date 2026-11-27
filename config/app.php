<?php
/**
 * Configuración global de la aplicación
 * Sistema de Soporte TI - AdminLTE 3.2
 */

// Configuración de la aplicación
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/urls.php';

// Rutas base
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));
defined('ASSETS_PATH') || define('ASSETS_PATH', BASE_PATH . '/assets');
defined('LAYOUTS_PATH') || define('LAYOUTS_PATH', BASE_PATH . '/layouts');

// URLs base (ajustar según tu servidor)
defined('ASSETS_URL') || define('ASSETS_URL', BASE_URL . '/assets');

// Zona horaria
date_default_timezone_set(APP_TIMEZONE);

// Manejo de errores según entorno
if (defined('APP_ENV') && APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
