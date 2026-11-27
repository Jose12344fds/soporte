-- Tabla de observaciones/anotaciones por plano (Módulo 2 FH ING)
-- Ejecutar en la base de datos "tama"
CREATE TABLE IF NOT EXISTS fh_ing_plano_obs (
    id          INT          NOT NULL AUTO_INCREMENT,
    id_planos   INT          NOT NULL COMMENT 'FK a pln_dtll_Ing.id_planos',
    ot          VARCHAR(50)  NOT NULL,
    cod_plano   VARCHAR(100) NOT NULL DEFAULT '',
    texto       TEXT         NOT NULL,
    usuario     VARCHAR(120) NOT NULL DEFAULT '',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_id_planos (id_planos),
    INDEX idx_ot        (ot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
