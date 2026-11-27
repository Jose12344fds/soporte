<?php
require __DIR__ . '/bootstrap.php';
activos_api('editar', 'POST', static function (PDO $connection, array $input): array {
    return ['data' => activos_guardar($connection, $input, activos_id($input, 'IdMaquina')), 'message' => 'Activo actualizado correctamente.'];
});
