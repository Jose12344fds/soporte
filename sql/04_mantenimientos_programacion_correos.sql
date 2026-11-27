-- Destinatarios para el envio semanal de programacion de mantenimiento.
-- Relaciona usuarios locales con correos destino del reporte.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
