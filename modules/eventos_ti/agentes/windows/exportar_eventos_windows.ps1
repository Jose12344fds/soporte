param(
    [string]$Destino = '\\192.168.4.8\soporte\modules\eventos_ti\Log_Eventos',
    [int]$Minutos = 70
)

$inicio = (Get-Date).AddMinutes(-[Math]::Abs($Minutos))
$ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
    Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } |
    Sort-Object InterfaceMetric | Select-Object -First 1 -ExpandProperty IPAddress
if (-not $ip) { throw 'No se pudo determinar la IP IPv4 del servidor.' }
if (-not (Test-Path -LiteralPath $Destino)) { New-Item -ItemType Directory -Path $Destino -Force | Out-Null }

$idsImportantes = @(41, 6005, 6006, 6008, 1074, 7031, 7034)
$eventos = Get-WinEvent -FilterHashtable @{ LogName=@('System','Application'); StartTime=$inicio } -ErrorAction SilentlyContinue |
    Where-Object { $_.Level -in @(1,2,3) -or $_.Id -in $idsImportantes } |
    ForEach-Object {
        [ordered]@{
            Fuente = 'WINDOWS'
            FechaEvento = $_.TimeCreated.ToString('yyyy-MM-dd HH:mm:ss')
            CodigoEvento = [string]$_.Id
            Severidad = switch ($_.Level) { 1 {'CRITICO'} 2 {'ERROR'} 3 {'ADVERTENCIA'} default {'INFORMATIVO'} }
            Proveedor = $_.ProviderName
            HostOrigen = $env:COMPUTERNAME
            IPOrigen = $ip
            Titulo = ($_.ProviderName + ' #' + $_.Id)
            Mensaje = $_.Message
            Log = $_.LogName
            RecordId = $_.RecordId
        }
    }

if (-not $eventos) { $eventos = @() }
$nombre = 'EVENTOS_WINDOWS_{0}_{1}.json' -f (Get-Date -Format 'yyyyMMdd_HHmmss'), $ip
$ruta = Join-Path $Destino $nombre
$json = ConvertTo-Json -InputObject @($eventos) -Depth 5
[System.IO.File]::WriteAllText($ruta, $json, [System.Text.UTF8Encoding]::new($false))
Write-Host "Exportado: $ruta ($(@($eventos).Count) eventos)"
