<?php
/**
 * Migración: tablas para antecedentes e imágenes de observaciones (FH ING)
 * Visitar una sola vez en el navegador:
 *   http://<servidor>/soporte/sql/_run_fh_ing_ant.php
 */
require_once dirname(__DIR__) . '/includes/config.php';
$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$con) { die('Error de conexión: ' . mysqli_connect_error()); }
mysqli_set_charset($con, 'utf8mb4');

$results = [];

/* ── Tabla 1: fh_ing_ant_archivos ─────────────────────────── */
$sql1 = "CREATE TABLE IF NOT EXISTS fh_ing_ant_archivos (
    id               INT          NOT NULL AUTO_INCREMENT,
    ot               VARCHAR(50)  NOT NULL,
    actividad_id     INT          NOT NULL DEFAULT 1  COMMENT 'FK a fh_ing_actividades.id',
    nombre_original  VARCHAR(255) NOT NULL,
    nombre_guardado  VARCHAR(255) NOT NULL,
    tipo_mime        VARCHAR(100) DEFAULT NULL,
    tamano           BIGINT       DEFAULT NULL         COMMENT 'Tamaño en bytes',
    descripcion      VARCHAR(500) DEFAULT NULL,
    usuario          VARCHAR(120) NOT NULL DEFAULT '',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_ot_act (ot, actividad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($con, $sql1)) {
    $results[] = ['tabla' => 'fh_ing_ant_archivos', 'ok' => true,  'msg' => 'OK — tabla creada o ya existe'];
} else {
    $results[] = ['tabla' => 'fh_ing_ant_archivos', 'ok' => false, 'msg' => mysqli_error($con)];
}

/* ── Tabla 2: fh_ing_obs_imagenes ─────────────────────────── */
$sql2 = "CREATE TABLE IF NOT EXISTS fh_ing_obs_imagenes (
    id               INT          NOT NULL AUTO_INCREMENT,
    id_obs           INT          NOT NULL  COMMENT 'FK a fh_ing_plano_obs.id',
    nombre_original  VARCHAR(255) NOT NULL,
    nombre_guardado  VARCHAR(255) NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_obs (id_obs)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($con, $sql2)) {
    $results[] = ['tabla' => 'fh_ing_obs_imagenes', 'ok' => true,  'msg' => 'OK — tabla creada o ya existe'];
} else {
    $results[] = ['tabla' => 'fh_ing_obs_imagenes', 'ok' => false, 'msg' => mysqli_error($con)];
}

mysqli_close($con);

/* ── Directorios de upload ────────────────────────────────── */
$base    = dirname(__DIR__);
$dirs    = [
    $base . '/uploads/FH_ING/antecedentes',
    $base . '/uploads/FH_ING/obs_imgs',
];
foreach ($dirs as $d) {
    if (!is_dir($d)) {
        $ok = mkdir($d, 0755, true);
        $results[] = ['tabla' => $d, 'ok' => $ok, 'msg' => $ok ? 'Directorio creado' : 'No se pudo crear (verificar permisos)'];
    } else {
        $results[] = ['tabla' => $d, 'ok' => true, 'msg' => 'Directorio ya existe'];
    }
}

/* ── Output ───────────────────────────────────────────────── */
$allOk = array_reduce($results, fn($c, $r) => $c && $r['ok'], true);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Migración FH ING — Antecedentes</title>
<style>
body{font-family:monospace;padding:30px;background:#f5f5f5;}
table{border-collapse:collapse;width:100%;background:#fff;}
th,td{border:1px solid #ddd;padding:10px 14px;text-align:left;}
th{background:#1a3a5c;color:#fff;}
.ok{color:#27ae60;font-weight:bold;}
.fail{color:#e74c3c;font-weight:bold;}
h2{color:#1a3a5c;}
.summary{margin-top:18px;padding:14px 18px;border-radius:6px;font-size:1.05rem;}
.s-ok{background:#d4edda;color:#155724;}
.s-fail{background:#f8d7da;color:#721c24;}
</style>
</head>
<body>
<h2>Migración — FH ING Antecedentes</h2>
<table>
<tr><th>Objeto</th><th>Estado</th><th>Mensaje</th></tr>
<?php foreach ($results as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['tabla']) ?></td>
  <td class="<?= $r['ok'] ? 'ok' : 'fail' ?>"><?= $r['ok'] ? '✓ OK' : '✗ ERROR' ?></td>
  <td><?= htmlspecialchars($r['msg']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<div class="summary <?= $allOk ? 's-ok' : 's-fail' ?>">
  <?= $allOk
    ? '✓ Todo listo. Puedes cerrar esta página y usar el módulo normalmente.'
    : '✗ Hubo errores. Revisa los mensajes arriba y corrige antes de continuar.' ?>
</div>
</body>
</html>
