<?php
/** Servicios de aplicacion compartidos por las vistas PHP y los endpoints JSON. */
defined('ACTIVOS_ROOT') || define('ACTIVOS_ROOT', dirname(__DIR__, 3));
require_once __DIR__ . '/compartido.php';
if (isset($db)) $db->query('SET NAMES utf8mb4');

function activos_modulo_mensaje(array &$messages, string $type, $text): void
{
    foreach ((array)$text as $message) {
        $messages[] = ['type' => $type, 'text' => (string)$message];
    }
}

function activos_modulo_resultado(array $messages): array
{
    $errors = array_column(array_filter($messages, static fn($m) => $m['type'] === 'd'), 'text');
    return $errors
        ? ['success' => false, 'error' => implode(' ', $errors)]
        : ['success' => true, 'message' => implode(' ', array_column($messages, 'text')) ?: 'Datos guardados correctamente.'];
}

/** Rutas y claves admitidas. Nunca se construyen includes con valores del cliente. */
function activos_modulo_operaciones(): array
{
    return [
        'servicios' => ['save_service' => 'IdServicio', 'save_service_contract' => 'IdContrato'],
        'aplicaciones' => ['save_application' => 'IdAplicacionActivo', 'save_contract' => 'IdContrato', 'save_installation' => null],
        'copias' => ['save_backup' => 'IdCopia', 'save_record' => null],
        'informacion' => ['save_info' => 'IdActivoInformacion'],
        'ubicaciones' => ['save_location' => 'IdUbicacion', 'save_role' => 'IdRol', 'assign_machines' => false],
    ];
}
