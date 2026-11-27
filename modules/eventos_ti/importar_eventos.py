#!/usr/bin/env python3
"""Ejecuta una revisión de la bandeja de eventos del sistema."""

from pathlib import Path
import argparse
import os
import subprocess
import sys
import time

BASE_DIR = Path(__file__).resolve().parent
WORKER = BASE_DIR / "importar_eventos_worker.php"
DEFAULT_PHP = Path(os.environ.get("EVENTOS_PHP", r"C:\xampp\php\php.exe"))

def run_once(php: Path) -> int:
    if not php.is_file():
        print(f"ERROR: no se encontró PHP en {php}", file=sys.stderr)
        return 2
    result = subprocess.run([str(php), str(WORKER)], cwd=str(BASE_DIR), text=True, encoding="utf-8", errors="replace", capture_output=True, check=False)
    if result.stdout: print(result.stdout, end="")
    if result.stderr: print(result.stderr, end="", file=sys.stderr)
    return result.returncode

def main() -> int:
    parser = argparse.ArgumentParser(description="Importador de eventos de infraestructura")
    parser.add_argument("--watch", action="store_true")
    parser.add_argument("--interval", type=int, default=3600)
    parser.add_argument("--php", type=Path, default=DEFAULT_PHP)
    args = parser.parse_args()
    if not args.watch: return run_once(args.php)
    while True:
        run_once(args.php)
        time.sleep(max(60, args.interval))

if __name__ == "__main__":
    raise SystemExit(main())
