# Vittles POS — contrato corregido para el mock provisto

**Alcance:** solo el `mock_server.py` incluido en este repositorio. El comportamiento del mock se comprobó contra el servidor local y se contrastó con el código fuente. No implica que una API real de Vittles tenga el mismo contrato. `API_DOCS.md` permanece sin modificaciones.

## Autenticación

`POST /oauth/token` recibe el `client_id` y `client_secret` documentados y **no** lleva bearer previo. Devuelve `access_token`, `token_type` y **`expires: 90`** (segundos); no devuelve `expires_in: 3600`. Ante token vencido, la API responde 401 con `error: "token no longer valid"`. Evidencia: `TOKEN_TTL_SEC`, `Handler.do_POST` y `Handler._authed`.

## Locations

`GET /v1/locations` está paginado en grupos de 2. La respuesta contiene `data` y, cuando queda otra página, `next_cursor` (string). Hay que solicitar sucesivamente `?cursor=<next_cursor>` hasta que desaparezca. El mock incluye 5 sedes y una inactiva (`loc_1004`). Evidencia: `PAGE_SIZE`, `LOCATIONS`, `Handler.do_GET`.

## Menús

`GET /v1/locations/{id}/menu` devuelve **`menuItems`**, no `menu_items`. `price` puede ser número o string numérico; `available` puede ser booleano o 0/1. La sede inactiva devuelve **403** con `error: "location is not enabled for partner access"`. Un menú habilitado puede devolver 500 de forma aleatoria (~12% por intento), con `trace_id`. Evidencia: `MENUS`, rama `/menu` de `Handler.do_GET`.

## Órdenes

`POST /v1/orders` requiere el header **`X-Vittles-Location: <location_id>`** además del bearer y del `location_id` en JSON. Deben coincidir. Una orden aceptada devuelve HTTP 201, `status: "ACCEPTED"`, `id`, `total`, `client_ref`, `created_at` y `updated`. Errores de negocio como header ausente, sede incorrecta o producto agotado devuelven **HTTP 200 con `status: "REJECTED"`** y `reason`; no debe confundirse con una creación exitosa. El campo `customer` documentado no es exigido por el mock. Evidencia: rama `/v1/orders` de `Handler.do_POST`.

**`client_ref` no es idempotente en el servidor.** Dos POST con la misma referencia crean dos órdenes si ambos son aceptados. La API sí ofrece el endpoint no documentado `GET /v1/orders?client_ref=<valor>`, que devuelve `{ "data": [órdenes...] }`. Permite consultar antes de crear y conciliar después de una respuesta incierta, pero la secuencia buscar→crear no es atómica. Evidencia: diccionario `ORDERS`, rama GET de `/v1/orders` y creación incondicional en `Handler.do_POST`.

`GET /v1/orders/{id}` funciona como indica la documentación. `created_at` termina en `Z`, pero se forma con `time.localtime`; **no debe interpretarse como UTC fiable**. `updated` es un timestamp Unix en milisegundos. Evidencia: `Handler._public_order`.

## Límite de requests

El mock permite **30 requests por ventana de 60 segundos**, aplicados globalmente al proceso, no 60 por token. Al superar el límite responde HTTP 429 con header **`Retry-After-Ms: 4000`**; no envía `Retry-After` en segundos. El contador incluye también autenticación. Evidencia: `RATE_LIMIT`, `REQUESTS`, `rate_limited` y métodos GET/POST.
