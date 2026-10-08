# SETUP-001 — Instalación y arranque conjunto

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

Preparar el entorno y levantar Vittles y Laravel sin recordar varios comandos, manteniendo una ruta clara para repetir el ejercicio.

## Archivos cambiados

- `scripts/install.ps1`, `scripts/install.sh`: verificaciones, Composer, `.env`, clave, credenciales y migraciones SQLite.
- `scripts/start.ps1`, `scripts/start.sh`: arranque de ambos servicios, comprobación de puertos y cierre de procesos propios.
- `README.md`, `docs/LOCAL_SETUP.md`: comandos exactos, opciones y prueba de repetición.
- `docs/ROADMAP/LOCAL_SETUP.md`: tareas y criterios de aceptación.
- `docs/CHANGES/README.md`: secuencia de pasadas.

## Verificación

- Sintaxis PowerShell validada mediante el parser de PowerShell.
- `scripts/install.ps1 -NoPrompt` completó Composer y migraciones sobre la instalación existente, sin reemplazar `.env` ni SQLite.
- `scripts/start.ps1 -ReuseMock -WebPort 8099` sirvió `/login` con HTTP 200; `Ctrl+C` cerró solo la web y conservó el mock existente.
- El arranque sin `-ReuseMock` rechazó correctamente el puerto 8422 ocupado.
- `git diff --check` sin errores; `bash -n` no disponible en este Windows.

## Pendientes

- Probar Bash en un entorno macOS/Linux y registrar el resultado.
