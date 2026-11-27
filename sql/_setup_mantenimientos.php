<?php
/**
 * Script de configuración - Módulo Mantenimientos
 * Ejecutar UNA SOLA VEZ desde el navegador:
 *   http://<servidor>/soporte/sql/_setup_mantenimientos.php
 *
 * Registra el módulo en MySQL (tama):
 *   1. Tabla: modulos
 *   2. Tabla: permisos_modulos  (para todos los grupos)
 *   3. Tabla: module_pages      (páginas de navegación)
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'tama';

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<b>Error de conexión:</b> ' . $conn->connect_error);
}

$log = [];
$errors = [];

function exec_sql(mysqli $c, string $sql, string $desc, array &$log, array &$errors): void {
    if ($c->query($sql)) {
        $log[] = "✅ {$desc}";
    } else {
        $errors[] = "❌ {$desc} — " . $c->error;
    }
}

// ─────────────────────────────────────────────
// 1. Registrar módulo en tabla `modulos`
// ─────────────────────────────────────────────
$sql = "INSERT INTO modulos (slug, nombre, descripcion, icono, estado, orden)
        VALUES ('mantenimientos', 'Mantenimiento', 'Registro de órdenes de trabajo y programación de mantenimiento de equipos', 'fas fa-tools', 1, 50)
        ON DUPLICATE KEY UPDATE
            nombre      = VALUES(nombre),
            descripcion = VALUES(descripcion),
            icono       = VALUES(icono),
            estado      = 1";
exec_sql($conn, $sql, 'Módulo mantenimientos en tabla modulos', $log, $errors);

// ─────────────────────────────────────────────
// 2. Obtener grupos activos
// ─────────────────────────────────────────────
$grupos = [];
$r = $conn->query("SELECT group_level, group_name FROM user_groups WHERE group_status = 1 ORDER BY group_level ASC");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $grupos[] = $row;
    }
}

if (empty($grupos)) {
    // Fallback: si no existe user_groups, insertar para niveles 1-5
    $grupos = [
        ['group_level'=>1, 'group_name'=>'Administrador'],
        ['group_level'=>2, 'group_name'=>'Supervisor'],
        ['group_level'=>3, 'group_name'=>'Usuario'],
        ['group_level'=>4, 'group_name'=>'Operario'],
        ['group_level'=>5, 'group_name'=>'Solo lectura'],
    ];
    $log[] = 'ℹ️ No se encontró tabla user_groups — usando niveles 1-5 por defecto';
}

// ─────────────────────────────────────────────
// 3. Permisos en `permisos_modulos`
//    Nivel 1 (admin)     → todos
//    Nivel 2 (supervis.) → ver, crear, editar
//    Nivel 3+            → solo ver
// ─────────────────────────────────────────────
foreach ($grupos as $g) {
    $level = (int)$g['group_level'];
    $ver    = 1;
    $crear  = $level <= 2 ? 1 : 0;
    $editar = $level <= 2 ? 1 : 0;
    $elim   = $level == 1 ? 1 : 0;

    $sql = "INSERT INTO permisos_modulos (group_level, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar)
            VALUES ({$level}, 'mantenimientos', {$ver}, {$crear}, {$editar}, {$elim})
            ON DUPLICATE KEY UPDATE
                puede_ver      = VALUES(puede_ver),
                puede_crear    = VALUES(puede_crear),
                puede_editar   = VALUES(puede_editar),
                puede_eliminar = VALUES(puede_eliminar)";
    exec_sql($conn, $sql, "Permiso nivel {$level} ({$g['group_name']})", $log, $errors);
}

// ─────────────────────────────────────────────
// 4. Páginas de navegación en `module_pages`
// ─────────────────────────────────────────────

// Verificar si existe la tabla module_pages
$tableCheck = $conn->query("SHOW TABLES LIKE 'module_pages'");
if ($tableCheck && $tableCheck->num_rows > 0) {

    // Raíz del módulo
    $sql = "INSERT INTO module_pages (modulo_slug, parent_id, title, url, icon, orden, estado)
            VALUES ('mantenimientos', NULL, 'Mantenimiento Equipos', '/soporte/modules/mantenimientos/index.php', 'fas fa-tools', 1, 1)
            ON DUPLICATE KEY UPDATE updated_at = NOW()";
    exec_sql($conn, $sql, 'Página raíz mantenimientos', $log, $errors);

    // Obtener ID raíz
    $rootRow = $conn->query("SELECT id FROM module_pages WHERE modulo_slug='mantenimientos' AND parent_id IS NULL LIMIT 1");
    $rootId  = $rootRow ? (int)$rootRow->fetch_assoc()['id'] : null;

    if ($rootId) {
        $pages = [
            ['Órdenes de Trabajo',         '/soporte/modules/mantenimientos/index.php',        'fas fa-clipboard-list', 1],
            ['Nueva Orden de Trabajo',     '/soporte/modules/mantenimientos/nueva_ot.php',      'fas fa-plus-circle',    2],
            ['Programación Mantenimiento', '/soporte/modules/mantenimientos/programacion.php',  'fas fa-calendar-alt',   3],
            ['Nueva Programación',         '/soporte/modules/mantenimientos/nueva_prog.php',    'fas fa-calendar-plus',  4],
        ];
        foreach ($pages as [$title, $url, $icon, $orden]) {
            $titleEsc = $conn->real_escape_string($title);
            $urlEsc   = $conn->real_escape_string($url);
            $iconEsc  = $conn->real_escape_string($icon);
            $sql = "INSERT INTO module_pages (modulo_slug, parent_id, title, url, icon, orden, estado)
                    VALUES ('mantenimientos', {$rootId}, '{$titleEsc}', '{$urlEsc}', '{$iconEsc}', {$orden}, 1)
                    ON DUPLICATE KEY UPDATE updated_at = NOW()";
            exec_sql($conn, $sql, "Página: {$title}", $log, $errors);
        }
    }
} else {
    $log[] = 'ℹ️ Tabla module_pages no encontrada — se omiten las páginas de navegación';
}

$conn->close();

// ─────────────────────────────────────────────
// Salida HTML
// ─────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Setup Mantenimientos</title>
<style>
  body { font-family: monospace; background:#1e1e1e; color:#d4d4d4; padding:2rem; }
  h2   { color:#569cd6; }
  .ok  { color:#4ec9b0; }
  .err { color:#f44747; }
  .info{ color:#dcdcaa; }
  a    { color:#ce9178; }
</style>
</head>
<body>
<h2>⚙️ Setup Módulo Mantenimientos</h2>
<hr>
<?php foreach ($log as $line): ?>
    <div class="<?= strpos($line,'✅')!==false ? 'ok' : (strpos($line,'❌')!==false ? 'err' : 'info') ?>">
        <?= htmlspecialchars($line) ?>
    </div>
<?php endforeach; ?>
<?php foreach ($errors as $line): ?>
    <div class="err"><?= htmlspecialchars($line) ?></div>
<?php endforeach; ?>
<hr>
<?php if (empty($errors)): ?>
    <p class="ok"><b>✅ Configuración completada sin errores.</b></p>
    <p>Accede al módulo: <a href="/soporte/modules/mantenimientos/index.php">
        /soporte/modules/mantenimientos/index.php
    </a></p>
    <p>Nueva OT: <a href="/soporte/modules/mantenimientos/nueva_ot.php">
        /soporte/modules/mantenimientos/nueva_ot.php
    </a></p>
<?php else: ?>
    <p class="err"><b>⚠️ Hay errores. Revisa la configuración.</b></p>
<?php endif; ?>
</body>
</html>
