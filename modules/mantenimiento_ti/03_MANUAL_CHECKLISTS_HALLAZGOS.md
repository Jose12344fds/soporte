# Checklists y hallazgos de Mantenimiento TI

## Estado de despliegue

El codigo PHP esta integrado, pero la migracion no se ejecuta automaticamente.
Hasta instalarla, las pantallas muestran un aviso y el modulo anterior sigue funcionando.

## Archivos SQL

1. 00_preflight_checklists_hallazgos.sql: confirma tipos y motores; solo lectura.
2. 01_checklists_hallazgos.sql: crea las ocho tablas nuevas.
3. 03_verificar_checklists_hallazgos.sql: valida tablas, tipos, FK y conteos.
4. 01_checklists_hallazgos_rollback.sql: elimina solo las ocho tablas nuevas.

## Despliegue

1. Crear un respaldo completo de la base tama.
2. Ejecutar 00_preflight_checklists_hallazgos.sql y confirmar:
   - planes, ocurrencias y ordenes: BIGINT UNSIGNED.
   - usuarios: INT UNSIGNED.
3. Ejecutar 01_checklists_hallazgos.sql en una base de pruebas.
4. Ejecutar 03_verificar_checklists_hallazgos.sql.
5. Probar los casos indicados debajo.
6. Repetir en produccion durante una ventana controlada.

## Uso

### Plantillas

Entrar a mantenimiento_ti/checklist_plantillas.php.

- Una plantilla asociada a un plan solo aparece en OTs de ocurrencias de ese plan.
- Una plantilla general solo aparece en OTs manuales o correctivas.
- Cuando una plantilla ya tiene ejecuciones, sus preguntas quedan bloqueadas.
- Para cambiar preguntas historicas, crear una plantilla/version nueva.

### Checklist de una OT

Abrir el detalle de la OT y usar la seccion Checklists.

- Asignar una plantilla.
- Guardar avance sin exigir todas las respuestas.
- Completar valida obligatorias, dominios, numeros y justificaciones de NA.
- Una respuesta negativa exige hallazgo o justificacion.
- Si la pregunta exige evidencia al fallar, requiere hallazgo con evidencia.
- Al completar, el checklist queda bloqueado.

### Hallazgos

Se pueden crear desde una respuesta o manualmente desde la OT.

- Criticidad: BAJA, MEDIA, ALTA o CRITICA.
- Un hallazgo critico o que requiere parada exige medida inmediata.
- Cada hallazgo admite varias acciones y evidencias.
- Las evidencias son privadas y se sirven mediante un endpoint autenticado.
- La OT correctiva se crea con el formulario normal de OTs y queda vinculada.
- No se permite avanzar a verificacion/cierre con acciones pendientes.
- El cierre exige resultado de verificacion y queda auditado.

## Pruebas minimas

1. Crear plantilla con dos preguntas y asignarla a dos OTs del mismo plan.
2. Confirmar que cada OT tiene respuestas independientes.
3. Hacer doble clic al asignar y comprobar que no duplica.
4. Crear plantilla general y asignarla a una OT manual.
5. Guardar un avance y luego completar.
6. Responder NO_CUMPLE, crear dos hallazgos sobre la misma respuesta.
7. Crear varias acciones en un hallazgo.
8. Intentar cerrar con acciones pendientes: debe bloquearse.
9. Ejecutar acciones, subir evidencia de verificacion y cerrar.
10. Crear una OT correctiva desde el hallazgo y comprobar mismo equipo y enlace.
11. Verificar que cerrar la OT preventiva no cierre el hallazgo.
12. Intentar abrir evidencia sin sesion/permisos: debe rechazarse.

## Reversion

La reversion elimina datos nuevos. Solo con respaldo y autorizacion:

Ejecutar 01_checklists_hallazgos_rollback.sql.

No modifica planes, ocurrencias, ordenes, equipos ni historiales preexistentes.
