-- PREFLIGHT DE SOLO LECTURA. EJECUTAR ANTES DE 01_checklists_hallazgos.sql.
SELECT VERSION() AS mysql_version, DATABASE() AS base_actual;

SELECT table_name, engine, table_collation
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'activos_mantenimiento_planes',
    'activos_mantenimiento_ocurrencias',
    'activos_mantenimientos',
    'users'
  )
ORDER BY table_name;

SELECT table_name, column_name, column_type, is_nullable, column_key
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'activos_mantenimiento_planes' AND column_name = 'id')
    OR (table_name = 'activos_mantenimiento_ocurrencias' AND column_name IN ('id','plan_id','mantenimiento_id'))
    OR (table_name = 'activos_mantenimientos' AND column_name = 'id')
    OR (table_name = 'users' AND column_name = 'id')
  )
ORDER BY table_name, ordinal_position;

-- Resultado esperado por el codigo versionado:
-- activos_mantenimiento_planes.id: BIGINT UNSIGNED
-- activos_mantenimiento_ocurrencias.id/plan_id/mantenimiento_id: BIGINT UNSIGNED
-- activos_mantenimientos.id: BIGINT UNSIGNED
-- users.id: INT UNSIGNED
-- Las tres tablas de mantenimiento deben usar InnoDB.
