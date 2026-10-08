# DOC-003 — README alineado con la instalación y el arranque

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

Dejar el comando exacto de instalación, arranque y ejecución del ejercicio en el README de entrega, y evitar que la página README de la demo muestre el procedimiento manual anterior.

## Archivos cambiados

- `README.md`: comandos Windows y macOS/Linux, URLs, prueba de idempotencia, decisiones, exclusiones y uso de IA.
- `app/resources/views/vittles/readme.blade.php`: mismos pasos de arranque en la página de la demo.
- `docs/CHANGES/README.md`: orden de la nueva pasada.

## Verificación

- Comprobación de que el README conserva las tres piezas requeridas: comando exacto, decisiones y exclusiones, además de la declaración de IA.
- `git diff --check` sin errores de formato.

## Pendientes

- Validar los scripts Bash en macOS/Linux según `docs/ROADMAP/LOCAL_SETUP.md`.
