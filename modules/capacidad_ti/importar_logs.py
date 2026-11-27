#!/usr/bin/env python3
"""Ejecuta el importador de logs una vez o lo mantiene revisando la carpeta cada hora."""

from __future__ import annotations

import argparse
import os
from pathlib import Path
import subprocess
import sys
import time


BASE_DIR = Path(__file__).resolve().parent
WORKER = BASE_DIR / "importar_logs_worker.php"
DEFAULT_PHP = Path(os.environ.get("CAPACIDAD_PHP", r"C:\xampp\php\php.exe"))


def run_once(php_executable: Path) -> int:
    if not php_executable.is_file():
        print(f"ERROR: no se encontró PHP en {php_executable}", file=sys.stderr)
        print("Configure CAPACIDAD_PHP con la ruta correcta de php.exe.", file=sys.stderr)
        return 2
    result = subprocess.run(
        [str(php_executable), str(WORKER)],
        cwd=str(BASE_DIR),
        text=True,
        encoding="utf-8",
        errors="replace",
        capture_output=True,
        check=False,
    )
    if result.stdout:
        print(result.stdout, end="")
    if result.stderr:
        print(result.stderr, end="", file=sys.stderr)
    return result.returncode


def main() -> int:
    parser = argparse.ArgumentParser(description="Importador automático de mediciones de Capacidad TI")
    parser.add_argument("--watch", action="store_true", help="mantenerse ejecutando y revisar periódicamente")
    parser.add_argument("--interval", type=int, default=3600, help="segundos entre revisiones (predeterminado: 3600)")
    parser.add_argument("--php", type=Path, default=DEFAULT_PHP, help="ruta de php.exe")
    args = parser.parse_args()
    if args.interval < 60:
        parser.error("el intervalo mínimo es 60 segundos")

    if not args.watch:
        return run_once(args.php)

    print(f"Monitoreando {BASE_DIR / 'Log_Sistems'} cada {args.interval} segundos. Ctrl+C para detener.")
    try:
        while True:
            run_once(args.php)
            time.sleep(args.interval)
    except KeyboardInterrupt:
        print("Importador detenido.")
        return 0


if __name__ == "__main__":
    raise SystemExit(main())

