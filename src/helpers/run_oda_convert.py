#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Convierte un archivo DWG/DWS/DWT a DXF usando ODA File Converter.

Uso:   python run_oda_convert.py <input.dwg> <output.dxf>
Salida stdout: "OK:<bytes>"
Errores stderr: "ERROR:<mensaje>"

La flag CREATE_NO_WINDOW permite ejecutar ODA desde un servicio de Windows
(Apache/IIS) sin necesitar una sesion de escritorio activa.
"""

import sys
import os

# Asegurar user-site disponible cuando se ejecuta desde servicio Windows
import site as _site
_user_sp = _site.getusersitepackages()
if _user_sp and _user_sp not in sys.path:
    sys.path.insert(0, _user_sp)
_known_sp = [r'C:\Users\Jose_Sovero\AppData\Roaming\Python\Python313\site-packages',
             r'C:\python313\Lib\site-packages']
for _p in _known_sp:
    if os.path.isdir(_p) and _p not in sys.path:
        sys.path.insert(0, _p)

import subprocess
import shutil
import time

ODA_EXE     = r'C:\Program Files\ODA\ODAFileConverter 26.4.0\ODAFileConverter.exe'
TIMEOUT_SEC = 120

# Flag de Windows para ejecutar un proceso sin crear ventana
CREATE_NO_WINDOW = 0x08000000


def main():
    if len(sys.argv) < 3:
        sys.stderr.write("ERROR:Uso: run_oda_convert.py <input.dwg> <output.dxf>\n")
        sys.exit(1)

    dwg_path = sys.argv[1]
    dxf_dest = sys.argv[2]

    # Validaciones previas
    if not os.path.isfile(ODA_EXE):
        sys.stderr.write("ERROR:ODA File Converter no encontrado: " + ODA_EXE + "\n")
        sys.exit(1)

    if not os.path.isfile(dwg_path):
        sys.stderr.write("ERROR:Archivo DWG no encontrado: " + dwg_path + "\n")
        sys.exit(1)

    # Directorios temporales locales (ODA no funciona bien con rutas UNC)
    uid     = os.urandom(6).hex()
    tmp_in  = os.path.join(os.environ.get('TEMP', 'C:\\Temp'), 'oda_in_'  + uid)
    tmp_out = os.path.join(os.environ.get('TEMP', 'C:\\Temp'), 'oda_out_' + uid)

    os.makedirs(tmp_in,  exist_ok=True)
    os.makedirs(tmp_out, exist_ok=True)

    base = os.path.basename(dwg_path)
    try:
        shutil.copy2(dwg_path, os.path.join(tmp_in, base))
    except Exception as e:
        shutil.rmtree(tmp_in,  ignore_errors=True)
        shutil.rmtree(tmp_out, ignore_errors=True)
        sys.stderr.write("ERROR:No se pudo copiar DWG al directorio temporal: " + str(e) + "\n")
        sys.exit(1)

    # Ejecutar ODA con CREATE_NO_WINDOW (sin escritorio, desde servicio)
    try:
        r = subprocess.run(
            [ODA_EXE, tmp_in, tmp_out, 'ACAD2013', 'DXF', '0', '0'],
            capture_output=True,
            timeout=TIMEOUT_SEC,
            creationflags=CREATE_NO_WINDOW
        )
        time.sleep(0.5)   # ODA puede tardar un poco en escribir el archivo
    except subprocess.TimeoutExpired:
        shutil.rmtree(tmp_in,  ignore_errors=True)
        shutil.rmtree(tmp_out, ignore_errors=True)
        sys.stderr.write("ERROR:ODA tardo mas de " + str(TIMEOUT_SEC) + "s (timeout)\n")
        sys.exit(1)
    except Exception as e:
        shutil.rmtree(tmp_in,  ignore_errors=True)
        shutil.rmtree(tmp_out, ignore_errors=True)
        sys.stderr.write("ERROR:No se pudo ejecutar ODA: " + str(e) + "\n")
        sys.exit(1)
    finally:
        shutil.rmtree(tmp_in, ignore_errors=True)

    # Verificar DXF generado
    dxf_base = os.path.splitext(base)[0] + '.dxf'
    dxf_tmp  = os.path.join(tmp_out, dxf_base)

    if not os.path.isfile(dxf_tmp) or os.path.getsize(dxf_tmp) == 0:
        shutil.rmtree(tmp_out, ignore_errors=True)
        sys.stderr.write(
            "ERROR:ODA no genero DXF valido (exit=" + str(r.returncode) + ")\n"
        )
        sys.exit(1)

    # Copiar DXF al destino (puede ser ruta UNC)
    try:
        shutil.copy2(dxf_tmp, dxf_dest)
    except Exception as e:
        shutil.rmtree(tmp_out, ignore_errors=True)
        sys.stderr.write("ERROR:No se pudo copiar DXF al destino: " + str(e) + "\n")
        sys.exit(1)
    finally:
        shutil.rmtree(tmp_out, ignore_errors=True)

    if os.path.isfile(dxf_dest) and os.path.getsize(dxf_dest) > 0:
        sys.stdout.write("OK:" + str(os.path.getsize(dxf_dest)) + "\n")
    else:
        sys.stderr.write("ERROR:DXF no encontrado en destino despues de copiar\n")
        sys.exit(1)


if __name__ == '__main__':
    main()
