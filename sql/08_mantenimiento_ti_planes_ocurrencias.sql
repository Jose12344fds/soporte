-- Mantenimiento TI (solo MySQL)
-- Estructura: plan maestro -> ocurrencias -> orden de mantenimiento existente.
-- Este script no modifica ni elimina las ordenes actuales.

CREATE TABLE IF NOT EXISTS `activos_mantenimiento_planes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NULL,
  `IdMaquina` INT NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `tipo` VARCHAR(20) NOT NULL DEFAULT 'PREVENTIVO',
  `prioridad` VARCHAR(15) NOT NULL DEFAULT 'MEDIA',
  `frecuencia` VARCHAR(20) NOT NULL,
  `intervalo` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `unidad_intervalo` VARCHAR(10) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `primera_fecha_programada` DATE NOT NULL,
  `fecha_fin` DATE NULL,
  `instruccion` TEXT NOT NULL,
  `tecnico_predeterminado` VARCHAR(120) NULL,
  `generar_automatico` TINYINT(1) NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultima_fecha_generada` DATE NULL,
  `creado_por` INT NOT NULL DEFAULT 0,
  `actualizado_por` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mti_plan_codigo` (`codigo`),
  KEY `idx_mti_plan_equipo` (`IdMaquina`, `activo`),
  KEY `idx_mti_plan_frecuencia` (`frecuencia`, `activo`),
  KEY `idx_mti_plan_fechas` (`fecha_inicio`, `fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `activos_mantenimiento_ocurrencias` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `mantenimiento_id` BIGINT UNSIGNED NULL COMMENT 'Orden TI generada en activos_mantenimientos',
  `fecha_original` DATE NOT NULL,
  `fecha_programada` DATE NOT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
  `observacion` TEXT NULL,
  `tecnico_responsable` VARCHAR(120) NULL,
  `motivo_cancelacion` VARCHAR(500) NULL,
  `creado_por` INT NOT NULL DEFAULT 0,
  `actualizado_por` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mti_ocurrencia_codigo` (`codigo`),
  UNIQUE KEY `uq_mti_ocurrencia_plan_fecha` (`plan_id`, `fecha_original`),
  UNIQUE KEY `uq_mti_ocurrencia_orden` (`mantenimiento_id`),
  KEY `idx_mti_ocurrencia_estado_fecha` (`estado`, `fecha_programada`),
  KEY `idx_mti_ocurrencia_plan_programada` (`plan_id`, `fecha_programada`),
  CONSTRAINT `fk_mti_ocurrencia_plan`
    FOREIGN KEY (`plan_id`) REFERENCES `activos_mantenimiento_planes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mti_ocurrencia_orden`
    FOREIGN KEY (`mantenimiento_id`) REFERENCES `activos_mantenimientos` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `activos_mantenimiento_ocurrencia_historial` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ocurrencia_id` BIGINT UNSIGNED NOT NULL,
  `accion` VARCHAR(30) NOT NULL,
  `fecha_anterior` DATE NULL,
  `fecha_nueva` DATE NULL,
  `estado_anterior` VARCHAR(20) NULL,
  `estado_nuevo` VARCHAR(20) NULL,
  `comentario` VARCHAR(500) NULL,
  `registrado_por` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mti_ocurrencia_historial` (`ocurrencia_id`, `created_at`),
  CONSTRAINT `fk_mti_ocurrencia_historial`
    FOREIGN KEY (`ocurrencia_id`) REFERENCES `activos_mantenimiento_ocurrencias` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
