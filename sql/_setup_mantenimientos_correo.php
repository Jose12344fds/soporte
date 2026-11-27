<?php
/**
 * Setup de destinatarios para correo semanal de programacion.
 * Ejecutar desde navegador:
 *   /soporte/sql/_setup_mantenimientos_correo.php
 */
require_once(__DIR__ . '/../includes/load.php');
require_once(__DIR__ . '/../config/app.php');
require_permiso('mantenimientos', 'editar');

global $db;

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `mantenimientos_programacion_correos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NULL,
  `nombre_destino` VARCHAR(120) NOT NULL,
  `correo_destino` VARCHAR(160) NOT NULL,
  `tipo_reporte` VARCHAR(50) NOT NULL DEFAULT 'PROGRAMACION_MANTENIMIENTO',
  `dia_envio` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Lunes, 2=Martes, ... 7=Domingo',
  `hora_envio` TIME NOT NULL DEFAULT '08:00:00',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `recibe_html` TINYINT(1) NOT NULL DEFAULT 1,
  `recibe_excel` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_envio` DATETIME NULL,
  `observacion` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prog_correo_reporte` (`tipo_reporte`, `correo_destino`),
  KEY `idx_prog_correo_user` (`user_id`),
  KEY `idx_prog_correo_activo` (`activo`, `dia_envio`, `hora_envio`),
  CONSTRAINT `fk_prog_correo_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL;

$ok = $db->query($sql);
$err = $ok ? '' : 'No se pudo crear la tabla.';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Setup correos programacion</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f9; padding:32px; color:#1f2933; }
    .box { max-width:760px; margin:auto; background:#fff; border:1px solid #dfe6ee; border-radius:8px; padding:24px; }
    .ok { color:#15803d; font-weight:700; }
    .err { color:#b91c1c; font-weight:700; }
    a { color:#0d6efd; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Setup correos de programacion</h2>
    <?php if ($ok): ?>
      <p class="ok">Tabla creada/verificada correctamente: mantenimientos_programacion_correos</p>
      <p><a href="/soporte/modules/mantenimientos/correos_programacion.php">Ir a destinatarios de correo</a></p>
    <?php else: ?>
      <p class="err"><?= htmlspecialchars($err) ?></p>
    <?php endif; ?>
  </div>
</body>
</html>
