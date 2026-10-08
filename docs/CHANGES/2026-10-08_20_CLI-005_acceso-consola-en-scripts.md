# CLI-005 — Acceso de consola dentro de scripts

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

Mantener juntos los accesos de instalación, arranque y consola en `scripts/`, como pidió el usuario.

## Archivos cambiados

- `scripts/console.cmd`, `scripts/console.sh`: accesos trasladados; sus rutas a `app/` se ajustaron al nuevo directorio.
- `README.md`, `docs/CONSOLE.md`, `docs/LOCAL_SETUP.md`, `docs/ROADMAP/LOCAL_SETUP.md`, `app/resources/views/vittles/readme.blade.php`: comandos actualizados.
- `docs/CHANGES/README.md`: secuencia de pasadas.

## Verificación

- `.\scripts\console.cmd` abrió el menú de acceso en una terminal interactiva desde la raíz; la opción `4 · Salir` terminó con código 0.
- Plantillas Blade compiladas y `git diff --check` sin errores de formato.
- La ejecución Bash sigue pendiente de validación en macOS/Linux.

## Pendientes

- Validar el acceso Bash en macOS/Linux real.
