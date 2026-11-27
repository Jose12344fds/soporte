CREATE TABLE IF NOT EXISTS `mantenimientos_ot_evidencias` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ot_docentry` INT UNSIGNED NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_relativa` VARCHAR(500) NOT NULL COMMENT 'Estructura: YYYY/MM/ot_DocEntry/archivo',
  `mime_type` VARCHAR(100) NOT NULL,
  `tamano_bytes` BIGINT UNSIGNED NOT NULL,
  `descripcion` VARCHAR(250) NULL,
  `user_id` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ot_evidencia_archivo` (`nombre_archivo`),
  KEY `idx_ot_evidencia_docentry` (`ot_docentry`, `created_at`),
  KEY `idx_ot_evidencia_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
