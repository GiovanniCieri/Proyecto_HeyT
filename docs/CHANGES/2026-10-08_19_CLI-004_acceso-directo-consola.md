# CLI-004 — Acceso directo a la consola interactiva

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

Abrir el menú de terminal desde la raíz con un solo comando, sin cambiar manualmente a `app/`.

## Archivos cambiados

- `console.cmd`, `console.sh`: accesos directos para Windows y macOS/Linux; comprueban PHP y dependencias antes de invocar `vittles:console`.
- `README.md`, `docs/CONSOLE.md`, `docs/LOCAL_SETUP.md`, `app/resources/views/vittles/readme.blade.php`: comandos exactos y relación con el arranque de servicios.
- `docs/ROADMAP/LOCAL_SETUP.md`: tarea y criterio de aceptación.
- `docs/CHANGES/README.md`: orden de pasadas.

## Verificación

- `console.cmd` abrió el menú de acceso en una terminal interactiva desde la raíz; la opción `4 · Salir` terminó con código 0.
- `git diff --check` sin errores de formato.
- `console.sh` pendiente de prueba en macOS/Linux.

## Pendientes

- Validar el acceso Bash en macOS/Linux real.
