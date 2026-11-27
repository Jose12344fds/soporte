<?php
require __DIR__ . '/bootstrap.php';
activos_api(['ver', 'editar'], 'GET', static function (PDO $connection, array $input): array {
    return ['data' => activos_obtener($connection, activos_id($input))];
});
