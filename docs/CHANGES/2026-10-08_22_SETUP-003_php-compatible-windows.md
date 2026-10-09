# SETUP-003 — PHP compatible en los scripts Windows

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

La consola abierta desde una ventana CMD seleccionó PHP 7.4.33 por orden de `PATH` y Composer rechazó Laravel, que requiere PHP 8.2+. El mismo equipo dispone de versiones compatibles.

## Archivos cambiados

- `scripts/resolve-php.ps1`: comprueba los ejecutables `php.exe` de `PATH` y devuelve el primero con versión 8.2+.
- `scripts/console.cmd`: invoca Artisan con esa ruta explícita y muestra un error breve si falta.
- `scripts/install.ps1`: usa el PHP elegido y lo antepone en el `PATH` temporal del trabajo de Composer.
- `scripts/start.ps1`: inicia Laravel con el PHP elegido.
- `README.md`, `docs/CONSOLE.md`, `docs/LOCAL_SETUP.md`, `docs/ROADMAP/LOCAL_SETUP.md`: explican el requisito y la selección.
- `docs/CHANGES/README.md`: secuencia de pasadas.

## Verificación

- Con PHP 7.4 puesto primero en `PATH`, el selector eligió PHP 8.2.12.
- En la misma condición, `scripts/console.cmd` abrió el menú y salió correctamente.
- Un trabajo de PowerShell heredó PHP 8.2.12 después de anteponer su ruta, como hará Composer.
- `scripts/start.ps1 -ReuseMock -WebPort 8099` sirvió `/login` con HTTP 200 bajo ese `PATH` y cerró la web sin detener el mock ajeno.
- Parser de PowerShell sin errores; `git diff --check` sin errores de formato.

## Pendientes

- Ejecutar el instalador completo desde CMD/Explorador en el entorno del usuario con `PATH` antiguo.
