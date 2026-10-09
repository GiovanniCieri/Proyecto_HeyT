#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[[ -f "$root/app/vendor/autoload.php" ]] || { printf 'ERROR: La aplicación no está instalada. Ejecuta bash scripts/install.sh.\n' >&2; exit 1; }
command -v php >/dev/null || { printf 'ERROR: Falta PHP 8.2 o superior en PATH.\n' >&2; exit 1; }
php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || { printf 'ERROR: Se requiere PHP 8.2 o superior.\n' >&2; exit 1; }
cd "$root/app"
exec php artisan "$@"
