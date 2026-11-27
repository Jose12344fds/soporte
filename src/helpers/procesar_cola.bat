@echo off
title Procesando cola de conversiones DWG...
echo.
echo =====================================================
echo  SOPORTE - Procesando archivos DWG pendientes
echo =====================================================
echo.
C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -ExecutionPolicy Bypass -File "C:\xampp\htdocs\SOPORTE\src\helpers\process_queue.ps1"
echo.
echo Proceso finalizado. Cierra esta ventana.
pause
