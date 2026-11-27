<?php
/**
 * Configuracion central del portal PHP.
 * Edite este archivo para cambiar servidor, URL o base de datos.
 * APP_URL incluye protocolo, puerto (si corresponde) y /soporte.
 */

// URL publica del portal, menus, correos y reportes exportados.
// Para cambiar de servidor edite solamente APP_URL. Incluya /soporte.
// Servidor de red de ejemplo: http://192.168.4.8:8085/soporte
// Los enlaces antiguos del menu se resuelven con esta URL al mostrarlos.
defined('APP_URL') || define('APP_URL', 'http://localhost/soporte');
defined('APP_NAME') || define('APP_NAME', 'Sistema de Soporte TI');
defined('APP_VERSION') || define('APP_VERSION', '2.0.0');
defined('APP_ENV') || define('APP_ENV', 'development');
defined('APP_TIMEZONE') || define('APP_TIMEZONE', 'America/Lima');

// Servicio .NET usado por el modulo HH META.
defined('SAP_API_BASE_URL') || define('SAP_API_BASE_URL', getenv('SAP_API_BASE_URL') ?: 'http://127.0.0.1:5099');

// Base de datos MySQL. DB_PORT corresponde al puerto MySQL.
 defined('DB_HOST') || define( 'DB_HOST', 'localhost' );
 defined('DB_USER') || define( 'DB_USER', 'root' );
 defined('DB_PASS') || define( 'DB_PASS', '3u6s8692' );
 defined('DB_NAME') || define( 'DB_NAME', 'tama' );
defined('DB_PORT') || define('DB_PORT', 3306);

// Valores derivados. Configure APP_URL arriba para los enlaces absolutos.
defined('BASE_URL') || define('BASE_URL', rtrim((string)parse_url(APP_URL, PHP_URL_PATH), '/'));
defined('REPORT_LOGO_URL') || define('REPORT_LOGO_URL', rtrim(APP_URL, '/') . '/assets/img/logo.png');
