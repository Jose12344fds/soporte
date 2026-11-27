<?php
require __DIR__ . '/bootstrap.php';
activos_api('editar', 'POST', static function (PDO $connection, array $input): array {
    return activos_evaluar($connection, $input) + ['message' => 'Evaluacion guardada correctamente.'];
});