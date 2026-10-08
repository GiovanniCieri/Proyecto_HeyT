#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
app="$root/app"
web_port="${1:-8000}"
reuse_mock="${2:-}"
mock_pid=''
web_pid=''
die() { printf '\033[31mERROR: %s\033[0m\n' "$1" >&2; exit 1; }
cleanup() {
    [[ -z "$web_pid" ]] || kill "$web_pid" 2>/dev/null || true
    [[ -z "$mock_pid" ]] || kill "$mock_pid" 2>/dev/null || true
    printf '\n\033[33mServicios detenidos.\033[0m\n'
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
env_value() { sed -n "s/^$1=//p" "$app/.env" | tail -n 1 | tr -d '"' | tr -d "'"; }
port_open() { python3 - "$1" <<'PY'
import socket, sys
s = socket.socket()
s.settimeout(.25)
try: sys.exit(0 if s.connect_ex(('127.0.0.1', int(sys.argv[1]))) == 0 else 1)
finally: s.close()
PY
}
wait_port() { for _ in {1..40}; do if port_open "$1"; then return 0; fi; sleep .25; done; return 1; }

printf '\n\033[33m  heytruffle*  |  VITTLES POS\033[0m\n  Arranque local · dos servicios\n'
[[ "$web_port" =~ ^[0-9]+$ ]] && (( web_port > 0 && web_port <= 65535 && web_port != 8422 )) || die 'Puerto web inválido.'
command -v php >/dev/null || die 'Falta PHP. Ejecuta el instalador.'
command -v python3 >/dev/null || die 'Falta Python 3. Ejecuta el instalador.'
[[ -f "$app/vendor/autoload.php" && -f "$app/.env" ]] || die 'Instalación incompleta. Ejecuta bash scripts/install.sh.'
[[ -n "$(env_value APP_KEY)" ]] || die 'Falta APP_KEY. Ejecuta el instalador.'
[[ -n "$(env_value VITTLES_CLIENT_ID)" && -n "$(env_value VITTLES_CLIENT_SECRET)" ]] || die 'Configura las credenciales del mock en app/.env.'
if [[ "$reuse_mock" == '--reuse-mock' ]]; then
    port_open 8422 || die '--reuse-mock requiere un mock activo en 8422.'
else
    port_open 8422 && die 'El puerto 8422 ya está ocupado. Cierra el mock anterior o usa --reuse-mock.'
fi
port_open "$web_port" && die "El puerto $web_port ya está ocupado. Usa otro puerto como argumento."
mkdir -p "$app/storage/logs"
if [[ "$reuse_mock" == '--reuse-mock' ]]; then
    printf '\033[36m[1/2] Reutilizando mock activo en 127.0.0.1:8422.\033[0m\n'
else
    printf '\033[36m[1/2] Iniciando Vittles mock en 127.0.0.1:8422...\033[0m\n'
    # El mock imprime credenciales al iniciar; descartamos stdout para no guardarlas.
    (cd "$root/docs/Docs_API/vittles" && exec python3 mock_server.py) >/dev/null 2>"$app/storage/logs/mock-start.err.log" &
    mock_pid=$!
    wait_port 8422 || die 'Vittles no inició. Revisa app/storage/logs/mock-start.err.log.'
fi
printf '\033[36m[2/2] Iniciando Laravel...\033[0m\n'
(cd "$app/public" && exec php -S "127.0.0.1:$web_port" -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php) >"$app/storage/logs/web-start.out.log" 2>"$app/storage/logs/web-start.err.log" &
web_pid=$!
wait_port "$web_port" || die 'Laravel no inició. Revisa app/storage/logs/web-start.err.log.'
printf '\n\033[32m  Vittles: http://127.0.0.1:8422\n  Web:     http://127.0.0.1:%s\033[0m\n' "$web_port"
printf '  Logs:    app/storage/logs/\n\033[33m  Ctrl+C detiene los dos servicios iniciados aquí.\033[0m\n'
while { [[ -z "$mock_pid" ]] || kill -0 "$mock_pid" 2>/dev/null; } && kill -0 "$web_pid" 2>/dev/null; do sleep 1; done
die 'Un servicio terminó inesperadamente. Revisa los logs de arranque.'
