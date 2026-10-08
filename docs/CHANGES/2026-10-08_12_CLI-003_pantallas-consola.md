# CLI-003 — Pantallas de la consola interactiva

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

El menú imprimía cada respuesta seguida de otro menú. Tras varias acciones, la navegación quedaba muy abajo en el historial de la terminal.

## Archivos cambiados

- `app/app/Console/Commands/VittlesConsole.php`: pantalla exclusiva en TTY, encabezado y navegación redibujados, pausa tras resultados, pantallas para los pasos del pedido y ADMIN, paginación de pedidos y trazas de seis entradas.
- `docs/CONSOLE.md`: uso y alcance de la navegación por pantallas.
- `docs/ROADMAP/INTERACTIVE_CONSOLE.md`: tarea y criterio de aceptación.
- `docs/CHANGES/README.md`: entrada cronológica.

## Verificación

- `php artisan test --filter=VittlesConsoleTest`: seis tests, 70 aserciones.
- Ejecución manual en PTY: abrir `vittles:console`, entrar a README, volver con Enter y salir. El encabezado se redibuja arriba y la terminal recupera su pantalla previa.

## Pendientes

- El mock y el alcance obligatorio del comando `vittles:order` no requieren cambios por esta mejora de presentación.
