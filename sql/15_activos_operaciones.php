<?php
/** Ejecutar desde consola con --check o --apply, despues del respaldo. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Disponible unicamente desde la consola.');
}
require_once dirname(__DIR__) . '/config/database.php';
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)) {
    fwrite(STDERR, "Uso: php sql/15_activos_operaciones.php --check|--apply\n");
    exit(2);
}
$connection = database_connection();
if ($mode === '--apply') {
    $connection->exec("CREATE TABLE IF NOT EXISTS activos_asignacion_historial (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        IdMaquina INT NOT NULL, usuario_anterior_id INT NULL, usuario_nuevo_id INT NULL,
        fecha_asignacion DATE NOT NULL, comentarios VARCHAR(250) NOT NULL DEFAULT '',
        accion VARCHAR(20) NOT NULL, registrado_por INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_activo_fecha (IdMaquina,created_at), KEY idx_usuario_nuevo (usuario_nuevo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
$pending = [];
foreach (['maquina', 'asig_maquina', 'licencias', 'activos_asignacion_historial', 'users'] as $table) {
    $statement = $connection->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->execute([$table]);
    $engine = $statement->fetchColumn();
    if (!$engine) {
        $pending[] = $table . ' inexistente';
        continue;
    }
    if (strcasecmp($engine, 'InnoDB') !== 0) {
        if ($mode === '--apply') {
            $connection->exec("ALTER TABLE `$table` ENGINE=InnoDB");
        } else {
            $pending[] = $table . ' requiere InnoDB';
        }
    }
}
foreach (['maquina' => 'IdMaquina', 'asig_maquina' => 'IdAsMaq', 'activos_asignacion_historial' => 'id'] as $table => $field) {
    $statement = $connection->prepare('SELECT COLUMN_TYPE,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $statement->execute([$table, $field]);
    $column = $statement->fetch();
    if (!$column || stripos($column['EXTRA'], 'auto_increment') !== false) {
        continue;
    }
    if ($mode === '--apply') {
        $next = (int)$connection->query("SELECT COALESCE(MAX(`$field`),0)+1 FROM `$table`")->fetchColumn();
        $connection->exec("UPDATE `$table` SET `$field`=$next WHERE `$field`=0");
        $connection->exec("ALTER TABLE `$table` MODIFY `$field` " . $column['COLUMN_TYPE'] . ' NOT NULL AUTO_INCREMENT');
    } else {
        $pending[] = $table . '.' . $field . ' requiere AUTO_INCREMENT';
    }
}
if ($pending) {
    fwrite(STDERR, implode(PHP_EOL, $pending) . PHP_EOL);
    exit(1);
}
echo 'Operaciones transaccionales de Activos verificadas en ' . DB_NAME . PHP_EOL;
