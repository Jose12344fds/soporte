# Mesa de Ayuda TI

Modulo para gestionar incidentes y solicitudes de servicio con trazabilidad, prioridad por impacto/urgencia, objetivos SLA, evidencias, resolucion, cierre y reportes.

## Paginas

- `index.php`: bandeja, filtros, indicadores rapidos y registro de tickets.
- `detalle.php`: ficha del ticket, SLA, gestion, documentos y linea de tiempo.
- `configuracion.php`: categorias y politicas SLA.
- `reportes.php`: indicadores anuales, tendencias y carga operativa.
- `exportar.php`: detalle anual en CSV UTF-8 compatible con Excel.
- `archivo.php`: entrega autenticada de evidencias.
- `acciones.php`: operaciones POST protegidas por CSRF.

## Tablas

- `inc_tickets`: registro principal y fotografia historica del solicitante y responsable.
- `inc_categorias`: catalogo de clasificacion.
- `inc_sla_politicas`: objetivos de respuesta y resolucion por prioridad.
- `inc_seguimiento`: historial inmutable de comentarios y cambios.
- `inc_adjuntos`: metadatos y ruta segura de evidencias.

Las tablas se crean y se inicializan de forma idempotente desde `inc_schema()` al abrir el modulo. Los archivos se almacenan en `uploads/incidencias/AÑO/MES`; MySQL conserva sus metadatos y relaciones.

## Reglas

- Prioridad: impacto por urgencia. `1-3 = Baja`, `4-7 = Media`, `8-11 = Alta`, `12-16 = Critica`.
- SLA: minutos calendario desde el registro.
- Pausa: `PENDIENTE_USUARIO` y `PENDIENTE_TERCERO` detienen el objetivo de resolucion.
- Cierre: resolver o cerrar exige solucion y codigo de cierre.
- Auditoria: los tickets no se eliminan; se cierran o cancelan y todo cambio queda en seguimiento.
- Acceso: el solicitante ve sus tickets; personal TI o usuarios con permiso de edicion gestionan la bandeja completa.
- Configuracion: requiere permiso explicito `incidencias/editar`.

Este flujo esta alineado con practicas de Mesa de Ayuda, Gestion de Incidentes y Gestion de Niveles de Servicio. No implica certificacion ITIL del software o del proceso de la organizacion.

