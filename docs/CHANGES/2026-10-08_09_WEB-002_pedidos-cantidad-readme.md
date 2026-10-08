# WEB-002 — Pedidos, cantidad web y README visible

**Fecha:** 2026-10-08

**Archivos cambiados:** `OrderService.php`, `ConfirmedOrderStore.php`, `VittlesController.php`, rutas, migración de `confirmed_orders`, vistas de Nueva orden/Resultado/Pedidos/README, layout, CSS, `DiagnosticLog.php`, pruebas, README.md, `docs/LOGGING.md` y roadmaps ORDER_FLOW, WEB_UI, ORDER_HISTORY y WEB_README.

**Motivo:** mostrar pedidos confirmados por location, permitir elegir cantidad en la demo y explicar en la propia web qué significa la lista de exclusiones deliberadas. El enunciado exige un archivo README de media página y cantidad 2 en el comando; no exige una página README ni cantidad variable.

**Cambios:** historial SQLite local, una fila por `client_ref`, con filtro por location y sin consultar Vittles para listar; guarda solo `CREATED`, `EXISTING` o `RECOVERED`. El comando conserva cantidad 2. La web admite 1–20; la cantidad y la cuenta local entran en la referencia idempotente, y la cantidad se envía en el POST. README.md se redujo y la página `/readme` separa entrega obligatoria y ampliación web. Las páginas se enlazan desde la navegación. El historial indica que no equivale a un listado completo del POS y puede contener datos de un proceso anterior del mock.

**Verificación:** `php artisan migrate --force` aplicó la tabla local; `php artisan test`: 19 pruebas, 90 aserciones. Se comprobó filtro y deduplicación sin llamada al POS, POST web con cantidad 3 y total confirmado, rechazo de cantidad 0 antes de contactar a Vittles, y rutas `/orders` y `/readme`. Dos corridas reales del comando contra el mismo mock devolvieron `EXISTING`, ID `ord_5501` y total `$31.00`; ambas registraron `order.history.saved` sin duplicar la referencia. Pint y compilación de Blade correctos.

**Pendientes:** el historial no importa órdenes anteriores ni puede descubrir órdenes creadas fuera de esta integración; el mock no ofrece listado completo por location. Comparación visual fina y accesibilidad permanecen pendientes en WEB_UI.
