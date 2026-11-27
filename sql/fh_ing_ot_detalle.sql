-- ============================================================
-- Tabla: fh_ing_ot_detalle
-- Módulo: FH_ING — Registro de documentos por OT
-- ============================================================
CREATE TABLE IF NOT EXISTS `fh_ing_ot_detalle` (
    `id`            INT(11)       NOT NULL AUTO_INCREMENT,
    `ot`            VARCHAR(30)   NOT NULL COMMENT 'Código OT (ej: OTC-004713)',
    `proyecto`      VARCHAR(255)  NOT NULL COMMENT 'Nombre del proyecto (viene de SAP HANA)',
    `elaborado_por` VARCHAR(150)  NOT NULL DEFAULT '',
    `revisado_por`  VARCHAR(150)  NOT NULL DEFAULT '',
    `aprobado_por`  VARCHAR(150)  NOT NULL DEFAULT '',
    `actualizado_al` DATE         NULL     DEFAULT NULL COMMENT 'Fecha de actualización',
    `fecha_inicio` DATE NULL DEFAULT NULL COMMENT 'Inicio del proyecto, fijo desde el registro',
    `created_by`    INT(11)       NOT NULL DEFAULT 0,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ot` (`ot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci
  COMMENT='Registro de OT detalle para FH_ING';
