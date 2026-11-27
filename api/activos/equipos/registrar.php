<?php
require __DIR__ . '/bootstrap.php';
activos_api('crear', 'POST', static function (PDO $connection, array $input): array {
    return ['data' => activos_guardar($connection, $input), 'message' => 'Activo registrado correctamente.'];
});
