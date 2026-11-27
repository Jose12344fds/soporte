-- Programacion recurrente de mantenimientos
-- Propuesta de tablas locales para complementar SAP:
-- - @TAMA_PROGMANT queda como origen / plan legado en SAP
-- - @TAMA_ORDTRABAJO sigue siendo la evidencia de ejecucion en SAP
-- - estas tablas locales controlan planes, ocurrencias y calendario por periodo

START TRANSACTION;

CREATE TABLE IF NOT EXISTS mantenimientos_planes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sap_prog_code VARCHAR(30) DEFAULT NULL,
    codigo_plan VARCHAR(30) NOT NULL,
    descripcion VARCHAR(150) NOT NULL,
    itemcode VARCHAR(60) NOT NULL,
    tipomant VARCHAR(20) NOT NULL,
    frecuencia VARCHAR(20) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    generar_automatico TINYINT(1) NOT NULL DEFAULT 1,
    observacion TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mant_planes_codigo_plan (codigo_plan),
    KEY idx_mant_planes_itemcode (itemcode),
    KEY idx_mant_planes_freq (frecuencia),
    KEY idx_mant_planes_activo (activo),
    KEY idx_mant_planes_sap_prog_code (sap_prog_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mantenimientos_calendario_periodos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    anio SMALLINT NOT NULL,
    tipo_periodo VARCHAR(20) NOT NULL,
    periodo_numero SMALLINT NOT NULL,
    mes_numero TINYINT DEFAULT NULL,
    quincena_numero TINYINT DEFAULT NULL,
    semana_iso TINYINT DEFAULT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    label_periodo VARCHAR(60) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mant_cal_periodo (anio, tipo_periodo, periodo_numero),
    KEY idx_mant_cal_fechas (fecha_inicio, fecha_fin),
    KEY idx_mant_cal_tipo (tipo_periodo, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mantenimientos_plan_ocurrencias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_id INT UNSIGNED NOT NULL,
    calendario_id BIGINT UNSIGNED NOT NULL,
    sap_prog_code VARCHAR(30) DEFAULT NULL,
    anio SMALLINT NOT NULL,
    tipo_periodo VARCHAR(20) NOT NULL,
    periodo_numero SMALLINT NOT NULL,
    fecha_programada DATE NOT NULL,
    fecha_inicio_periodo DATE NOT NULL,
    fecha_fin_periodo DATE NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
    ot_docentry INT DEFAULT NULL,
    ot_docnum INT DEFAULT NULL,
    fecha_ejecucion DATE DEFAULT NULL,
    ejecutado_fuera_fecha TINYINT(1) NOT NULL DEFAULT 0,
    observacion TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mant_ocurrencia_periodo (plan_id, calendario_id),
    KEY idx_mant_ocurr_plan (plan_id),
    KEY idx_mant_ocurr_estado (estado),
    KEY idx_mant_ocurr_fecha (fecha_programada),
    KEY idx_mant_ocurr_ot (ot_docentry),
    CONSTRAINT fk_mant_ocurr_plan
        FOREIGN KEY (plan_id) REFERENCES mantenimientos_planes(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mant_ocurr_calendario
        FOREIGN KEY (calendario_id) REFERENCES mantenimientos_calendario_periodos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mantenimientos_ot_relacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ocurrencia_id BIGINT UNSIGNED NOT NULL,
    ot_docentry INT NOT NULL,
    ot_docnum INT DEFAULT NULL,
    estado_ot VARCHAR(10) NOT NULL DEFAULT 'O',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mant_ot_rel_docentry (ot_docentry),
    KEY idx_mant_ot_rel_ocurr (ocurrencia_id),
    CONSTRAINT fk_mant_ot_rel_ocurrencia
        FOREIGN KEY (ocurrencia_id) REFERENCES mantenimientos_plan_ocurrencias(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla opcional para historico de sincronizacion con SAP
CREATE TABLE IF NOT EXISTS mantenimientos_sync_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entidad VARCHAR(40) NOT NULL,
    referencia VARCHAR(60) NOT NULL,
    accion VARCHAR(30) NOT NULL,
    detalle TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mant_sync_entidad (entidad, referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- Notas de uso:
-- 1. frecuencia admite: Anual, Semestral, Trimestral, Bimensual, Mensual, Quincenal, Semanal
-- 2. tipo_periodo en calendario/ocurrencias debe usar exactamente esos mismos valores
-- 3. mantenimientos_planes = cabecera / plan maestro
-- 4. mantenimientos_plan_ocurrencias = periodos reales a ejecutar
-- 5. mantenimientos_ot_relacion = cruce de lo ejecutado contra la OT en SAP

