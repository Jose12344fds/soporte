param(
    [string]$ScriptPath = 'C:\SoporteTI\exportar_eventos_windows.ps1',
    [string]$TaskName = 'SoporteTI-ExportarEventosWindows'
)

if (-not (Test-Path -LiteralPath $ScriptPath)) { throw "No se encontró el exportador: $ScriptPath" }
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument ('-NoProfile -ExecutionPolicy Bypass -File "' + $ScriptPath + '"')
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Days 3650)
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 15)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Exporta eventos importantes de Windows al Sistema de Soporte TI.' -Force
Write-Host "Tarea instalada: $TaskName"
