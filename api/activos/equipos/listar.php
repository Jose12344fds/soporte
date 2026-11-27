<?php
require __DIR__ . '/bootstrap.php';
activos_api('ver', 'GET', static function (PDO $connection, array $input): array {
    if (isset($input['draw'])) {
        return activos_listado_tabla($connection, $input);
    }
    return ['data' => activos_listar($connection)];
}, true);