"""
generar_svgs.py -- Genera SVG para cada DXF en uploads/FH_ING/planos/
Ejecutar desde CLI como Jose_Sovero (no desde Apache):
    C:\\Python313\\python.exe src\\helpers\\generar_svgs.py

Agrega los site-packages del usuario antes de buscar ezdxf por si acaso
el interprete se ejecuta sin perfil (aunque desde CLI esto no suele pasar).
"""
import os
import sys
import subprocess

# ── ezdxf path fix ──────────────────────────────────────────────────────────
import site as _site
_user_sp = _site.getusersitepackages()
if _user_sp and _user_sp not in sys.path:
    sys.path.insert(0, _user_sp)
for _p in [
    r'C:\Users\Jose_Sovero\AppData\Roaming\Python\Python313\site-packages',
    r'C:\Python313\Lib\site-packages',
]:
    if os.path.isdir(_p) and _p not in sys.path:
        sys.path.insert(0, _p)

SCRIPT_DIR  = os.path.dirname(os.path.abspath(__file__))
BASE_DIR    = os.path.dirname(os.path.dirname(SCRIPT_DIR))   # …/soporte
UPLOADS_DIR = os.path.join(BASE_DIR, 'uploads', 'FH_ING', 'planos')
CONVERTER   = os.path.join(SCRIPT_DIR, 'dwg_to_svg.py')
PYTHON      = sys.executable

if not os.path.isdir(UPLOADS_DIR):
    print(f"ERROR: uploads dir no existe: {UPLOADS_DIR}")
    sys.exit(1)

if not os.path.isfile(CONVERTER):
    print(f"ERROR: dwg_to_svg.py no encontrado: {CONVERTER}")
    sys.exit(1)

dxf_files = sorted(
    f for f in os.listdir(UPLOADS_DIR)
    if f.lower().endswith('.dxf')
)
print(f"=== GENERACIÓN SVG ===")
print(f"Directorio : {UPLOADS_DIR}")
print(f"DXF encontrados: {len(dxf_files)}\n")

ok = skip = fail = 0

for dxf in dxf_files:
    dxf_path = os.path.join(UPLOADS_DIR, dxf)
    svg_name = os.path.splitext(dxf)[0] + '.svg'
    svg_path = os.path.join(UPLOADS_DIR, svg_name)

    if os.path.isfile(svg_path) and os.path.getsize(svg_path) > 100:
        print(f"SKIP  {dxf}  →  SVG ya existe ({os.path.getsize(svg_path):,} bytes)")
        skip += 1
        continue

    print(f"CONV  {dxf}  ... ", end='', flush=True)
    try:
        env = dict(os.environ)
        env['PYTHONPATH'] = r'C:\Users\Jose_Sovero\AppData\Roaming\Python\Python313\site-packages'
        result = subprocess.run(
            [PYTHON, CONVERTER, dxf_path],
            capture_output=True, text=True, timeout=360, env=env
        )
        if result.returncode == 0 and '<svg' in result.stdout:
            with open(svg_path, 'w', encoding='utf-8') as f:
                f.write(result.stdout)
            size = os.path.getsize(svg_path)
            print(f"OK ({size:,} bytes)")
            ok += 1
        else:
            err = (result.stderr or result.stdout or 'sin salida SVG').strip()
            # Extraer línea ERROR: si existe
            for line in err.splitlines():
                if 'ERROR:' in line:
                    err = line.replace('ERROR:', '').strip()
                    break
            print(f"ERROR: {err[:200]}")
            fail += 1
    except subprocess.TimeoutExpired:
        print("ERROR: Timeout (>6 min)")
        fail += 1
    except Exception as exc:
        print(f"ERROR: {exc}")
        fail += 1

print(f"\n=== RESUMEN ===")
print(f"Generados  : {ok}")
print(f"Omitidos   : {skip}")
print(f"Fallidos   : {fail}")
