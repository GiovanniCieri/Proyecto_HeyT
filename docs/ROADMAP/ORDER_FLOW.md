# ORDER_FLOW — Selección, creación e idempotencia

**Estado:** implementado; límite distribuido documentado  
**Pertenece al ejercicio:** sí  
**Dependencia:** POS_CLIENT y contrato de órdenes verificado en API_FIX

## Objetivo

Recibir location e ítem por parámetro, validar el producto en el menú de esa sede y crear como máximo una orden de cantidad 2. Una segunda ejecución con la misma intención debe reutilizar la orden existente.

## Tareas

- [x] Definir argumentos location e item y `--request-key` estable con valor `demo`.
- [x] Validar que la location indicada exista entre todas las recuperadas.
- [x] Resolver el ítem por nombre exacto dentro del menú de esa location; no seleccionar coincidencias ambiguas.
- [x] Verificar disponibilidad y no enviar POST si el menú está inaccesible o el ítem no puede comprarse.
- [x] Generar client_ref estable para la misma intención de compra.
- [x] Consultar GET /v1/orders?client_ref antes de crear; tratar cero, una, varias o error de búsqueda como casos distintos.
- [x] Crear la orden con location_id, client_ref, cantidad 2 y X-Vittles-Location correcto.
- [x] Interpretar el cuerpo de la respuesta: 200 con REJECTED no es éxito.
- [x] Tras una respuesta incierta del POST, conciliar por client_ref antes de decidir; nunca reenviar a ciegas.
- [x] Imprimir CREATED, EXISTING, RECOVERED, REJECTED o UNKNOWN con ID y total cuando corresponda.

## Criterios de aceptación

- Con location loc_1001 e ítem Buffalo Wings (12), la primera corrida crea una orden; la segunda informa el mismo ID y total sin otro POST creador.
- Ninguna corrida crea órdenes en otras locations aunque haya leído sus menús.
- Una sede inactiva, un producto ausente y uno no disponible producen un resultado claro y ninguna orden.
- Los límites de esta estrategia ante procesos concurrentes y reinicio del mock quedan documentados.
