# SETUP-002 — Progreso visible durante Composer

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

La generación de autoload optimizado puede tardar minutos sin emitir texto. En la consola parecía que el instalador se había detenido.

## Archivos cambiados

- `scripts/install.ps1`: ejecuta Composer como trabajo supervisado; muestra su salida, fase y tiempo cada ocho segundos, y conserva su código de error.
- `scripts/install.sh`: muestra un latido y tiempo transcurrido mientras Composer sigue activo.
- `docs/ROADMAP/LOCAL_SETUP.md`: registra el criterio de visibilidad.
- `docs/CHANGES/README.md`: orden de pasadas.

## Verificación

- `scripts/install.ps1 -NoPrompt` completó Composer, configuración y migraciones. Durante 151 segundos mostró fase y tiempo cada ocho segundos; avanzó de autoload a descubrimiento de paquetes y terminó correctamente.
- El instalador conservó `.env` y SQLite existentes.
- Parser de PowerShell sin errores y `git diff --check` sin errores de formato.
- Validación Bash en macOS/Linux sigue pendiente.

## Pendientes

- Verificar comportamiento del trabajo de Composer al interrumpir el instalador a mitad de la instalación.
