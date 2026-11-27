-- REINICIO SOLICITADO DEL MODULO CHECKLISTS / HALLAZGOS.
-- ELIMINA TODOS LOS DATOS, INCLUIDOS LOS CHECKLISTS PADRE.
-- NO ELIMINA LAS TABLAS NI LAS ORDENES DE MANTENIMIENTO.

START TRANSACTION;

DELETE FROM activos_mantenimiento_hallazgo_evidencias;
DELETE FROM activos_mantenimiento_hallazgo_historial;
DELETE FROM activos_mantenimiento_hallazgo_acciones;
DELETE FROM activos_mantenimiento_hallazgos;
DELETE FROM activos_mantenimiento_checklist_respuestas;
DELETE FROM activos_mantenimiento_checklists;
DELETE FROM activos_mantenimiento_checklist_items;
DELETE FROM activos_mantenimiento_checklist_plantillas;

COMMIT;

ALTER TABLE activos_mantenimiento_hallazgo_evidencias AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_hallazgo_historial AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_hallazgo_acciones AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_hallazgos AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_checklist_respuestas AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_checklists AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_checklist_items AUTO_INCREMENT = 1;
ALTER TABLE activos_mantenimiento_checklist_plantillas AUTO_INCREMENT = 1;

SELECT 'RESET COMPLETADO' AS resultado;
