-- Tabla de imágenes referenciales para anotaciones de planos (FH ING)
-- Ejecutar en la base de datos "tama"
CREATE TABLE IF NOT EXISTS fh_ing_obs_imagenes (
    id               INT          NOT NULL AUTO_INCREMENT,
    id_obs           INT          NOT NULL  COMMENT 'FK a fh_ing_plano_obs.id',
    nombre_original  VARCHAR(255) NOT NULL,
    nombre_guardado  VARCHAR(255) NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_obs (id_obs)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
