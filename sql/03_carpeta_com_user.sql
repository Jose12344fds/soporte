-- =============================================================
-- Módulo: CarpetComUser — Carpeta Compartida de Documentos
-- Ejecutar una sola vez en la base de datos `tama`
-- =============================================================

-- Tabla principal de archivos subidos
CREATE TABLE IF NOT EXISTS `carpeta_archivos` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)       NOT NULL,
  `nombre_original` VARCHAR(500)  NOT NULL,
  `nombre_guardado` VARCHAR(255)  NOT NULL,
  `tipo_mime`       VARCHAR(120)  NOT NULL,
  `extension`       VARCHAR(10)   NOT NULL,
  `tamano`          BIGINT(20)    NOT NULL DEFAULT 0,
  `area`            VARCHAR(50)   DEFAULT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_area`    (`area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de compartidos entre usuarios
CREATE TABLE IF NOT EXISTS `carpeta_compartidos` (
  `id`             INT(11)   NOT NULL AUTO_INCREMENT,
  `archivo_id`     INT(11)   NOT NULL,
  `compartido_por` INT(11)   NOT NULL,
  `compartido_con` INT(11)   NOT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_share` (`archivo_id`, `compartido_con`),
  KEY `idx_compartido_con` (`compartido_con`),
  CONSTRAINT `fk_cc_archivo`
    FOREIGN KEY (`archivo_id`) REFERENCES `carpeta_archivos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Registrar el módulo en el catálogo del sistema
INSERT IGNORE INTO `modulos` (`slug`, `nombre`, `descripcion`, `icono`, `estado`, `orden`)
VALUES ('CarpetComUser', 'Carpeta Compartida',
        'Carga y compartición de documentos entre usuarios',
        'fas fa-folder-open', 1, 10);

-- Permisos predeterminados por grupo:
--   Admin   (1): acceso total
--   Special (2): ver + subir
--   User    (3): ver + subir
-- (puede_editar y puede_eliminar quedan en 0 para usuarios normales;
--  solo Admin puede usar admin.php que requiere 'eliminar')
INSERT IGNORE INTO `permisos_modulos`
    (`group_level`, `modulo`, `puede_ver`, `puede_crear`, `puede_editar`, `puede_eliminar`)
VALUES
    (1, 'CarpetComUser', 1, 1, 1, 1),
    (2, 'CarpetComUser', 1, 1, 0, 0),
    (3, 'CarpetComUser', 1, 1, 0, 0);
