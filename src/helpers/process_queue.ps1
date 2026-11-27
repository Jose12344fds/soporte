$PYTHON     = 'C:\Python313\python.exe'
$BASE       = 'C:\xampp\htdocs\SOPORTE'
$PLANOS_DIR = "$BASE\uploads\FH_ING\planos"
$ODA_SCRIPT = "$BASE\src\helpers\run_oda_convert.py"
$SVG_SCRIPT = "$BASE\src\helpers\dwg_to_svg.py"
$PY_SITE    = 'C:\Users\Jose_Sovero\AppData\Roaming\Python\Python313\site-packages'

$env:PYTHONPATH = $PY_SITE

$pending = Get-ChildItem "$PLANOS_DIR\*.pending" -ErrorAction SilentlyContinue
if ($pending.Count -eq 0) { exit 0 }

Write-Host ("[" + (Get-Date).ToString('HH:mm:ss') + "] process_queue.ps1 -- " + $pending.Count + " trabajo(s)")

foreach ($job in $pending) {
    $dwgPath = (Get-Content $job.FullName -Raw -ErrorAction SilentlyContinue).Trim()

    if (-not (Test-Path $dwgPath)) {
        Write-Host "  SKIP (no existe): $dwgPath"
        Remove-Item $job.FullName -Force -ErrorAction SilentlyContinue
        continue
    }

    $base    = [System.IO.Path]::GetFileNameWithoutExtension($dwgPath)
    $dxfPath = [System.IO.Path]::Combine($PLANOS_DIR, "$base.dxf")
    $svgPath = [System.IO.Path]::Combine($PLANOS_DIR, "$base.svg")

    # Paso 1: DWG a DXF
    if (-not (Test-Path $dxfPath) -or (Get-Item $dxfPath).Length -lt 1000) {
        Write-Host ("  DWG->DXF: " + [System.IO.Path]::GetFileName($dwgPath))
        $p = Start-Process -FilePath $PYTHON `
                           -ArgumentList "`"$ODA_SCRIPT`" `"$dwgPath`" `"$dxfPath`"" `
                           -Wait -PassThru -NoNewWindow `
                           -RedirectStandardOutput "$PLANOS_DIR\_oda_stdout.tmp" `
                           -RedirectStandardError  "$PLANOS_DIR\_oda_stderr.tmp"

        if ($p.ExitCode -ne 0) {
            $err = Get-Content "$PLANOS_DIR\_oda_stderr.tmp" -Raw -ErrorAction SilentlyContinue
            Write-Host "  ERROR ODA (exit $($p.ExitCode)): $err"
            # No borrar .pending para reintentar la próxima vez
            continue
        }
        Write-Host ("  DXF OK (" + [Math]::Round((Get-Item $dxfPath).Length/1MB,1) + " MB)")
    } else {
        Write-Host "  DXF ya existe, omitiendo ODA"
    }

    # Paso 2: DXF a SVG
    Write-Host ("  DXF->SVG: " + [System.IO.Path]::GetFileName($dxfPath))
    $svgOut = & $PYTHON $SVG_SCRIPT $dxfPath 2>"$PLANOS_DIR\_svg_stderr.tmp"
    if ($LASTEXITCODE -eq 0 -and $svgOut.Length -gt 100) {
        [System.IO.File]::WriteAllText($svgPath, $svgOut, [System.Text.Encoding]::UTF8)
        Write-Host ("  SVG OK (" + [Math]::Round($svgOut.Length/1MB,1) + " MB)")
    } else {
        $err = Get-Content "$PLANOS_DIR\_svg_stderr.tmp" -Raw -ErrorAction SilentlyContinue
        Write-Host "  ERROR SVG: $err"
        continue
    }

    # Limpiar job
    Remove-Item $job.FullName -Force -ErrorAction SilentlyContinue
    Write-Host ("  OK: " + $base)
}

# Limpiar temporales
Remove-Item "$PLANOS_DIR\_oda_stdout.tmp","$PLANOS_DIR\_oda_stderr.tmp","$PLANOS_DIR\_svg_stderr.tmp" -ErrorAction SilentlyContinue
Write-Host ("[" + (Get-Date).ToString('HH:mm:ss') + "] Fin.")
