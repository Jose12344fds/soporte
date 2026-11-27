-- ============================================================
--  MÓDULO ENCUESTAS — Estructura de Base de Datos
--  Sistema de Soporte TI - TAMA
--  Fecha: 2026-03-27
-- ============================================================

-- ──────────────────────────────────────────────────────────
-- 1. Encuestas (cabecera)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS encuestas (
    id_encuesta   INT           NOT NULL AUTO_INCREMENT,
    titulo        VARCHAR(255)  NOT NULL,
    descripcion   TEXT          NULL,
    fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre  DATE          NULL          COMMENT 'NULL = sin fecha límite',
    activa        TINYINT(1)    NOT NULL DEFAULT 1,
    creado_por    INT UNSIGNED  NULL,
    PRIMARY KEY (id_encuesta),
    KEY idx_creado_por (creado_por),
    CONSTRAINT fk_encuestas_creado_por FOREIGN KEY (creado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cabecera de encuestas';

-- ──────────────────────────────────────────────────────────
-- 2. Preguntas
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS preguntas (
    id_pregunta   INT           NOT NULL AUTO_INCREMENT,
    id_encuesta   INT           NOT NULL,
    texto         TEXT          NOT NULL,
    tipo          ENUM('unica','multiple','abierta','escala') NOT NULL DEFAULT 'unica'
                  COMMENT 'unica=radio, multiple=checkbox, abierta=textarea, escala=1-5',
    requerida     TINYINT(1)    NOT NULL DEFAULT 1,
    orden         INT           NOT NULL DEFAULT 0,
    PRIMARY KEY (id_pregunta),
    FOREIGN KEY (id_encuesta) REFERENCES encuestas(id_encuesta) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Preguntas de cada encuesta';

-- ──────────────────────────────────────────────────────────
-- 3. Opciones de respuesta (solo para tipo unica / multiple / escala)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS opciones (
    id_opcion     INT           NOT NULL AUTO_INCREMENT,
    id_pregunta   INT           NOT NULL,
    texto         VARCHAR(255)  NOT NULL,
    valor         INT           NOT NULL DEFAULT 0 COMMENT 'Peso numérico para reportes',
    orden         INT           NOT NULL DEFAULT 0,
    PRIMARY KEY (id_opcion),
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Opciones de respuesta por pregunta';

-- ──────────────────────────────────────────────────────────
-- 4. Cabecera de respuesta (un registro por usuario/encuesta)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS respuestas (
    id_respuesta  INT           NOT NULL AUTO_INCREMENT,
    id_encuesta   INT           NOT NULL,
    id_usuario    INT UNSIGNED  NOT NULL,
    fecha         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_respuesta),
    UNIQUE KEY uk_encuesta_usuario (id_encuesta, id_usuario)
          COMMENT 'Cada usuario solo puede responder una vez por encuesta',
    FOREIGN KEY (id_encuesta) REFERENCES encuestas(id_encuesta) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de participación';

-- ──────────────────────────────────────────────────────────
-- 5. Detalle de respuestas (una fila por pregunta respondida)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS detalle_respuestas (
    id_detalle       INT  NOT NULL AUTO_INCREMENT,
    id_respuesta     INT  NOT NULL,
    id_pregunta      INT  NOT NULL,
    id_opcion        INT  NULL     COMMENT 'Preguntas cerradas: ID de la opción elegida',
    texto_respuesta  TEXT NULL     COMMENT 'Preguntas abiertas: texto libre',
    PRIMARY KEY (id_detalle),
    FOREIGN KEY (id_respuesta) REFERENCES respuestas(id_respuesta)   ON DELETE CASCADE,
    FOREIGN KEY (id_pregunta)  REFERENCES preguntas(id_pregunta)     ON DELETE CASCADE,
    FOREIGN KEY (id_opcion)    REFERENCES opciones(id_opcion)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Respuestas detalle por pregunta';
