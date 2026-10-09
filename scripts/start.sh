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
    if [[ -n "$web_pid" || -n "$mock_pid" ]]; then printf '\n\033[33mServicios iniciados aquí detenidos.\033[0m\n'; fi
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
mock_ready() { python3 - <<'PY'
import urllib.error, urllib.request
try:
    request = urllib.request.Request('http://127.0.0.1:8422/', method='HEAD')
    try: response = urllib.request.urlopen(request, timeout=2)
    except urllib.error.HTTPError as error: response = error
    with response: raise SystemExit(0 if response.headers.get('Server', '').startswith('vittles-pos/') else 1)
except Exception: raise SystemExit(1)
PY
}
web_ready() { python3 - "$1" <<'PY'
import json, sys, urllib.request
try:
    with urllib.request.urlopen('http://127.0.0.1:' + sys.argv[1] + '/healthz', timeout=3) as response:
        payload = json.load(response)
        raise SystemExit(0 if response.status == 200 and payload.get('service') == 'heytruffle-vittles-demo' and payload.get('status') == 'ok' else 1)
except Exception: raise SystemExit(1)
PY
}
wait_ready() {
    for _ in {1..40}; do
        if [[ "$1" == mock ]]; then mock_ready && return 0; else web_ready "$2" && return 0; fi
        sleep .25
    done
    return 1
}

printf '\n\033[33m  heytruffle*  |  VITTLES POS\033[0m\n  Arranque local · dos servicios\n'
[[ "$web_port" =~ ^[0-9]+$ ]] && (( web_port > 0 && web_port <= 65535 && web_port != 8422 )) || die 'Puerto web inválido.'
command -v php >/dev/null || die 'Falta PHP. Ejecuta el instalador.'
command -v python3 >/dev/null || die 'Falta Python 3. Ejecuta el instalador.'
php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || die 'Se requiere PHP 8.2 o superior.'
python3 -c 'import sys; sys.exit(0 if sys.version_info >= (3,9) else 1)' || die 'Se requiere Python 3.9 o superior.'
[[ -f "$app/vendor/autoload.php" && -f "$app/.env" ]] || die 'Instalación incompleta. Ejecuta bash scripts/install.sh.'
[[ -n "$(env_value APP_KEY)" ]] || die 'Falta APP_KEY. Ejecuta el instalador.'
[[ -n "$(env_value VITTLES_CLIENT_ID)" && -n "$(env_value VITTLES_CLIENT_SECRET)" ]] || die 'Configura las credenciales del mock en app/.env.'
mock_active=false
web_active=false
if port_open 8422; then mock_ready || die 'El puerto 8422 pertenece a otro servicio o Vittles no responde.'; mock_active=true; fi
if port_open "$web_port"; then web_ready "$web_port" || die "El puerto $web_port pertenece a otra web o Laravel tiene un error."; web_active=true; fi
if [[ "$reuse_mock" == '--reuse-mock' && "$mock_active" == false ]]; then die '--reuse-mock requiere un mock activo en 8422.'; fi
if [[ "$mock_active" == true && "$web_active" == true ]]; then
    printf '\033[32m  Ambos servicios ya están activos:\n  Vittles: http://127.0.0.1:8422\n  Web:     http://127.0.0.1:%s\033[0m\n' "$web_port"
    exit 0
fi
mkdir -p "$app/storage/logs"
run_id="$(date +%Y%m%d-%H%M%S)-$$"
if [[ "$mock_active" == true ]]; then
    printf '\033[36m[1/2] Vittles ya activo en 127.0.0.1:8422.\033[0m\n'
else
    printf '\033[36m[1/2] Iniciando Vittles mock en 127.0.0.1:8422...\033[0m\n'
    # El mock imprime credenciales al iniciar; descartamos stdout para no guardarlas.
    (cd "$root/docs/Docs_API/vittles" && exec python3 mock_server.py) >/dev/null 2>"$app/storage/logs/mock-start-$run_id.err.log" &
    mock_pid=$!
    wait_ready mock || die "Vittles no inició. Revisa app/storage/logs/mock-start-$run_id.err.log."
fi
if [[ "$web_active" == true ]]; then
    printf '\033[36m[2/2] Laravel ya activo en 127.0.0.1:%s.\033[0m\n' "$web_port"
else
    printf '\033[36m[2/2] Iniciando Laravel...\033[0m\n'
    (cd "$app/public" && exec php -S "127.0.0.1:$web_port" -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php) >"$app/storage/logs/web-start-$run_id.out.log" 2>"$app/storage/logs/web-start-$run_id.err.log" &
    web_pid=$!
    wait_ready web "$web_port" || die "Laravel no inició. Revisa app/storage/logs/web-start-$run_id.err.log."
fi
printf '\n\033[32m  Vittles: http://127.0.0.1:8422\n  Web:     http://127.0.0.1:%s\033[0m\n' "$web_port"
printf '  Logs:    app/storage/logs/\n\033[33m  Ctrl+C detiene los dos servicios iniciados aquí.\033[0m\n'
while { [[ -z "$mock_pid" ]] || kill -0 "$mock_pid" 2>/dev/null; } && kill -0 "$web_pid" 2>/dev/null; do sleep 1; done
die 'Un servicio terminó inesperadamente. Revisa los logs de arranque.'
