# ORD-003 — Varias líneas, consultas CLI y comprobante

**Fecha:** 2026-10-08

**Archivos cambiados:** `OrderService.php`, `ConfirmedOrderStore.php`, `VittlesController.php`, comandos `ShowVittlesOrder.php` y `ListVittlesOrders.php`, migración de `items_json`, rutas, vistas Nueva orden/Resultado/Pedidos/Detalle/README, CSS, pruebas, README.md, FIX_API_DOCS.md, `docs/COMMANDS.md` y roadmaps MULTI_ITEM_WEB, ORDER_QUERY_CLI, RESULT_FLOW, ORDER_FLOW, ORDER_HISTORY, WEB_UI y WEB_README.

**Motivo:** permitir varios productos en un pedido web, consultar una orden o el historial por comando y evitar que Resultado parezca una página de estado estático. El ejercicio evaluable conserva su comando de un producto y cantidad 2.

**Cambios:** `placeMany` reutiliza validación, referencia, búsqueda, bloqueo, POST y conciliación del flujo de orden. Las líneas se validan contra el menú de una única sede; una línea inválida impide todo el POST. La referencia de varias líneas ordena IDs y cantidades para conservar idempotencia aunque cambie el orden visual. La referencia anterior de una sola línea se mantiene. La web envía varias líneas en un POST y el historial guarda su detalle. `vittles:show` consulta el POS por ID; `vittles:orders` lista únicamente las confirmaciones locales, con filtro opcional por location. Resultado se muestra una vez después del POST y deja un enlace al detalle persistente en Pedidos.

**Verificación:** `php artisan migrate --force` aplicó la nueva columna. `php artisan test`: 23 pruebas y 112 aserciones; Pint, compilación Blade y `git diff --check` correctos. Contra el mock real, un pedido de 2 `itm_88` y 1 `itm_91` creó `ord_5504` por `$39.25`; al repetir devolvió `EXISTING` con el mismo ID y total. `vittles:show ord_5504` leyó esos datos del POS y `vittles:orders loc_1001` listó ese pedido y el anterior. El comando obligatorio, ejecutado dos veces, conservó `ord_5501` y `$31.00`.

**Pendientes y límites:** la API del mock no lista todas las órdenes por location, de modo que el historial solo cubre confirmaciones vistas por esta integración. El detalle guardado no actualiza estados posteriores del POS; para lectura actual por ID se usa `vittles:show`. La comparación visual fina y accesibilidad siguen pendientes en WEB_UI.
