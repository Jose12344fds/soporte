$PLANOS_UNC  = '\\192.168.4.8\soporte\uploads\FH_ING\planos'
$PYTHON      = 'C:\Python313\python.exe'
$SCRIPTS_UNC = '\\192.168.4.8\soporte\src\helpers'
$ODA_SCRIPT  = "$SCRIPTS_UNC\run_oda_convert.py"
$SVG_SCRIPT  = "$SCRIPTS_UNC\dwg_to_svg.py"
$PY_SITE     = 'C:\Users\Jose_Sovero\AppData\Roaming\Python\Python313\site-packages'
$TEMP_DIR    = 'C:\Temp\soporte_queue'

$env:PYTHONPATH = $PY_SITE

if (-not (Test-Path $TEMP_DIR)) { New-Item -ItemType Directory -Path $TEMP_DIR | Out-Null }

$pendingFiles = Get-ChildItem "$PLANOS_UNC\*.pending" -ErrorAction SilentlyContinue

# Tambien convertir DWGs sin SVG y sin pending (subidos antes del sistema de cola)
$orphanDwgs = Get-ChildItem "$PLANOS_UNC\*.dwg" -ErrorAction SilentlyContinue | Where-Object {
    $svgPath = $PLANOS_UNC + '\' + $_.BaseName + '.svg'
    $penPath = $PLANOS_UNC + '\' + $_.BaseName + '.pending'
    (-not (Test-Path $svgPath)) -and (-not (Test-Path $penPath))
}
foreach ($dwg in $orphanDwgs) {
    $tmpPending = $PLANOS_UNC + '\' + $dwg.BaseName + '.pending'
    # Crear pending temporal con ruta UNC (para que el loop lo procese)
    [System.IO.File]::WriteAllText($tmpPending, $dwg.FullName)
}

$pendingFiles = Get-ChildItem "$PLANOS_UNC\*.pending" -ErrorAction SilentlyContinue
if ($pendingFiles.Count -eq 0) {
    Write-Host "No hay archivos pendientes."
    exit 0
}

Write-Host ("=== Procesando " + $pendingFiles.Count + " archivo(s) ===")

foreach ($pf in $pendingFiles) {
    $localPath = [System.IO.File]::ReadAllText($pf.FullName).Trim()
    $base      = [System.IO.Path]::GetFileNameWithoutExtension($localPath)
    $dwgUnc    = "$PLANOS_UNC\$base.dwg"
    $dxfUnc    = "$PLANOS_UNC\$base.dxf"
    $svgUnc    = "$PLANOS_UNC\$base.svg"

    Write-Host ""
    Write-Host ("--- " + $base)

    # Determinar tipo de fuente (DWG o DXF directo)
    $extSrc   = [System.IO.Path]::GetExtension($localPath).ToLower()
    $isDxfSrc = ($extSrc -eq '.dxf')

    if ($isDxfSrc) {
        # El .pending apunta a un DXF (subido directamente o forzado por ver_plano.php)
        if (-not (Test-Path $dxfUnc)) {
            Write-Host ("  SKIP (DXF eliminado): " + $base)
            Remove-Item $pf.FullName -Force -ErrorAction SilentlyContinue
            continue
        }
    } else {
        if (-not (Test-Path $dwgUnc)) {
            # DWG no existe pero puede haber DXF ya generado
            if (-not (Test-Path $dxfUnc)) {
                Write-Host ("  SKIP (DWG eliminado): " + $base)
                Remove-Item $pf.FullName -Force -ErrorAction SilentlyContinue
                continue
            }
            Write-Host "  DWG ya no existe pero DXF presente, generando solo SVG"
            $isDxfSrc = $true
        }
    }

    # ── Paso 1: DWG -> DXF via ODA (solo si fuente es DWG) ────────────────
    if (-not $isDxfSrc -and (-not (Test-Path $dxfUnc) -or (Get-Item $dxfUnc).Length -lt 1000)) {
        Write-Host "  [1/2] Convirtiendo DWG -> DXF (ODA)..."
        $odaOut = "$TEMP_DIR\oda_out.txt"
        $odaErr = "$TEMP_DIR\oda_err.txt"

        $p = Start-Process -FilePath $PYTHON `
            -ArgumentList "`"$ODA_SCRIPT`" `"$dwgUnc`" `"$dxfUnc`"" `
            -Wait -PassThru -NoNewWindow `
            -RedirectStandardOutput $odaOut `
            -RedirectStandardError $odaErr

        if ($p.ExitCode -ne 0 -or -not (Test-Path $dxfUnc) -or (Get-Item $dxfUnc).Length -lt 1000) {
            $errTxt = if (Test-Path $odaErr) { (Get-Content $odaErr -Raw).Trim() } else { 'sin detalle' }
            Write-Host ("  ERROR ODA (exit " + $p.ExitCode + "): " + $errTxt)
            continue
        }
        $dxfMB = [Math]::Round((Get-Item $dxfUnc).Length / 1MB, 1)
        Write-Host ("  DXF generado: " + $dxfMB + " MB")
    } elseif (-not $isDxfSrc) {
        Write-Host "  [1/2] DXF ya existe, saltando ODA"
    } else {
        Write-Host "  [1/2] Fuente DXF, sin ODA necesario"
    }

    # ── Paso 2: DXF -> SVG via ezdxf ──────────────────────────────────────
    Write-Host "  [2/2] Convirtiendo DXF -> SVG (ezdxf)..."
    $svgTmp = "$TEMP_DIR\output_$base.svg"
    $svgErr = "$TEMP_DIR\svg_err.txt"

    $p2 = Start-Process -FilePath $PYTHON `
        -ArgumentList "`"$SVG_SCRIPT`" `"$dxfUnc`"" `
        -Wait -PassThru -NoNewWindow `
        -RedirectStandardOutput $svgTmp `
        -RedirectStandardError $svgErr

    if ($p2.ExitCode -ne 0 -or -not (Test-Path $svgTmp) -or (Get-Item $svgTmp).Length -lt 1000) {
        $errTxt = if (Test-Path $svgErr) { (Get-Content $svgErr -Raw).Trim() } else { 'sin detalle' }
        Write-Host ("  ERROR SVG (exit " + $p2.ExitCode + "): " + $errTxt)
        continue
    }

    Copy-Item $svgTmp $svgUnc -Force
    $svgMB = [Math]::Round((Get-Item $svgUnc).Length / 1MB, 1)
    Write-Host ("  SVG generado: " + $svgMB + " MB")

    Remove-Item $pf.FullName -Force -ErrorAction SilentlyContinue
    Write-Host "  OK: $base"
}

Write-Host ""
Write-Host "=== Completado. ==="
