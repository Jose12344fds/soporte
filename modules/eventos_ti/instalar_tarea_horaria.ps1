param(
    [string]$ProjectRoot = 'C:\xampp\htdocs\SOPORTE',
    [string]$PhpPath = 'C:\xampp\php\php.exe',
    [string]$TaskName = 'SoporteTI-ImportarEventos'
)

$worker = Join-Path $ProjectRoot 'modules\eventos_ti\importar_eventos_worker.php'
if (-not (Test-Path -LiteralPath $PhpPath)) { throw "No se encontró PHP: $PhpPath" }
if (-not (Test-Path -LiteralPath $worker)) { throw "No se encontró el importador: $worker" }
$action = New-ScheduledTaskAction -Execute $PhpPath -Argument ('"' + $worker + '"') -WorkingDirectory (Split-Path $worker)
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Days 3650)
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 15)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Importa cada hora eventos TXT o JSON de Windows, SUSE, SAP HANA y aplicaciones.' -Force
Write-Host "Tarea instalada: $TaskName"
