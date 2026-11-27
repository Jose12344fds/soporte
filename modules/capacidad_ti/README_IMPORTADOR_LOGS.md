# Importador automático de mediciones

## Carpeta observada

`C:\xampp\htdocs\SOPORTE\modules\capacidad_ti\Log_Sistems`

Formato recomendado del nombre:

`SERVIDOR_aaaammdd_HHMMSS_IP.txt`

Ejemplos:

- `WS2025SAP_20260924_110001_192.168.4.32.txt`
- `hanabone_20260924_110001_192.168.4.33.txt`

La IP relaciona la fuente con la máquina virtual registrada en Gestión de Capacidad TI. Después de una importación correcta el archivo se renombra, por ejemplo:

`WS2025SAP_20260924_110001_192.168.4.32-LEIDO.TXT`

Los archivos `-LEIDO.TXT` no vuelven a procesarse. Si ocurre un error, el TXT conserva su nombre y el detalle se escribe en `importador_errores.log`. El mismo error no se repite más de una vez por día.

## Formato recomendado del contenido

```text
SERVIDOR|WS2025SAP
FECHA|2026-09-24 11:00:01

COMPONENTE|TOTAL|USO|LIBRE
CPU|7 NUCLEOS|13%|87%
MEMORIA RAM|56252 MB|31108 MB|25144 MB
DISCO DURO|149.8 GB|121.8 GB|28 GB
RED|1000 Mbps|50 Mbps|950 Mbps
```

También sigue aceptándose el formato anterior `CAMPO=VALOR` y el nombre `NOMBRE-VM-dd-mm-aaaa_HH-MM.TXT`.

## Comportamiento

1. Lee la fecha y los recursos CPU, RAM, disco y red.
2. Busca la VM por la IP del nombre; posteriormente recuerda también el alias `SERVIDOR`.
3. Inserta cuatro filas en `historial_capacidad`.
4. Calcula advertencia o crítico usando los umbrales configurados para la VM.
5. Abre una alerta por VM y recurso. Si ya existe una alerta abierta, la actualiza para evitar duplicados cada hora.
6. Registra la trazabilidad en `importacion_log_capacidad` y renombra el archivo con `-LEIDO`.

## Ejecución

Una revisión manual:

`python C:\xampp\htdocs\SOPORTE\modules\capacidad_ti\importar_logs.py`

La tarea `SoporteTI-ImportarMedicionesCapacidad` ejecuta el trabajador cada hora. Para reinstalarla, abra PowerShell como administrador en el servidor y ejecute:

`powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\SOPORTE\modules\capacidad_ti\instalar_tarea_horaria.ps1`
