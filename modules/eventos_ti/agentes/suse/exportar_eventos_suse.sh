#!/usr/bin/env bash
set -euo pipefail

DESTINO="${1:-/mnt/soporte/modules/eventos_ti/Log_Eventos}"
MINUTOS="${2:-70}"
HOST_NAME="$(hostname -s)"
IP_ADDR="$(hostname -I | awk '{print $1}')"
STAMP="$(date +%Y%m%d_%H%M%S)"
TEMP_FILE="$(mktemp)"
FINAL_FILE="${DESTINO}/EVENTOS_SUSE_${STAMP}_${IP_ADDR}.json"
trap 'rm -f "$TEMP_FILE"' EXIT

mkdir -p "$DESTINO"
{
  journalctl --since "-${MINUTOS} minutes" -p warning..emerg -o json --no-pager || true
  journalctl --since "-${MINUTOS} minutes" -o json --no-pager | grep -Ei 'kernel panic|out of memory|oom-killer|read-only file system|failed|segfault|reboot|shutdown' || true
} | awk '!seen[$0]++' > "$TEMP_FILE"

if [[ ! -s "$TEMP_FILE" ]]; then
  printf '{"Fuente":"SUSE","FechaEvento":"%s","CodigoEvento":"SIN_NOVEDAD","Severidad":"INFORMATIVO","Proveedor":"journalctl","HostOrigen":"%s","IPOrigen":"%s","Titulo":"Recolección sin eventos relevantes","Mensaje":"No se detectaron advertencias o errores durante el periodo consultado."}\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$HOST_NAME" "$IP_ADDR" > "$TEMP_FILE"
fi
mv "$TEMP_FILE" "$FINAL_FILE"
trap - EXIT
echo "Exportado: $FINAL_FILE"
