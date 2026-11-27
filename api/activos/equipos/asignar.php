<?php
require __DIR__ . '/bootstrap.php';
activos_api('editar', 'POST', static function (PDO $connection, array $input): array {
    $operator = current_user();
    return ['data' => activos_asignar($connection, $input, (int)$operator['id']), 'message' => 'Asignacion actualizada correctamente.'];
});
