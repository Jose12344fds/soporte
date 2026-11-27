# Encargo para Codex: checklist, hallazgos y acciones en `modules/mantenimiento_ti/`

## Objetivo
Implementa end-to-end **checklists de mantenimiento TI** y **gestión de hallazgos, acciones inmediatas, correcciones, órdenes correctivas vinculadas, evidencias, verificación y cierre** sobre el módulo existente. **Primero inspecciona el repositorio y la BD**: NO reestructures ni reemplaces los formularios/CRUD existentes ni asumas que es PHP, VB.NET, Laravel u otro stack. Adapta al framework, convenciones de rutas, servicios, permisos, UI y driver MySQL que realmente encuentre.

## Contexto confirmado: esquema actual

- Raíz: `modules/mantenimiento_ti/`.
- `maquina(IdMaquina, Host, Tipo_Activo, EstadoMaquina, ...)`.
- `activos_mantenimiento_planes(id, codigo, IdMaquina, nombre, tipo, prioridad, frecuencia, intervalo, unidad_intervalo, fecha_inicio, primera_fecha_programada, fecha_fin, instruccion, tecnico_predeterminado, generar_automatico, activo, ultima_fecha_generada, creado_por, actualizado_por, created_at, updated_at)`. Código `MTIP-000001`.
- `activos_mantenimiento_ocurrencias(id, codigo, plan_id, mantenimiento_id, fecha_original, fecha_programada, estado, observacion, tecnico_responsable, motivo_cancelacion, creado_por, actualizado_por, created_at, updated_at)`. `UNIQUE(plan_id, fecha_original)`, `UNIQUE(mantenimiento_id)`. Código `MTIO-0000001`. Estados `PENDIENTE`, `GENERANDO_OT`, `OT_GENERADA`, `EJECUTADA`, `CANCELADA`, `VENCIDA`.
- `activos_mantenimientos(id, folio, IdMaquina, tipo, prioridad, estado, fecha_programada, fecha_ejecucion, motivo, diagnostico, trabajo_realizado, tecnico_responsable, usuario_asignado_id, usuario_asignado_nombre, creado_por, actualizado_por, foto_antes, foto_despues, ...)`. Código `MTI-AAAA-000001`. No tiene `plan_id`: **obtener plan mediante** `ocurrencias.mantenimiento_id -> ocurrencias.plan_id`.
- `activos_mantenimiento_ocurrencia_historial(ocurrencia_id, accion, fecha_anterior, fecha_nueva, estado_anterior, estado_nuevo, comentario, registrado_por, created_at)`.
- `activos_mantenimiento_detalle(mantenimiento_id, cantidad, costo_unitario, creado_por, ...)`: repuestos y servicios.
- `activos_mantenimiento_historial(mantenimiento_id, registrado_por, ...)`: auditoría OT.
- `asig_maquina(IdMaquina, id, ...)`: responsables; `users(id, ...)` usuarios.

**Cardinalidad importante:** un plan tiene N ocurrencias; una ocurrencia enlaza con 0 o 1 OT; por la restricción `UNIQUE(mantenimiento_id)` una OT está asociada con **a lo sumo una** ocurrencia (puede existir OT manual sin plan ni ocurrencia). No agregar `plan_id` a `activos_mantenimientos`, ni `ocurrencia_id` redundante en instancias de checklist.

## SQL entregado

Archivo `01_checklists_hallazgos.sql`: crea ocho tablas, sin ALTER de las existentes:
1. `activos_mantenimiento_checklist_plantillas` (`plan_id` nullable para plantillas generales).
2. `activos_mantenimiento_checklist_items`.
3. `activos_mantenimiento_checklists` (`mantenimiento_id` + `plantilla_id` únicos por OT, con snapshot).
4. `activos_mantenimiento_checklist_respuestas` (ítems y respuesta con snapshot inmutable).
5. `activos_mantenimiento_hallazgos` (originados en checklist o registrados manualmente desde OT).
6. `activos_mantenimiento_hallazgo_acciones` (varias por hallazgo, opcional `mantenimiento_correctivo_id`).
7. `activos_mantenimiento_hallazgo_evidencias` (etapa y archivo privado).
8. `activos_mantenimiento_hallazgo_historial` (auditoría de transiciones).

### Antes de migrar

1. Haz copia de seguridad; revisa `SHOW CREATE TABLE` de las 3 tablas padre, `users`, engine, collation, tipos y signo de PK, y versión MySQL. El SQL asume **INT SIGNED** para IDs, **MySQL 8.0+** e InnoDB. Ajusta los nuevos tipos de FK para coincidir *exactamente* con padres (`BIGINT`, `UNSIGNED`, etc.). No cambies padres innecesariamente.
2. No ejecutes la migración automáticamente sin autorización. Integrarla al mecanismo de migraciones del proyecto; no basta con `CREATE TABLE IF NOT EXISTS` para actualizar tablas ya creadas. Proporciona instrucciones de despliegue y reversión solo de tablas nuevas, y verifica integridad.
3. `users.id` podría tener tipo distinto: campos `*_por`, `responsable_id`, `verificado_por` son lógicos sin FK en el SQL hasta confirmar tipo y convención; agrega FK con la política correcta únicamente después de inspeccionar.
4. Los snapshots protegen la ejecución histórica; **editar una plantilla nunca debe cambiar preguntas ya asignadas**. Si cambian ítems, versionar la plantilla o impedir editar la definición activa utilizada; `version` por sí sola no sustituye una estrategia de versionado.

## Funcionalidades/UI a construir

### 1. Administración de plantillas
- Listar, buscar, crear, editar, activar/desactivar plantillas por plan; opción `plan_id IS NULL` para plantilla general usada en órdenes manuales/correctivas.
- CRUD de preguntas; tipos: `SI_NO_NA` (`SI`,`NO`,`NA`), `CUMPLE_NO_CUMPLE_NA` (`CUMPLE`,`NO_CUMPLE`,`NA`), `NUMERICO` y `TEXTO`; orden, obligatoriedad, evidencia requerida cuando falla.
- Nunca borrar físicamente ítems que ya tengan ejecuciones: desactivar o versionar.

### 2. Checklist desde orden de trabajo
- En detalle de OT, sección/pestaña **Checklists**.
- Si OT tiene ocurrencia, resolver plan con `SELECT o.plan_id FROM activos_mantenimiento_ocurrencias o WHERE o.mantenimiento_id = ?` y buscar plantillas activas `plan_id = ...`; ofrecer asignar; **no asignar automáticamente plantillas generales**, salvo decisión explícita.
- Si OT manual/correctiva, ofrecer plantillas generales (`plan_id IS NULL`), y opcionalmente plantillas específicas previa autorización/regla de negocio.
- En una **transacción**, insertar instancia de checklist y todas sus respuestas (filas vacías) copiando `nombre_snapshot`, `version_snapshot`, `descripcion_snapshot`, tipo, obligatorio, evidencia requerida, orden. Idempotencia mediante `UNIQUE(mantenimiento_id, plantilla_id)`; evitar doble asignación por clic doble/concurrencia.
- Mostrar controles por tipo, notas, guardar parcial, completar; validar valores exactos, numéricos con cultura/decimal correcto y obligatoriedad; `NA` requiere justificación cuando corresponda.
- Para respuesta negativa (`NO` o `NO_CUMPLE`), ofrecer **Registrar hallazgo**, exigir hallazgo o justificación documentada antes de completar. Se puede tener más de un hallazgo por respuesta si se detectan problemas distintos.
- Bloquear edición de checklist `COMPLETADO` salvo proceso de reapertura explícito, autorizado y auditado.

### 3. Registrar hallazgo
- Desde respuesta o manual desde OT: componente, título, descripción, criticidad (`BAJA`, `MEDIA`, `ALTA`, `CRITICA`), requiere_parada, creador/detector, evidencias.
- Validación de pertenencia: `hallazgo.mantenimiento_id` **debe ser** la OT del checklist cuya respuesta se vincula (join respuestas -> checklist); hacerlo de forma transaccional y del lado servidor.
- Código único post-insert con ID (`HAL-TI-000001` es sugerencia; ajustar a estándares existentes). Nunca calcular con `MAX(id)+1`.
- Si riesgo alto/crítico, permitir registrar medida de control inmediata y prohibir mostrar "apto para operar" sin verificación y reglas de seguridad apropiadas.

### 4. Acciones
- Varias por hallazgo; tipos `ACCION_INMEDIATA` (contener/aislar), `CORRECCION` (resolver defecto), `ACCION_CORRECTIVA` (eliminar causa de recurrencia).
- Responsable (users), vencimiento, estado y evidencia; no confundir fecha compromiso con fecha ejecución.
- Si requiere trabajo adicional, habilitar **Crear OT correctiva** usando **servicio de creación de OT ya existente**, respetando folio `MTI-AAAA-000001`, permisos, historial y reglas de negocio; guardar el nuevo `activos_mantenimientos.id` en `mantenimiento_correctivo_id`. **No crear directo por SQL una OT incompleta.**
- Validar que OT correctiva pertenezca al mismo `IdMaquina` que el hallazgo y que no se haga autoreferencia circular peligrosa.

### 5. Evidencias
- Archivos/imagenes asociados a hallazgo o acción con etapa `DETECCION`, `EJECUCION`, `VERIFICACION`.
- Validar `accion_id` corresponde a `hallazgo_id` (doble FK independiente no lo garantiza). Controlar extensión real, MIME, tamaño, permisos, rutas seguras y acceso autenticado. Almacenar la ruta relativa, **no** archivos binarios en SQL ni rutas arbitrarias suministradas por el cliente.

### 6. Seguimiento, verificación y cierre
- Vista filtro por estado/criticidad/equipo/OT/responsable/vencimiento con indicadores de abiertos, vencidos, en atención, pendientes verificación y cerrados.
- Estados hallazgo: `ABIERTO` -> `EN_ATENCION` -> `PENDIENTE_VERIFICACION` -> `CERRADO`; `ANULADO` sólo con permiso y motivo. Registrar transiciones en `activos_mantenimiento_hallazgo_historial`, guardando antes/después, actor, fecha y comentario.
- Bloquear cierre con acciones obligatorias no ejecutadas o sin verificación; verificador autorizado y preferentemente distinto del ejecutor. Guardar resultado y evidencia.
- Una OT preventiva puede cerrarse con hallazgo pendiente **sólo si** reglas de la empresa y seguridad lo permiten; el hallazgo sigue abierto. No cerrar hallazgos al cerrar OT.

### 7. Reportes
- PDF o impresión de checklist por OT, informe de hallazgo, levantamiento y lista de pendientes. Reusar herramientas del proyecto, no integrar nueva librería sin necesidad.

## Consultas base

Plan de una OT programada:
```sql
SELECT o.id AS ocurrencia_id, o.plan_id, p.codigo, p.nombre
FROM activos_mantenimiento_ocurrencias o
JOIN activos_mantenimiento_planes p ON p.id=o.plan_id
WHERE o.mantenimiento_id=@mantenimiento_id;
```

Checklist y respuestas de una OT:
```sql
SELECT c.id AS checklist_id,c.estado,r.id AS respuesta_id,
       r.descripcion_snapshot,r.tipo_respuesta_snapshot,
       r.obligatorio_snapshot,r.respuesta_valor,r.observacion
FROM activos_mantenimiento_checklists c
JOIN activos_mantenimiento_checklist_respuestas r ON r.checklist_id=c.id
WHERE c.mantenimiento_id=@mantenimiento_id
ORDER BY c.id,r.orden_snapshot,r.id;
```

Hallazgos y acciones pendientes:
```sql
SELECT h.id,h.codigo,h.mantenimiento_id,h.titulo,h.criticidad,h.estado,
       m.folio,q.Host,COUNT(a.id) total_acciones,
       SUM(CASE WHEN a.estado IN ('PENDIENTE','EN_PROCESO') THEN 1 ELSE 0 END) acciones_pendientes
FROM activos_mantenimiento_hallazgos h
JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id
JOIN maquina q ON q.IdMaquina=m.IdMaquina
LEFT JOIN activos_mantenimiento_hallazgo_acciones a ON a.hallazgo_id=h.id
GROUP BY h.id,h.codigo,h.mantenimiento_id,h.titulo,h.criticidad,h.estado,m.folio,q.Host
ORDER BY h.id DESC;
```

## Integración y restricciones críticas

- Seguir convención de seguridad: consultas preparadas, protección CSRF si es web, autenticación, RBAC, validación backend, transacciones, manejo de errores.
- Si se guarda `respuesta_valor` como TEXT, aplicar validación de dominio y número en el servidor; no interpretar `NO` como `NO_CUMPLE` sin el tipo asociado.
- El servidor es fuente de verdad para `mantenimiento_id`, `plan_id`, `item_id` y asociaciones; nunca confiar en IDs del cliente.
- Respetar sistema de `users`, `asig_maquina`, `activos_mantenimiento_historial` y flujos existentes de OTs y ocurrencias.
- Auditoría por eventos y transiciones, sin alterar historial previo. Hacer operaciones de alta de instancia y sus ítems en una sola transacción; creación de hallazgo más acción inmediata también en transacción.
- Revisar permisos de lectura de evidencia y protección de datos.
- Índices/constraints adicionales según consultas reales, compatibles con MySQL de producción.

## Pruebas de aceptación mínimas

1. Plan P con dos ocurrencias O1 y O2, OTs distintas: ambas reciben **copias independientes** del checklist, sin compartir respuestas.
2. OT manual sin ocurrencia: puede usar plantilla general.
3. Doble clic para asignar plantilla: no duplica checklist ni filas.
4. Cambio de pregunta de plantilla: OT anterior sigue mostrando snapshots originales.
5. Respuesta `NO_CUMPLE`: se registra hallazgo enlazado a la OT correcta; rechazo cuando respuesta pertenece a otra OT.
6. Una respuesta puede tener dos hallazgos, y un hallazgo varias acciones/evidencias.
7. Acción de una OT diferente no puede enlazarse como evidencia de otro hallazgo.
8. Correctivo generado utiliza la rutina de OTs existente, con mismo equipo y folio correcto.
9. No se puede cerrar hallazgo con acciones pendientes o sin verificación; la transición queda auditada.
10. Cerrar OT preventiva no cierra automáticamente hallazgos.
11. Usuario sin permisos no puede cerrar hallazgo ni acceder a evidencias privadas.
12. Cancelación y reintentos no dejan checklist parcialmente insertado.

## Entregables solicitados a Codex

1. Inspección inicial del proyecto: stack exacto, rutas, repositorios y dependencias detectadas, y **verificación de tipos SQL**. Informar incompatibilidades antes de ejecutar migración.
2. Migración revisada y segura (desde el SQL adjunto) y, si corresponde, script de reversión únicamente de las tablas nuevas.
3. Código de backend, vistas/formularios, endpoints/rutas, validaciones, permisos, almacenamiento de evidencia, historial y reportes que correspondan al estilo del repositorio.
4. Integración en navegación de módulo y detalle de OT.
5. Tests ejecutados y manual breve de uso con capturas o pasos de prueba. Indicar qué no pudo probarse.
6. No inventar columnas existentes, no cambiar estados de tablas viejas ni borrar código funcional; ante discrepancias, detener sólo la migración conflictiva y proponer ajuste mínimo.

## Solicitud de ejecución

**Codex: inspecciona primero `modules/mantenimiento_ti/` y los CREATE TABLE actuales; adapta `01_checklists_hallazgos.sql` al esquema físico confirmado, implementa por fases el módulo descrito, ejecuta los tests aplicables y reporta archivos cambiados, migraciones y resultados. No ejecutes cambios destructivos ni migraciones sobre producción sin aprobación.**
