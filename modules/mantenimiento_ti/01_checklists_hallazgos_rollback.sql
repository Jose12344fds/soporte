-- ROLLBACK EXCLUSIVO DE CHECKLISTS Y HALLAZGOS DE MANTENIMIENTO TI.
-- Ejecutar solo con respaldo y autorizacion. El orden respeta las FK.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activos_mantenimiento_hallazgo_historial;
DROP TABLE IF EXISTS activos_mantenimiento_hallazgo_evidencias;
DROP TABLE IF EXISTS activos_mantenimiento_hallazgo_acciones;
DROP TABLE IF EXISTS activos_mantenimiento_hallazgos;
DROP TABLE IF EXISTS activos_mantenimiento_checklist_respuestas;
DROP TABLE IF EXISTS activos_mantenimiento_checklists;
DROP TABLE IF EXISTS activos_mantenimiento_checklist_items;
DROP TABLE IF EXISTS activos_mantenimiento_checklist_plantillas;
SET FOREIGN_KEY_CHECKS = 1;
