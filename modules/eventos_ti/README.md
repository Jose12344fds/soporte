# Gestión de Eventos TI

Este módulo separa tres conceptos:

- **Capacidad TI:** mediciones periódicas de CPU, RAM, disco y red.
- **Eventos TI:** hechos puntuales detectados en Windows, SUSE, SAP HANA o aplicaciones.
- **Mesa de Ayuda:** incidencias que requieren responsable, SLA, diagnóstico y solución.

## Flujo

1. Cada servidor exporta un archivo TXT o JSON a `Log_Eventos`.
2. El importador clasifica los eventos mediante reglas y evita duplicados.
3. El archivo procesado conserva su extensión y cambia a `-LEIDO.TXT` o `-LEIDO.JSON`.
4. TI revisa el evento y puede descartarlo, marcarlo revisado, vincularlo a una incidencia abierta o crear una nueva.
5. Si el mismo problema ya tiene una incidencia abierta, se agrega como una nueva ocurrencia a esa incidencia.

## Nombre de archivo

`HOST_aaaammdd_HHMMSS_IP.txt`

Ejemplos:

- `WS2025SAP_20260928_090000_192.168.4.32.txt`
- `HANABONE_20260928_090000_192.168.4.33.txt`
- `EVENTOS_WINDOWS_20260925_090000_192.168.4.32.json`
- `EVENTOS_SUSE_20260925_090000_192.168.4.33.json`

También se mantiene el nombre histórico `EVENTOS_FUENTE_...`. La IP del nombre o del contenido relaciona automáticamente el evento con la VM registrada en Capacidad TI.

## Formato TXT recomendado

Use una línea `CLAVE=valor` por dato y una línea vacía entre eventos:

```text
Fuente=WINDOWS
FechaEvento=2026-09-28 09:00:00
CodigoEvento=6008
Severidad=CRITICO
Proveedor=EventLog
HostOrigen=WS2025SAP
IPOrigen=192.168.4.32
Titulo=Apagado inesperado del servidor
Mensaje=El cierre anterior del sistema fue inesperado.
```

La carpeta `ejemplos` contiene un TXT listo para copiar y adaptar. También se aceptan arreglos JSON y JSON por líneas. Nunca coloque archivos de prueba directamente en `Log_Eventos` salvo que desee importarlos.

## Automatización

- `importar_eventos.py`: ejecución manual o modo continuo.
- `importar_eventos_worker.php`: trabajador de base de datos.
- `instalar_tarea_horaria.ps1`: instala la importación horaria en el servidor del sistema.
- `agentes/windows`: exportador para Windows Event Viewer.
- `agentes/suse`: exportador basado en `journalctl`.

Las reglas iniciales reconocen Kernel-Power 41, apagado inesperado 6008, reinicio solicitado 1074, servicio detenido 7031, kernel panic, OOM, sistema de archivos en solo lectura y servicios fallidos.

## Puesta en marcha

En el servidor web, abra PowerShell como administrador y ejecute:

`powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\SOPORTE\modules\eventos_ti\instalar_tarea_horaria.ps1`

En cada Windows que será monitoreado, copie los dos archivos de `agentes/windows` a `C:\SoporteTI` y ejecute como administrador:

`powershell -ExecutionPolicy Bypass -File C:\SoporteTI\instalar_exportador_horario.ps1`

En SUSE, copie `exportar_eventos_suse.sh` a `/opt/soporte-ti`, dele permiso de ejecución y prográmelo en `cron`. Ejemplo:

`5 * * * * /opt/soporte-ti/exportar_eventos_suse.sh /mnt/soporte/modules/eventos_ti/Log_Eventos >> /var/log/soporte-ti-eventos.log 2>&1`

La cuenta que ejecuta cada agente necesita permiso de escritura sobre la carpeta compartida. La instalación de estas tareas debe realizarse con una cuenta administradora del servidor correspondiente.
