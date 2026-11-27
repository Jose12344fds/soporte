CREATE TABLE IF NOT EXISTS `mantenimientos_equipo_imagenes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sap_equipo_code` VARCHAR(50) NOT NULL,
  `item_code` VARCHAR(100) NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_relativa` VARCHAR(500) NOT NULL COMMENT 'Estructura: YYYY/MM/equipo_CODE/archivo',
  `mime_type` VARCHAR(100) NOT NULL,
  `tamano_bytes` BIGINT UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_equipo_imagen_code` (`sap_equipo_code`),
  UNIQUE KEY `uq_equipo_imagen_archivo` (`nombre_archivo`),
  KEY `idx_equipo_imagen_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
