@echo off
:: Registra la tarea SOPORTE_ConvertQueue con privilegios de administrador
:: Ejecuta este archivo UNA SOLA VEZ haciendo doble click
:: Pedira confirmacion de UAC (Control de Cuentas de Usuario)

echo Solicitando permisos de administrador...
powershell -Command "Start-Process cmd -Verb RunAs -Wait -ArgumentList '/c schtasks /Create /F /TN SOPORTE_ConvertQueue /SC MINUTE /MO 2 /TR \"C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NonInteractive -ExecutionPolicy Bypass -File C:\Users\Jose_Sovero\AppData\Local\SOPORTE\run_convert.ps1\" && echo Tarea registrada exitosamente! && timeout /t 3'"

echo.
echo Si viste "Tarea registrada exitosamente!" la automatizacion esta lista.
echo El sistema convertira DWGs automaticamente cada 2 minutos.
echo.
pause
