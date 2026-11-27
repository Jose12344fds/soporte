👉 LAS ESTRUCTURAS REALES DE TUS TABLAS (tal como las tienes en SAP + tu módulo)

Sin teoría, solo estructura clara.

🧠 1. TABLA EQUIPOS (SAP – OITM + ITM13)
🔵 OITM (principal)
ItemCode                → Código del equipo (CLAVE)
ItemName                → Nombre del equipo
InventryNo             → Código de inventario
AssetSerNo             → Número de serie
U_TAMA_CategoriaSOF    → Categoría (MANTENIMIENTO)
🔵 ITM13 (atributos del equipo)
ItemCode     → Relación con OITM
AttriTxt3    → Marca
AttriTxt4    → Modelo
🧩 2. TABLA: TIPOS DE MANTENIMIENTO
@TMA_TIPOMANT

Code        → CORR / PREV / PRED
Name        → Correctivo / Preventivo / Predictivo
👨‍🔧 3. TABLA: TÉCNICOS
@TMA_TECNICOS

Code           → Código técnico
Name           → Nombre técnico
U_Area         → SISTEMAS / MANTENIMIENTO
U_Correo       → Correo
U_Activo       → Y / N
📅 4. TABLA: PROGRAMACIÓN DE MANTENIMIENTO
@TMA_PROGMANT

Code            → Código programación
Name            → Descripción

U_ItemCode      → OITM.ItemCode (equipo)
U_TipoMant      → CORR / PREV / PRED
U_FechaProg     → Fecha programada
U_Frecuencia    → Mensual / Trimestral / etc
U_Estado        → Pendiente / Ejecutado / Vencido
U_Observacion   → Comentarios
🛠️ 5. TABLA: ÓRDENES DE TRABAJO
@TMA_ORDTRABAJO

Code                → Código OT
Name                → Descripción

U_ProgMant          → @TMA_PROGMANT.Code (opcional)
U_ItemCode          → OITM.ItemCode (OBLIGATORIO)
U_TipoMant          → CORR / PREV
U_Tecnico           → @TMA_TECNICOS.Code

U_FechaInicio       → Fecha/hora inicio real
U_FechaFin          → Fecha/hora fin real

U_TrabajoRealizado  → Descripción del trabajo
U_Observacion       → Observaciones
U_Estado            → Abierto / Cerrado
🔗 6. RELACIÓN COMPLETA (MUY IMPORTANTE)
OITM (ItemCode)
     ↓
@TMA_PROGMANT (U_ItemCode)
     ↓
@TMA_ORDTRABAJO (U_ProgMant opcional)

@TMA_TECNICOS → @TMA_ORDTRABAJO (U_Tecnico)
@TMA_TIPOMANT → @TMA_PROGMANT / @TMA_ORDTRABAJO
ITM13 → OITM (atributos Marca / Modelo)
🧠 7. RESUMEN SIMPLE
🔵 EQUIPOS (SAP)
OITM
ITM13
🟡 TU SISTEMA
Tipos mantenimiento
Técnicos
Programación
Órdenes de trabajo
🚀 8. FLUJO REAL
Preventivo
OITM → Programación → Orden de trabajo → Técnico ejecuta
Correctivo
OITM → Orden de trabajo (directo)
✔ CONCLUSIÓN

Tu modelo final está así:

✔ SAP = equipos
✔ ITM13 = detalles técnicos
✔ UDT = mantenimiento completo
✔ OT = ejecución real
✔ Programación = planificación





tabla  @TAMA_ORDTRABAJO         TAMA_ORDTRABAJO     Registro     Documento

SELECT T0."DocEntry", T0."DocNum", T0."Period", T0."Instance", T0."Series", T0."Handwrtten", T0."Canceled", T0."Object", T0."LogInst", T0."UserSign", T0."Transfered", T0."Status", T0."CreateDate", T0."CreateTime", T0."UpdateDate", T0."UpdateTime", T0."DataSource", T0."RequestStatus", T0."Creator", T0."Remark", T0."U_ProgMant", T0."U_ItemCode", T0."U_TipoMant", T0."U_Tecnico", T0."U_FechaInicio", T0."U_FechaFin", T0."U_TrabajoRealizado", T0."U_Observacion" FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"  T0



tabla @TAMA_PROGMANT           TAMA_PROGMANT   Programación    Datos Maestros

SELECT T0."Code", T0."Name", T0."DocEntry", T0."Canceled", T0."Object", T0."LogInst", T0."UserSign", T0."Transfered", T0."CreateDate", T0."CreateTime", T0."UpdateDate", T0."UpdateTime", T0."DataSource", T0."U_ItemCode", T0."U_TipoMant", T0."U_FechaProg", T0."U_Frecuencia", T0."U_Estado", T0."U_Observacion" FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"  T0




tabla @TAMA_TECNICOS           TAMA_TECNICOS   Tecnicos del Mantenimeinto    Ningún Objeto

SELECT T0."Code", T0."Name", T0."U_Especialidad", T0."U_Correo", T0."U_Activo" FROM "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS"  T0

especialidad es como area. 



tabla @TAMA_TIPOMANT           TAMA_TIPOMANT      Tipo de Mantenimeinto        Ningún Objeto

SELECT T0."Code", T0."Name" FROM "SBO_TAMAING_PRODUC"."@TAMA_TIPOMANT"  T0

Code	Name	
PREV	Preventivo	
CORR	Correctivo	
PRED	Predictivo	
EMERG	Emergencia	

oitm : SELECT T0."ItemCode", T0."ItemName", T0."InventryNo", T0."AssetSerNo", T1."AttriTxt3", T1."AttriTxt4" FROM OITM T0 INNER JOIN ITM13 T1 ON T0."ItemCode" = T1."ItemCode" WHERE T0."U_TAMA_CategoriaSOF" ='MANTENIMIENTO'
 ,