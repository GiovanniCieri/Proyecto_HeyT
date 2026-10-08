#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
app="$root/app"
die() { printf '\033[31mERROR: %s\033[0m\n' "$1" >&2; exit 1; }
step() { printf '\n\033[36m[%s/5] %s\033[0m\n' "$1" "$2"; }
env_value() { sed -n "s/^$1=//p" "$app/.env" | tail -n 1 | tr -d '"' | tr -d "'"; }

printf '\n\033[33m  heytruffle*  |  VITTLES POS\033[0m\n  Instalación local · Laravel + mock\n'
step 1 'Comprobando herramientas'
command -v php >/dev/null || die 'Falta PHP en PATH.'
command -v composer >/dev/null || die 'Falta Composer en PATH.'
command -v python3 >/dev/null || die 'Falta Python 3.9+ en PATH.'
php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || die 'Se requiere PHP 8.2 o superior.'
python3 -c 'import sys; sys.exit(0 if sys.version_info >= (3,9) else 1)' || die 'Se requiere Python 3.9 o superior.'
printf '\033[32m  PHP, Composer y Python listos.\033[0m\n'

step 2 'Instalando dependencias PHP'
printf '  Composer instala desde composer.lock; autoload puede tardar varios minutos.\n'
composer_started=$SECONDS
last_heartbeat=0
(cd "$app" && composer install --no-interaction --prefer-dist) &
composer_pid=$!
while kill -0 "$composer_pid" 2>/dev/null; do
    sleep 1
    elapsed=$((SECONDS - composer_started))
    if kill -0 "$composer_pid" 2>/dev/null && (( elapsed - last_heartbeat >= 8 )); then
        printf '\033[33m  ... Composer sigue activo (%s s); espera la siguiente salida.\033[0m\n' "$((SECONDS - composer_started))"
        last_heartbeat=$elapsed
    fi
done
wait "$composer_pid" || die 'composer install falló. Revisa las líneas anteriores.'
printf '\033[32m  Dependencias listas en %s s.\033[0m\n' "$((SECONDS - composer_started))"

step 3 'Preparando configuración'
if [[ ! -f "$app/.env" ]]; then cp "$app/.env.example" "$app/.env"; printf '  .env creado desde el ejemplo.\n';
else printf '  .env existente conservado.\n'; fi
if [[ -z "$(env_value APP_KEY)" ]]; then (cd "$app" && php artisan key:generate --no-interaction) || die 'No se pudo generar APP_KEY.'; fi

step 4 'Configurando acceso al mock'
if [[ -z "$(env_value VITTLES_CLIENT_ID)" || -z "$(env_value VITTLES_CLIENT_SECRET)" ]]; then
    if [[ "${1:-}" == '--no-prompt' || ! -t 0 ]]; then
        printf '\033[33m  Completa VITTLES_CLIENT_ID y VITTLES_CLIENT_SECRET en app/.env.\033[0m\n'
    else
        printf '  Usa las credenciales del README original del mock. La clave no se muestra.\n'
        read -r -p '  Client ID: ' client_id
        read -r -s -p '  Client secret: ' client_secret
        printf '\n'
        if [[ -n "$client_id" && -n "$client_secret" ]]; then
            export VITTLES_SETUP_ID="$client_id" VITTLES_SETUP_SECRET="$client_secret"
            python3 - "$app/.env" <<'PY'
import os, pathlib, sys
path = pathlib.Path(sys.argv[1])
text = path.read_text(encoding='utf-8')
for name, value in [('VITTLES_CLIENT_ID', os.environ['VITTLES_SETUP_ID']), ('VITTLES_CLIENT_SECRET', os.environ['VITTLES_SETUP_SECRET'])]:
    if '\n' in value or '\r' in value:
        raise SystemExit(f'{name} contiene saltos de línea')
    entry = name + '="' + value.replace('\\', '\\\\').replace('"', '\\"') + '"'
    lines = text.splitlines()
    lines = [entry if line.startswith(name + '=') else line for line in lines]
    if not any(line.startswith(name + '=') for line in lines):
        lines.append(entry)
    text = '\n'.join(lines) + '\n'
path.write_text(text, encoding='utf-8')
PY
            unset VITTLES_SETUP_ID VITTLES_SETUP_SECRET client_id client_secret
            printf '\033[32m  Credenciales guardadas en app/.env (ignorado por Git).\033[0m\n'
        fi
    fi
else printf '  Credenciales existentes conservadas.\n'; fi

step 5 'Preparando SQLite sin borrar datos'
if [[ ! -f "$app/database/database.sqlite" ]]; then touch "$app/database/database.sqlite"; printf '  Base SQLite creada.\n'; fi
(cd "$app" && php artisan migrate --force) || die 'Las migraciones fallaron.'
printf '\n\033[32mLISTO. Arranca ambos servicios con: bash scripts/start.sh\033[0m\n'
