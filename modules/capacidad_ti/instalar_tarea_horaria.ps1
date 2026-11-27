param(
    [string]$ProjectRoot = 'C:\xampp\htdocs\SOPORTE',
    [string]$PhpPath = 'C:\xampp\php\php.exe',
    [string]$TaskName = 'SoporteTI-ImportarMedicionesCapacidad'
)

$workerPath = Join-Path $ProjectRoot 'modules\capacidad_ti\importar_logs_worker.php'
if (-not (Test-Path -LiteralPath $PhpPath)) {
    throw "No se encontró PHP: $PhpPath"
}
if (-not (Test-Path -LiteralPath $workerPath)) {
    throw "No se encontró el importador: $workerPath"
}

$action = New-ScheduledTaskAction -Execute $PhpPath -Argument ('"' + $workerPath + '"') -WorkingDirectory (Split-Path $workerPath)
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Days 3650)
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 15)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Importa cada hora los TXT de CPU, RAM, disco y red a Gestión de Capacidad TI.' -Force
Write-Host "Tarea instalada: $TaskName"
