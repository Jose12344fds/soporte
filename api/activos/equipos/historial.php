<?php
require __DIR__ . '/bootstrap.php';
activos_api('ver', 'GET', static function (PDO $connection, array $input): array {
    return activos_historial($connection, activos_id($input));
});
