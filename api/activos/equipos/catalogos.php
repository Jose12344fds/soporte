<?php
require __DIR__ . '/bootstrap.php';
activos_api(['ver', 'crear', 'editar'], 'GET', static function (PDO $connection, array $input): array {
    return ['data' => activos_catalogos_formulario($connection, activos_id($input, 'id', true))];
});
