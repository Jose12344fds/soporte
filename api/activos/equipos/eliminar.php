<?php
require __DIR__ . '/bootstrap.php';
activos_api('eliminar', 'POST', static function (PDO $connection, array $input): array {
    activos_eliminar($connection, activos_id($input));
    return ['message' => 'Activo eliminado correctamente.'];
});
