# ORDER_HISTORY — Pedidos confirmados por location

**Estado:** implementado para demo local  
**Pertenece al ejercicio:** no; ampliación web solicitada  
**Dependencias:** ORDER_FLOW, migración SQLite de la demo

## Objetivo

Mostrar por sede los pedidos que esta integración confirmó, sin afirmar que son todas las órdenes del POS. El mock solo permite buscar órdenes por `client_ref`; no ofrece un listado completo por location.

## Tareas

- [x] Guardar una proyección local de `CREATED`, `EXISTING` y `RECOVERED`, una fila por `client_ref`.
- [x] Mantener la respuesta del POS como fuente de verdad; un fallo al guardar historial no cambia una orden confirmada.
- [x] Mostrar un filtro por location, cantidad, producto, ID y total final.
- [x] Excluir rechazos y resultados inciertos del listado de pedidos confirmados.
- [x] Declarar que el historial local puede sobrevivir a un reinicio del mock.
- [x] Probar filtro, deduplicación y ausencia de llamadas al POS al leer la página.
- [x] Guardar las líneas de varios productos y ofrecer detalle persistente por referencia.

## Criterios de aceptación

La página `/orders` solo muestra confirmaciones observadas por esta integración y una segunda confirmación de la misma referencia no crea otra fila. El comando Artisan sigue pudiendo operar aunque falle el almacenamiento opcional del historial.

## Deliberadamente fuera de alcance

Sincronización completa del POS, paginación de miles de órdenes, estado posterior de cocina/pago y garantías distribuidas de unicidad.
