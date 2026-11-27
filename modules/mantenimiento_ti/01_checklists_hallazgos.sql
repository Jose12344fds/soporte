-- MODULO MANTENIMIENTO TI - CHECKLISTS Y HALLAZGOS
-- MySQL 8.0+ / InnoDB / utf8mb4
-- IMPORTANTE: Antes de ejecutar, verificar que:
-- activos_mantenimiento_planes.id, activos_mantenimientos.id y
-- activos_mantenimiento_ocurrencias.id son BIGINT UNSIGNED (confirmado en
-- src/db/sql.php y sql/08_mantenimiento_ti_planes_ocurrencias.sql).
-- users.id es INT UNSIGNED; los campos de actor conservan ese tipo sin FK.
-- Ejecutar primero en una BD de pruebas, con respaldo previo.
-- NO modifica tablas preexistentes. No ejecutar parcialmente sin revisar errores.
-- Las referencias a users.id se mantienen como valores lógicos porque su tipo
-- exacto y definición FK no han sido confirmados.

CREATE TABLE IF NOT EXISTS activos_mantenimiento_checklist_plantillas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(30) NULL,
  plan_id BIGINT UNSIGNED NULL COMMENT 'NULL: plantilla general para OT manual/correctiva',
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT NULL,
  version INT NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_por INT UNSIGNED NULL,
  actualizado_por INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_chk_plantilla_codigo (codigo),
  KEY ix_chk_plantilla_plan_activo (plan_id, activo),
  CONSTRAINT fk_chk_plantilla_plan FOREIGN KEY (plan_id)
    REFERENCES activos_mantenimiento_planes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT ck_chk_plantilla_version CHECK (version > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activos_mantenimiento_checklist_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  plantilla_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(30) NULL,
  descripcion VARCHAR(500) NOT NULL,
  tipo_respuesta ENUM('SI_NO_NA','CUMPLE_NO_CUMPLE_NA','NUMERICO','TEXTO') NOT NULL DEFAULT 'CUMPLE_NO_CUMPLE_NA',
  obligatorio TINYINT(1) NOT NULL DEFAULT 1,
  requiere_evidencia_si_falla TINYINT(1) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_chk_item_codigo_plantilla (plantilla_id, codigo),
  KEY ix_chk_items_orden (plantilla_id, activo, orden),
  CONSTRAINT fk_chk_item_plantilla FOREIGN KEY (plantilla_id)
    REFERENCES activos_mantenimiento_checklist_plantillas(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada OT admite varias plantillas, cada plantilla a lo sumo una vez por OT.
-- El plan de la OT se obtiene SIN almacenar plan_id duplicado:
-- ocurrencias.mantenimiento_id -> ocurrencias.plan_id.
-- La instancia contiene la fecha/versión de copia de la plantilla.
CREATE TABLE IF NOT EXISTS activos_mantenimiento_checklists (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mantenimiento_id BIGINT UNSIGNED NOT NULL,
  plantilla_id BIGINT UNSIGNED NOT NULL,
  nombre_snapshot VARCHAR(200) NOT NULL,
  descripcion_snapshot TEXT NULL,
  version_snapshot INT NOT NULL,
  estado ENUM('PENDIENTE','EN_PROCESO','COMPLETADO','ANULADO') NOT NULL DEFAULT 'PENDIENTE',
  asignado_por INT UNSIGNED NULL,
  ejecutado_por INT UNSIGNED NULL,
  fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_inicio DATETIME NULL,
  fecha_fin DATETIME NULL,
  observaciones TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_chk_ot_plantilla (mantenimiento_id, plantilla_id),
  KEY ix_chk_ot_estado (mantenimiento_id, estado),
  CONSTRAINT fk_chk_ot FOREIGN KEY (mantenimiento_id)
    REFERENCES activos_mantenimientos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_chk_ot_plantilla FOREIGN KEY (plantilla_id)
    REFERENCES activos_mantenimiento_checklist_plantillas(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Se preservan los valores de los ítems aun si se edita la plantilla.
-- respuesta_valor: SI / NO / NA, CUMPLE / NO_CUMPLE / NA,
-- cadena numérica validada en aplicación, o texto libre, según tipo.
CREATE TABLE IF NOT EXISTS activos_mantenimiento_checklist_respuestas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  checklist_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  descripcion_snapshot VARCHAR(500) NOT NULL,
  tipo_respuesta_snapshot ENUM('SI_NO_NA','CUMPLE_NO_CUMPLE_NA','NUMERICO','TEXTO') NOT NULL,
  obligatorio_snapshot TINYINT(1) NOT NULL,
  requiere_evidencia_si_falla_snapshot TINYINT(1) NOT NULL DEFAULT 0,
  orden_snapshot INT NOT NULL DEFAULT 0,
  respuesta_valor TEXT NULL,
  observacion TEXT NULL,
  respondido_por INT UNSIGNED NULL,
  fecha_respuesta DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_chk_respuesta_item (checklist_id, item_id),
  KEY ix_chk_respuesta_checklist (checklist_id, orden_snapshot),
  CONSTRAINT fk_chk_respuesta_checklist FOREIGN KEY (checklist_id)
    REFERENCES activos_mantenimiento_checklists(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_chk_respuesta_item FOREIGN KEY (item_id)
    REFERENCES activos_mantenimiento_checklist_items(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hallazgo puede nacer del checklist o registrarse manualmente desde la OT.
CREATE TABLE IF NOT EXISTS activos_mantenimiento_hallazgos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(30) NULL,
  mantenimiento_id BIGINT UNSIGNED NOT NULL,
  checklist_respuesta_id BIGINT UNSIGNED NULL,
  componente VARCHAR(200) NULL,
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  criticidad ENUM('BAJA','MEDIA','ALTA','CRITICA') NOT NULL DEFAULT 'MEDIA',
  estado ENUM('ABIERTO','EN_ATENCION','PENDIENTE_VERIFICACION','CERRADO','ANULADO') NOT NULL DEFAULT 'ABIERTO',
  requiere_parada TINYINT(1) NOT NULL DEFAULT 0,
  detectado_por INT UNSIGNED NULL,
  fecha_deteccion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verificado_por INT UNSIGNED NULL,
  fecha_verificacion DATETIME NULL,
  resultado_verificacion TEXT NULL,
  fecha_cierre DATETIME NULL,
  motivo_anulacion TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_hallazgo_codigo (codigo),
  KEY ix_hallazgo_ot_estado (mantenimiento_id, estado),
  KEY ix_hallazgo_respuesta (checklist_respuesta_id),
  CONSTRAINT fk_hallazgo_ot FOREIGN KEY (mantenimiento_id)
    REFERENCES activos_mantenimientos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_hallazgo_respuesta FOREIGN KEY (checklist_respuesta_id)
    REFERENCES activos_mantenimiento_checklist_respuestas(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activos_mantenimiento_hallazgo_acciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  hallazgo_id BIGINT UNSIGNED NOT NULL,
  tipo ENUM('ACCION_INMEDIATA','CORRECCION','ACCION_CORRECTIVA') NOT NULL,
  descripcion TEXT NOT NULL,
  responsable_id INT UNSIGNED NULL,
  fecha_compromiso DATETIME NULL,
  fecha_inicio DATETIME NULL,
  fecha_ejecucion DATETIME NULL,
  estado ENUM('PENDIENTE','EN_PROCESO','EJECUTADA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  mantenimiento_correctivo_id BIGINT UNSIGNED NULL,
  resultado TEXT NULL,
  creado_por INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_acciones_hallazgo_estado (hallazgo_id, estado),
  KEY ix_acciones_correctivo (mantenimiento_correctivo_id),
  CONSTRAINT fk_accion_hallazgo FOREIGN KEY (hallazgo_id)
    REFERENCES activos_mantenimiento_hallazgos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_accion_ot_correctiva FOREIGN KEY (mantenimiento_correctivo_id)
    REFERENCES activos_mantenimientos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activos_mantenimiento_hallazgo_evidencias (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  hallazgo_id BIGINT UNSIGNED NOT NULL,
  accion_id BIGINT UNSIGNED NULL,
  etapa ENUM('DETECCION','EJECUCION','VERIFICACION') NOT NULL,
  tipo ENUM('FOTO','VIDEO','DOCUMENTO') NOT NULL,
  archivo_ruta VARCHAR(500) NOT NULL,
  archivo_nombre_original VARCHAR(255) NULL,
  archivo_mime VARCHAR(120) NULL,
  descripcion VARCHAR(500) NULL,
  subido_por INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_evidencias_hallazgo (hallazgo_id, etapa),
  KEY ix_evidencias_accion (accion_id),
  CONSTRAINT fk_evidencia_hallazgo FOREIGN KEY (hallazgo_id)
    REFERENCES activos_mantenimiento_hallazgos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_evidencia_accion FOREIGN KEY (accion_id)
    REFERENCES activos_mantenimiento_hallazgo_acciones(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auditoría propia de cambios de estado y eventos. No confundir con historial OT.
CREATE TABLE IF NOT EXISTS activos_mantenimiento_hallazgo_historial (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  hallazgo_id BIGINT UNSIGNED NOT NULL,
  accion_id BIGINT UNSIGNED NULL,
  evento VARCHAR(60) NOT NULL,
  estado_anterior VARCHAR(40) NULL,
  estado_nuevo VARCHAR(40) NULL,
  comentario TEXT NULL,
  registrado_por INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_hallazgo_historial (hallazgo_id, created_at),
  CONSTRAINT fk_hh_hallazgo FOREIGN KEY (hallazgo_id)
    REFERENCES activos_mantenimiento_hallazgos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_hh_accion FOREIGN KEY (accion_id)
    REFERENCES activos_mantenimiento_hallazgo_acciones(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CONSULTA: plan/ocurrencia real de la OT (sin asumir relación 1:1 obligatoria).
-- SELECT o.id AS ocurrencia_id,p.id AS plan_id,p.nombre
-- FROM activos_mantenimiento_ocurrencias o
-- JOIN activos_mantenimiento_planes p ON p.id=o.plan_id
-- WHERE o.mantenimiento_id = @mantenimiento_id;
--
-- CONSULTA: respuestas del checklist de una OT.
-- SELECT c.id AS checklist_id,c.estado,r.id AS respuesta_id,
--        r.descripcion_snapshot,r.tipo_respuesta_snapshot,
--        r.respuesta_valor,r.observacion
-- FROM activos_mantenimiento_checklists c
-- JOIN activos_mantenimiento_checklist_respuestas r ON r.checklist_id=c.id
-- WHERE c.mantenimiento_id=@mantenimiento_id
-- ORDER BY c.id,r.orden_snapshot,r.id;
