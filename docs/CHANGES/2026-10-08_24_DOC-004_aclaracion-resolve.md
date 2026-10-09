# DOC-004 — Función de los scripts resolve en README

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

El README mencionaba la selección de PHP compatible, pero no explicaba para qué sirven `resolve-php.ps1` y `resolve-python.ps1` ni si deben ejecutarse manualmente.

## Archivos cambiados

- `README.md`: explica la versión que busca cada selector, quién lo utiliza y que no instala programas ni cambia el `PATH` global.
- `docs/ROADMAP/LOCAL_SETUP.md`: marca la aclaración como completada.
- `docs/CHANGES/README.md`: registra el orden de esta pasada.

## Verificación

- Revisión de que las descripciones coinciden con ambos scripts y sus llamadas desde instalación, arranque, consola y Artisan.
- `git diff --check` sin errores de formato.

## Pendientes

- Validar los scripts Bash en macOS/Linux, según `docs/ROADMAP/LOCAL_SETUP.md`.
