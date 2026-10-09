# SETUP-004 — Arranque repetible y comprobación de servicios

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

`start.ps1` rechazaba el puerto 8422 aunque el mock del proyecto ya estuviera activo. La verificación inicial del servicio tampoco era compatible con Windows PowerShell 5.1. Los logs compartidos podían truncarse al probar otro puerto web.

## Archivos cambiados

- `scripts/start.ps1`, `scripts/start.sh`: reconocen mock y web activos, arrancan solo el servicio faltante, rechazan servicios ajenos y crean logs por ejecución.
- `app/routes/web.php`: `/healthz` identifica esta aplicación sin consultar Vittles.
- `scripts/resolve-python.ps1`, `scripts/install.ps1`: selección compatible de Python 3.9+ en Windows.
- `scripts/install.cmd`, `scripts/start.cmd`: accesos Windows para abrir instalación y arranque; start abre una ventana PowerShell propia.
- `scripts/artisan.cmd`, `scripts/artisan.sh`: ejecutan cualquier comando Artisan desde la raíz; en Windows seleccionan PHP 8.2+.
- `README.md`, `docs/LOCAL_SETUP.md`, `docs/ROADMAP/LOCAL_SETUP.md`, `docs/CHANGES/README.md`: comandos y criterios actualizados.

## Verificación

- Windows PowerShell 5.1 reconoció correctamente el mock y la web ya activos; `start.ps1` terminó en 0 sin interrumpirlos.
- Con PHP 7.4 primero en `PATH`, `install.ps1 -NoPrompt` seleccionó PHP 8.2.12 y completó Composer y migraciones.
- Con el mock activo y la web libre en 8099, `start.ps1 -WebPort 8099` abrió Laravel; `/healthz` respondió HTTP 200. `Ctrl+C` cerró solo la web iniciada.
- Con una web ajena temporal en 8099, el arranque informó el conflicto y terminó en 1 sin detenerla.
- `scripts/artisan.cmd vittles:order ...` devolvió `EXISTING` con el mismo ID y total aun con PHP 7.4 primero en `PATH`.
- `scripts/artisan.cmd test`: 31 pruebas y 198 aserciones correctas.
- `start.cmd` abrió una ventana PowerShell propia; se cerró esa ventana de prueba sin tocar los servicios activos.
- Parser de los `.ps1`, sintaxis PHP y `git diff --check` sin errores.

## Pendientes

- Probar los scripts Bash en macOS/Linux; este host Windows no tiene Bash funcional.
- Probar un arranque con ambos puertos libres en otra sesión, sin reiniciar el mock activo del usuario y perder sus órdenes en memoria.
