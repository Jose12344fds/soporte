-- ============================================================
-- Migración: Vista combinada Módulo 1 + 2 (FH_ING)
-- Ejecutar UNA SOLA VEZ en producción
-- ============================================================

-- 1. Agregar actividad_id a pln_dtll_Ing para vincular cada plano a su actividad
ALTER TABLE pln_dtll_Ing
  ADD COLUMN actividad_id INT NOT NULL DEFAULT 0 COMMENT 'FK a fh_ing_actividades.id' AFTER ot,
  ADD INDEX idx_pln_actividad (actividad_id);

-- 2. Cambiar fh_ing_planos_det.dias de INT a DECIMAL(8,2) para soportar días decimales
--    (0.25 = 2h, 0.5 = 4h, 1 = 8h, etc.)
ALTER TABLE fh_ing_planos_det
  MODIFY COLUMN dias DECIMAL(8,2) NOT NULL DEFAULT 0.00;

-- 3. Agregar columna estado a fh_ing_planos_cab si no existe (por compatibilidad)
ALTER TABLE fh_ing_planos_cab
  ADD COLUMN IF NOT EXISTS estado TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '-1=sin cronograma, 0=mod1, 1=aprobado/mod2, 2=aprobado mod2/mod3, 3=finalizado'
  AFTER fecha_inicio;
