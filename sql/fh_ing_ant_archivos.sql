-- Tabla de documentos de antecedentes para la Actividad 1 (FH ING)
-- Ejecutar en la base de datos "tama"
CREATE TABLE IF NOT EXISTS fh_ing_ant_archivos (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
