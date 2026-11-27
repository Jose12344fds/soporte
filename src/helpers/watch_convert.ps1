# watch_convert.ps1
# FileSystemWatcher - detecta .pending nuevos y convierte DWG->SVG automaticamente
# Se instala en la carpeta Startup para ejecutarse al iniciar sesion
# NO requiere permisos de administrador

$PLANOS_UNC = '\\192.168.4.8\soporte\uploads\FH_ING\planos'
$CONVERT_PS1 = 'C:\Users\Jose_Sovero\AppData\Local\SOPORTE\run_convert.ps1'
$LOG_FILE = 'C:\Users\Jose_Sovero\AppData\Local\SOPORTE\watcher.log'

function Write-Log {
    param([string]$msg)
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') $msg"
    Add-Content -Path $LOG_FILE -Value $line -Encoding UTF8
}

Write-Log "=== Watcher iniciado. Monitoreando: $PLANOS_UNC ==="

# Watcher sobre la carpeta UNC
$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $PLANOS_UNC
$watcher.Filter = '*.pending'
$watcher.NotifyFilter = [System.IO.NotifyFilters]::FileName
$watcher.EnableRaisingEvents = $true

# Flag para evitar ejecuciones solapadas
$script:converting = $false

$action = {
    $name = $Event.SourceEventArgs.Name
    if ($script:converting) {
        # Esperar a que termine la conversion actual y no encolar otro proceso
        return
    }
    $script:converting = $true

    # Esperar 2 seg para que el archivo .pending se escriba completamente
    Start-Sleep -Seconds 2

    $logPath = 'C:\Users\Jose_Sovero\AppData\Local\SOPORTE\watcher.log'
    $convertPs1 = 'C:\Users\Jose_Sovero\AppData\Local\SOPORTE\run_convert.ps1'
    $timestamp = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
    Add-Content -Path $logPath -Value "$timestamp .pending detectado: $name -> ejecutando run_convert.ps1" -Encoding UTF8

    try {
        & "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe" -NonInteractive -ExecutionPolicy Bypass -File $convertPs1
        $ts2 = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
        Add-Content -Path $logPath -Value "$ts2 Conversion completada." -Encoding UTF8
    } catch {
        $ts2 = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
        Add-Content -Path $logPath -Value "$ts2 ERROR en conversion: $_" -Encoding UTF8
    } finally {
        $script:converting = $false
    }
}

Register-ObjectEvent $watcher Created -Action $action | Out-Null

Write-Log "Watcher activo. Esperando archivos .pending..."

# Procesar cualquier .pending que haya quedado pendiente al arrancar
$existing = Get-ChildItem "$PLANOS_UNC\*.pending" -ErrorAction SilentlyContinue
if ($existing.Count -gt 0) {
    Write-Log "Encontrados $($existing.Count) .pending al iniciar -> ejecutando conversion..."
    & "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe" -NonInteractive -ExecutionPolicy Bypass -File $CONVERT_PS1
    Write-Log "Conversion inicial completada."
}

# Mantener el script vivo indefinidamente
# Wait-Event timeout = 60s -> cada minuto hace polling de seguridad
# (FileSystemWatcher sobre UNC puede perder eventos)
while ($true) {
    Wait-Event -Timeout 60 | Out-Null

    $pendingNow = Get-ChildItem "$PLANOS_UNC\*.pending" -ErrorAction SilentlyContinue
    if ($pendingNow.Count -gt 0 -and -not $script:converting) {
        Add-Content -Path $LOG_FILE -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') [poll] $($pendingNow.Count) .pending encontrados -> convirtiendo..." -Encoding UTF8
        $script:converting = $true
        try {
            & 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe' -NonInteractive -ExecutionPolicy Bypass -File $CONVERT_PS1
            Add-Content -Path $LOG_FILE -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') [poll] Conversion completada." -Encoding UTF8
        } catch {
            Add-Content -Path $LOG_FILE -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') [poll] ERROR: $_" -Encoding UTF8
        } finally {
            $script:converting = $false
        }
    } else {
        Add-Content -Path $LOG_FILE -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') [alive]" -Encoding UTF8
    }
}
