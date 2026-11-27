-- VERIFICACION POSTERIOR A LA MIGRACION. SOLO LECTURA.
SELECT VERSION() AS mysql_version, DATABASE() AS base_actual;

SELECT table_name, engine, table_collation
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'activos_mantenimiento_checklist_plantillas',
    'activos_mantenimiento_checklist_items',
    'activos_mantenimiento_checklists',
    'activos_mantenimiento_checklist_respuestas',
    'activos_mantenimiento_hallazgos',
    'activos_mantenimiento_hallazgo_acciones',
    'activos_mantenimiento_hallazgo_evidencias',
    'activos_mantenimiento_hallazgo_historial'
  )
ORDER BY table_name;

SELECT table_name, column_name, column_type, is_nullable
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name IN (
    'activos_mantenimiento_checklist_plantillas',
    'activos_mantenimiento_checklist_items',
    'activos_mantenimiento_checklists',
    'activos_mantenimiento_checklist_respuestas',
    'activos_mantenimiento_hallazgos',
    'activos_mantenimiento_hallazgo_acciones',
    'activos_mantenimiento_hallazgo_evidencias',
    'activos_mantenimiento_hallazgo_historial'
  )
  AND (column_name = 'id' OR column_name LIKE '%_id')
ORDER BY table_name, ordinal_position;

SELECT constraint_name, table_name, referenced_table_name
FROM information_schema.referential_constraints
WHERE constraint_schema = DATABASE()
  AND table_name LIKE 'activos_mantenimiento_%'
ORDER BY table_name, constraint_name;

SELECT
  (SELECT COUNT(*) FROM activos_mantenimiento_checklist_plantillas) plantillas,
  (SELECT COUNT(*) FROM activos_mantenimiento_checklists) checklists,
  (SELECT COUNT(*) FROM activos_mantenimiento_hallazgos) hallazgos,
  (SELECT COUNT(*) FROM activos_mantenimiento_hallazgo_acciones) acciones;
