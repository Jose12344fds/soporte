<?php
/**
 * Crea la tabla local para evidencias fotograficas de las OT.
 * CLI: php sql/_setup_mantenimientos_evidencias.php
 */
require_once __DIR__ . '/../includes/load.php';

if (PHP_SAPI !== 'cli') {
    require_permiso('mantenimientos', 'editar');
}

global $db;
$sql = file_get_contents(__DIR__ . '/06_mantenimientos_ot_evidencias.sql');
$ok = is_string($sql) && $sql !== '' && $db->query($sql);

if (PHP_SAPI === 'cli') {
    echo $ok ? "TABLE_CREATED=TRUE\n" : "TABLE_CREATED=FALSE\n";
    exit($ok ? 0 : 1);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Setup evidencias de mantenimiento</title>
</head>
<body>
    <h2>Setup evidencias de mantenimiento</h2>
    <p><?= $ok ? 'Tabla creada/verificada correctamente.' : 'No se pudo crear la tabla.' ?></p>
    <a href="../modules/mantenimientos/index.php">Volver a mantenimientos</a>
</body>
</html>
