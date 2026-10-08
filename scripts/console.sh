#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[[ -f "$root/app/vendor/autoload.php" ]] || { printf 'ERROR: La aplicación no está instalada. Ejecuta bash scripts/install.sh.\n' >&2; exit 1; }
command -v php >/dev/null || { printf 'ERROR: PHP no está disponible en PATH.\n' >&2; exit 1; }
cd "$root/app"
exec php artisan vittles:console "$@"
