@echo off
setlocal

REM Ajusta esta ruta si PHP no esta en el PATH del servidor.
set PHP_EXE=php

REM Ejecuta el envio semanal de programacion.
"%PHP_EXE%" "%~dp0enviar_programacion_mantenimiento.php"

endlocal
