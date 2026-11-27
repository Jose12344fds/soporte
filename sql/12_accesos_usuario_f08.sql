-- Modernizacion no destructiva del registro T-TI-F-08.
-- La aplicacion ejecuta esta migracion automaticamente desde funciones.php.

ALTER TABLE `usuariosf08`
  ADD COLUMN IF NOT EXISTS `user_id` INT NULL AFTER `ID_Usuariosf08`,
  ADD COLUMN IF NOT EXISTS `maquina_id` INT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `created_by` INT NOT NULL DEFAULT 0 AFTER `estado_f8`,
  ADD COLUMN IF NOT EXISTS `updated_by` INT NOT NULL DEFAULT 0 AFTER `created_by`,
  ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL AFTER `updated_by`,
  ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL AFTER `created_at`;

ALTER TABLE `usuariosf08`
  MODIFY `CarpetasCompartidasM` TEXT NULL,
  MODIFY `CarpetasCompartidasV` TEXT NULL;

CREATE TABLE IF NOT EXISTS `usuariosf08_historial` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `registro_id` INT NOT NULL,
  `accion` VARCHAR(30) NOT NULL,
  `detalle` VARCHAR(500) NULL,
  `estado_anterior` TINYINT(1) NULL,
  `estado_nuevo` TINYINT(1) NULL,
  `registrado_por` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_f08_historial_registro` (`registro_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
