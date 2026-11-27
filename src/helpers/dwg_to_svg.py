#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Convierte un archivo DXF a SVG para previsualización en el navegador.

Uso:   python dwg_to_svg.py <ruta_archivo.dxf>
Salida: SVG en stdout; errores en stderr con prefijo "ERROR:"

Instalar dependencias:
    pip install ezdxf[draw]        # SVGBackend (recomendado)
    pip install ezdxf matplotlib   # alternativa con matplotlib
"""

import sys
import os

# ── Asegurar que los paquetes del usuario están disponibles ─────────────────
# Cuando Python es invocado desde un servicio de Windows (Apache/IIS) el
# perfil del usuario no está cargado, por lo que user-site-packages no se
# incluye en sys.path.  Añadimos la ruta explícitamente usando site module
# y también un fallback con las rutas conocidas de este servidor.
import site as _site

_user_sp = _site.getusersitepackages()
if _user_sp and _user_sp not in sys.path:
    sys.path.insert(0, _user_sp)

# Fallback: rutas de paquetes conocidas en este servidor
_known_sp = [
    r'C:\Users\Jose_Sovero\AppData\Roaming\Python\Python313\site-packages',
    r'C:\python313\Lib\site-packages',
]
for _p in _known_sp:
    if os.path.isdir(_p) and _p not in sys.path:
        sys.path.insert(0, _p)
# ────────────────────────────────────────────────────────────────────────────


def main():
    if len(sys.argv) < 2:
        sys.stderr.write("ERROR:Uso: dwg_to_svg.py <filepath>\n")
        sys.exit(1)

    filepath = sys.argv[1]
    if not os.path.isfile(filepath):
        sys.stderr.write("ERROR:Archivo no encontrado: " + filepath + "\n")
        sys.exit(1)

    # --- Importar ezdxf ---
    try:
        import ezdxf
        from ezdxf.addons.drawing import RenderContext, Frontend
    except ImportError as e:
        sys.stderr.write("ERROR:ezdxf no instalado. Ejecuta: pip install ezdxf[draw]\n" + str(e) + "\n")
        sys.exit(2)

    # --- Leer el archivo DXF ---
    try:
        import ezdxf.recover as _recover
        doc, auditor = _recover.readfile(filepath)
    except Exception:
        try:
            doc = ezdxf.readfile(filepath)
        except Exception as e:
            sys.stderr.write("ERROR:No se pudo leer el archivo: " + str(e) + "\n")
            sys.exit(3)

    msp = doc.modelspace()

    # ── Configuración: fondo blanco + intercambio negro/blanco ──────────────
    # BackgroundPolicy.WHITE  → ezdxf usa fondo blanco y ajusta color 7 (blanco→negro)
    # ColorPolicy.COLOR_SWAP_BW → entidades blancas se convierten a negras y viceversa
    # Así todos los colores AutoCAD quedan correctamente visibles sobre fondo blanco.
    try:
        from ezdxf.addons.drawing import config as _draw_cfg
        _cfg = _draw_cfg.Configuration.defaults()
        _cfg = _cfg.with_changes(
            background_policy=_draw_cfg.BackgroundPolicy.WHITE,
            color_policy=_draw_cfg.ColorPolicy.COLOR_SWAP_BW,
        )
    except Exception:
        _cfg = None

    ctx = RenderContext(doc)

    # --- Intentar SVGBackend nativo (ezdxf >= 1.1, sin matplotlib) ---
    try:
        from ezdxf.addons.drawing import svg as svg_mod, layout as layout_mod

        backend = svg_mod.SVGBackend()
        if _cfg is not None:
            Frontend(ctx, backend, config=_cfg).draw_layout(msp, finalize=True)
        else:
            Frontend(ctx, backend).draw_layout(msp, finalize=True)

        # ezdxf >= 1.1: get_string requiere un objeto Page
        page = layout_mod.Page(0, 0)
        svg_string = backend.get_string(page)

        # ── Post-proceso: grosores visibles con vector-effect:non-scaling-stroke ──
        # Tema dark AutoCAD: se conservan los colores neon originales (brillantes sobre fondo oscuro).
        import re as _re

        # Escalar stroke-width para que sean legibles con vector-effect:non-scaling-stroke
        #    El viewBox es ~1,000,000 unidades; stroke-width:6 = 0.007px → invisible.
        #    Con VENS los valores son en píxeles de pantalla.
        #    Dividimos por 4: stroke-width:1→0.5px, 6→1.5px, 13→3.25px
        def _scale_sw(m):
            try:
                v = float(m.group(1))
                scaled = max(0.5, round(v / 4.0, 2))
                return 'stroke-width: ' + str(scaled) + ';'
            except Exception:
                return m.group(0)
        svg_string = _re.sub(r'stroke-width:\s*([\d.]+);', _scale_sw, svg_string)

        # Inyectar vector-effect:non-scaling-stroke → strokes en píxeles de pantalla
        _vens = '<style>path,line,polyline,polygon,circle,ellipse{vector-effect:non-scaling-stroke}</style>'
        svg_string = svg_string.replace('<defs>', '<defs>' + _vens, 1)
        # ─────────────────────────────────────────────────────────────────

        sys.stdout.write(svg_string)
        return

    except Exception as svg_err:
        sys.stderr.write("SVGBackend falló (" + str(svg_err) + "), intentando matplotlib...\n")

    # --- Fallback: MatplotlibBackend ---
    try:
        import matplotlib
        matplotlib.use('Agg')
        import matplotlib.pyplot as plt
        from ezdxf.addons.drawing.matplotlib import MatplotlibBackend
        import io

        fig = plt.figure(figsize=(20, 16), facecolor='white')
        ax = fig.add_axes([0, 0, 1, 1])
        ax.set_facecolor('white')

        out = MatplotlibBackend(ax)
        Frontend(ctx, out).draw_layout(msp, finalize=True)

        buf = io.BytesIO()
        fig.savefig(buf, format='svg', bbox_inches='tight', facecolor='white')
        plt.close(fig)

        sys.stdout.write(buf.getvalue().decode('utf-8'))

    except ImportError:
        sys.stderr.write("ERROR:matplotlib no instalado. Ejecuta: pip install matplotlib\n")
        sys.exit(4)
    except Exception as e:
        sys.stderr.write("ERROR:Fallo al renderizar: " + str(e) + "\n")
        sys.exit(5)


if __name__ == '__main__':
    main()
