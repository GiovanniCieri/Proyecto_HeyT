# CLI-006 — Consola visible ante fallos de arranque

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

Al abrir `scripts/console.cmd` como ventana nueva, un error cerraba la ventana sin dejar leer el mensaje. También se encontró que el comando podía repetir el menú indefinidamente si STDIN llegaba cerrado aunque Symfony lo marcara como interactivo.

## Archivos cambiados

- `scripts/console.cmd`: conserva la ventana al terminar y muestra el código de salida; mantiene visibles los errores de carpeta, instalación y PHP.
- `app/app/Console/Commands/VittlesConsole.php`: requiere una terminal real para ejecución CLI; conserva entradas simuladas de tests.
- `docs/CONSOLE.md`, `docs/LOCAL_SETUP.md`: comportamiento de apertura y cierre.
- `docs/CHANGES/README.md`: secuencia de pasadas.

## Verificación

- `scripts/console.cmd` abrió el menú en una terminal interactiva, permitió elegir `4 · Salir` y esperó una tecla antes de cerrar; código final 0.
- Sin TTY, terminó en 1, mostró un único error y el mensaje final del wrapper, sin bucle.
- `php artisan test --filter VittlesConsoleTest`: 6 pruebas y 70 aserciones correctas.
- `git diff --check` sin errores de formato.

## Pendientes

- Validar con doble clic desde el Explorador en la máquina del usuario.
