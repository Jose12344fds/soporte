#!/usr/bin/env python3
"""
Convierte todos los DWG existentes en uploads/FH_ING/planos/ a DXF via ODA.
Ejecutar: C:\\python313\\python.exe convertir_existentes.py
"""
import subprocess, os, shutil, time

ODA = r'C:\Program Files\ODA\ODAFileConverter 26.4.0\ODAFileConverter.exe'
UPLOAD_DIR = r'\\192.168.4.8\soporte\uploads\FH_ING\planos'

files = [f for f in os.listdir(UPLOAD_DIR)
         if f.lower().endswith(('.dwg', '.dws', '.dwt'))]

print(f"=== CONVERSIÓN MASIVA DWG → DXF ===")
print(f"Archivos a procesar: {len(files)}\n")

converted = skipped = failed = 0

for fname in files:
    dxf_base = os.path.splitext(fname)[0] + '.dxf'
    dxf_dest = os.path.join(UPLOAD_DIR, dxf_base)

    if os.path.isfile(dxf_dest) and os.path.getsize(dxf_dest) > 0:
        print(f"SKIP  {fname} → DXF ya existe")
        skipped += 1
        continue

    print(f"CONV  {fname} ... ", end='', flush=True)

    uid    = os.urandom(6).hex()
    tmpIn  = os.path.join(os.environ.get('TEMP', 'C:\\Temp'), f'oda_in_{uid}')
    tmpOut = os.path.join(os.environ.get('TEMP', 'C:\\Temp'), f'oda_out_{uid}')
    os.makedirs(tmpIn, exist_ok=True)
    os.makedirs(tmpOut, exist_ok=True)

    src = os.path.join(UPLOAD_DIR, fname)
    shutil.copy2(src, os.path.join(tmpIn, fname))

    try:
        r = subprocess.run([ODA, tmpIn, tmpOut, 'ACAD2013', 'DXF', '0', '0'],
                           capture_output=True, timeout=60)
        time.sleep(0.5)
    except subprocess.TimeoutExpired:
        print("TIMEOUT")
        failed += 1
        shutil.rmtree(tmpIn, ignore_errors=True)
        shutil.rmtree(tmpOut, ignore_errors=True)
        continue

    shutil.rmtree(tmpIn, ignore_errors=True)

    dxf_tmp = os.path.join(tmpOut, dxf_base)
    if os.path.isfile(dxf_tmp) and os.path.getsize(dxf_tmp) > 0:
        shutil.copy2(dxf_tmp, dxf_dest)
        size = os.path.getsize(dxf_dest)
        print(f"OK ({size:,} bytes)")
        converted += 1
    else:
        print(f"FALLO (exit={r.returncode})")
        failed += 1

    shutil.rmtree(tmpOut, ignore_errors=True)

print(f"\n=== RESUMEN ===")
print(f"Convertidos : {converted}")
print(f"Omitidos    : {skipped}")
print(f"Fallidos    : {failed}")
