## Estructura recomendada

Esta propuesta deja tus tablas actuales asi:

- `@TAMA_PROGMANT`: planificacion origen en SAP / legado
- `@TAMA_ORDTRABAJO`: ejecucion real en SAP
- tablas locales nuevas: control de plan maestro, ocurrencias y cruce con OT

## Tablas nuevas

### `mantenimientos_planes`
Una fila por plan maestro.

Campos clave:
- `codigo_plan`: codigo interno del plan
- `sap_prog_code`: codigo SAP legado si viene de `@TAMA_PROGMANT`
- `itemcode`: equipo
- `tipomant`: PREV, CORR, PRED, EMERG
- `frecuencia`: Anual, Semestral, Trimestral, Bimensual, Mensual, Quincenal, Semanal
- `fecha_inicio`, `fecha_fin`

### `mantenimientos_calendario_periodos`
Calendario anual por tipo de frecuencia.

Ejemplos:
- semanal: 52 o 53 lineas por anio
- mensual: 12 lineas por anio
- quincenal: 24 lineas aprox por anio

### `mantenimientos_plan_ocurrencias`
Una fila por cada ejecucion esperada del plan.

Ejemplos:
- plan mensual: 12 ocurrencias en el anio
- plan semanal: 52 o 53 ocurrencias

El estado correcto sale del cruce con OT:
- `Pendiente`
- `Ejecutado`
- `Vencido`
- `Ejecutado fuera de fecha`

### `mantenimientos_ot_relacion`
Relaciona una ocurrencia con la OT real en SAP.

Campos clave:
- `ocurrencia_id`
- `ot_docentry`
- `ot_docnum`

## Flujo recomendado

1. Se crea el plan maestro en `mantenimientos_planes`.
2. Se cargan periodos del anio en `mantenimientos_calendario_periodos`.
3. Se generan ocurrencias en `mantenimientos_plan_ocurrencias`.
4. Cuando se crea una OT, se relaciona con una ocurrencia.
5. Cuando la OT se cierra, la ocurrencia queda ejecutada o ejecutada fuera de fecha.

## Archivos creados

- SQL: [05_mantenimientos_programacion_recurrente.sql](/%5C%5C192.168.4.8/soporte/sql/05_mantenimientos_programacion_recurrente.sql)
- Guia: [estructura_programacion_recurrente.md](/%5C%5C192.168.4.8/soporte/modules/mantenimientos/estructura_programacion_recurrente.md)

## Siguiente paso sugerido

1. Crear estas tablas en la base local del sistema.
2. Generar el calendario anual.
3. Migrar `@TAMA_PROGMANT` a `mantenimientos_planes`.
4. Empezar a crear OT contra `mantenimientos_plan_ocurrencias`.

