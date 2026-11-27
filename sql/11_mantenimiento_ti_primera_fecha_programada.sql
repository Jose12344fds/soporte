-- Separa la vigencia del plan de la fecha ancla de su recurrencia.
-- No modifica ninguna ocurrencia existente.

ALTER TABLE `activos_mantenimiento_planes`
  ADD COLUMN IF NOT EXISTS `primera_fecha_programada` DATE NULL AFTER `fecha_inicio`;

UPDATE `activos_mantenimiento_planes`
SET `primera_fecha_programada` = `fecha_inicio`
WHERE `primera_fecha_programada` IS NULL;

ALTER TABLE `activos_mantenimiento_planes`
  MODIFY `primera_fecha_programada` DATE NOT NULL;
