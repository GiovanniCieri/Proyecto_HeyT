# ORDER_QUERY_CLI — Consultas de pedidos por comando

**Estado:** implementado para demo local  
**Pertenece al ejercicio:** no; comandos adicionales solicitados  
**Dependencias:** POS_CLIENT, ORDER_HISTORY

## Objetivo

Separar creación, lectura remota por ID y listado del historial local con nombres y fuentes explícitos.

## Tareas

- [x] Conservar `vittles:order` como comando evaluable de creación o recuperación.
- [x] Añadir `vittles:show {order_id}` con GET al POS y validación de ID, estado y total.
- [x] Añadir `vittles:orders {location?}` para listar hasta 100 confirmaciones locales.
- [x] Indicar en la salida de cada comando si la fuente es el POS o el historial local.
- [x] Documentar comandos y limitación de listado en `docs/COMMANDS.md`.
- [x] Probar la consulta por ID y que el listado no haga peticiones al POS.

## Criterio de aceptación

Ninguna salida presenta el historial local como lista completa de Vittles. Un 404 en `vittles:show` se informa como error de consulta, sin intentar crear una orden.
