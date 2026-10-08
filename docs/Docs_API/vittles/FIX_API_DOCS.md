# Vittles POS — contrato real del mock provisto

Esta es la corrección técnica de `API_DOCS.md` para el `mock_server.py` entregado. **Solo describe ese mock local** en `http://127.0.0.1:8422`; no afirma nada sobre un POS externo. Las muestras se obtuvieron con requests directos el 2026-10-08, con el panel ADMIN y con la implementación del servidor. Los tokens se omiten.

## Diferencias con la documentación oficial

| Tema | `API_DOCS.md` afirma | Comportamiento del mock | Evidencia |
| --- | --- | --- | --- |
| Autenticación | Todas las requests llevan bearer; token válido 1 hora; `expires_in: 3600` | `POST /oauth/token` no necesita bearer y devuelve `expires: 90`, en segundos | POST directo 200; `TOKEN_TTL_SEC`, `do_POST` |
| Locations | `GET /v1/locations` trae todas | Página de 2 con `next_cursor`; hay 5 sedes, una inactiva | GET con cursor vacío, `2`, `4`: 2/2/1 |
| Menú | Campo `menu_items`; `price` decimal y `available` booleano | Campo `menuItems`; `price` también puede ser string numérico y `available` puede ser 0/1 | GET de `loc_1001` y `MENUS` |
| Sede inactiva | No indica tratamiento | `GET` del menú devuelve 403 | GET de `loc_1004` |
| Creación | Basta el JSON documentado; inválido devuelve 400 | Requiere `X-Vittles-Location`; varios errores de negocio devuelven HTTP 200 con `status: REJECTED` | POST sin header y POST con `items: []` |
| Idempotencia | Repetir `client_ref` devuelve la orden original | Cada POST aceptado crea otra orden aunque repita `client_ref` | Dos POST previos; creación incondicional en `do_POST` |
| Búsqueda de orden | No documentada | `GET /v1/orders?client_ref=...` devuelve `{ "data": [...] }` | GET directo y `do_GET` |
| Lectura de orden | `created_at` es UTC; solo muestra `id`, `status`, `created_at`, `total` | Incluye `updated` y `client_ref`; `created_at` usa hora local aunque termina en `Z` | GET de `ord_5501`; `_public_order` |
| Rate limit | 60/min por token; `Retry-After` en segundos | 30 requests por ventana de 60 s, global al proceso; `Retry-After-Ms: 4000` | `RATE_LIMIT`, `REQUESTS`, `rate_limited` |
| Errores | Tabla 400/401/404/429/500 | También 403; errores de negocio con 200 `REJECTED`; menú puede dar 500 transitorio | GET 403, POST 200; rama de menú en `do_GET` |

## Reglas generales

- Base URL: `http://127.0.0.1:8422` (`localhost` también sirve). Las respuestas del mock son JSON.
- Para los endpoints conocidos de `/v1` se envía `Authorization: Bearer <access_token>`. El endpoint de token es la excepción.
- El límite se comprueba **antes** de autenticar o despachar rutas, así que también cuenta `POST /oauth/token`. Al excederlo, la respuesta es HTTP 429, `{"error":"slow down"}`, con `Retry-After-Ms: 4000`. Este comportamiento procede del código del mock; la prueba dirigida de saturación no forma parte de las transcripciones de este documento.
- Un bearer ausente devuelve 401 `{"error":"missing token"}`; un token desconocido, 401 `{"error":"unknown token"}`. Si venció, devuelve 401 `{"error":"token no longer valid"}`. Los tres casos se comprueban en `_authed`; la expiración no se reprodujo esperando 90 segundos en esta pasada.
- Un endpoint desconocido responde 404 `{"error":"not found"}`.

## `POST /oauth/token`

**Solicitud:** `Content-Type: application/json` con `client_id` y `client_secret`. No enviar bearer. Ejemplo sin mostrar el secreto:

```json
{ "client_id": "partner-demo", "client_secret": "<valor del mock>" }
```

**Respuesta observada:** HTTP 200 con `access_token` (string), `token_type: "bearer"` y `expires: 90`. No existe `expires_in`. Credenciales incorrectas devuelven 401 `{"error":"bad credentials"}` según `do_POST`. El token vive en la memoria del proceso; un reinicio del mock invalida los anteriores.

## `GET /v1/locations`

**Solicitud:** bearer. Primer GET sin cursor; repetir con `?cursor=<next_cursor>` mientras exista ese campo.

**Respuesta observada:** HTTP 200 y `data` (array). Las tres respuestas tienen tamaños 2, 2 y 1; `next_cursor` es `"2"`, luego `"4"`, y desaparece en la última página. Cada sede incluye `id`, `name`, `timezone` y `active`. En el dataset provisto `loc_1004` tiene `active: false`. Un cursor no numérico provoca una excepción no controlada en `int(...)` del servidor; **no hay contrato de error JSON fiable para ese caso**.

## `GET /v1/locations/{location_id}/menu`

**Solicitud:** bearer y un ID de sede.

**Respuesta observada:** HTTP 200, objeto `{ "menuItems": [...] }`. Cada producto trae `id`, `name`, `price`, `available` y `category`. En los datos del mock `price` aparece tanto como número (`15.5`) como string (`"8.25"`), y `available` como booleano o 0/1. `itm_88` cuesta 15.50 en `loc_1001` y `loc_1002`, pero 16.25 en `loc_1003`; en `loc_1005` está agotado. Por eso el producto debe elegirse dentro del menú de la sede indicada.

**Errores observados:** sede desconocida → 404 `{"error":"no such location"}`; sede inactiva `loc_1004` → 403 `{"error":"location is not enabled for partner access"}`. Para sedes activas, el código introduce un 500 aleatorio en aproximadamente 12% de los intentos, con `{"error":"internal error","trace_id":"..."}`. La proporción y el campo `trace_id` provienen de `do_GET`; no se debe confundir este fallo transitorio con menú vacío.

## `POST /v1/orders`

**Solicitud aceptable:** bearer, `Content-Type: application/json`, header `X-Vittles-Location: <location_id>` y JSON con el mismo `location_id`, `client_ref` e `items`. Cada línea usa `item_id` y `quantity`. El campo `customer` mostrado en la documentación oficial **no es requerido** por el mock.

```http
POST /v1/orders
Authorization: Bearer <access_token>
X-Vittles-Location: loc_1001
Content-Type: application/json
```

```json
{ "location_id": "loc_1001", "client_ref": "ejemplo-unico", "items": [{ "item_id": "itm_88", "quantity": 2 }] }
```

**Respuesta aceptada:** HTTP 201 con `id`, `status: "ACCEPTED"`, `total`, `created_at`, `updated` y `client_ref`. Para dos unidades de `itm_88` en `loc_1001`, el total observado fue `31.0`. El total procede del menú del servidor, no de un importe enviado por el cliente.

**Rechazos:** el cuerpo es `{"status":"REJECTED","reason":"..."}` con **HTTP 200**, no 400. Se observaron directamente `missing location context` al omitir el header y `no items` con lista vacía. El código también contempla `unknown location_id`, `location context mismatch`, `item not on this menu` e `item unavailable at this location`. La sede inactiva no tiene un rechazo especial de creación: su menú está vacío, por lo que un producto termina como `item not on this menu`. No interpretar el 200 como orden creada.

**Idempotencia real:** `client_ref` se almacena en cada orden, pero el POST no consulta duplicados. Dos POST aceptados con la misma referencia producen dos IDs. No hay garantía `exactly once` del proveedor. Si una respuesta del POST se pierde, buscar por referencia antes de considerar otro envío; esa conciliación sigue siendo vulnerable a carreras entre clientes.

## `GET /v1/orders?client_ref=<valor>`

Endpoint omitido en `API_DOCS.md`. Requiere bearer; responde HTTP 200 `{ "data": [órdenes] }`. Con una referencia ausente se observó `{ "data": [] }`. Si se omite `client_ref`, el mock también devuelve array vacío; **no lista todas las órdenes**. Si hay duplicados de referencia, devuelve todos los coincidentes, porque recorre `ORDERS.values()`.

## `GET /v1/orders/{order_id}`

Requiere bearer. Una orden existente devuelve HTTP 200 con `id`, `status`, `total`, `created_at`, `updated` y `client_ref`; un ID desconocido devolvió 404 `{"error":"no such order"}`. `updated` es epoch Unix en milisegundos. `created_at` se construye con `time.localtime(...)` y se formatea con sufijo `Z`: ese sufijo sugiere UTC, pero el valor **no es UTC fiable**. Esta discrepancia se confirma en `_public_order`.

## Alcance de las pruebas

En esta pasada se ejecutaron solicitudes directas a token, las tres páginas de locations, menú activo/inactivo/desconocido, búsqueda y lectura de orden inexistente, y dos rechazos de POST. El panel ADMIN de la pasada anterior mostró creación `CREATED` de `ord_5502` y recuperación `EXISTING` por la integración con el mismo ID y total; las trazas registran un solo POST 201. Los casos de token vencido, saturación 429 y error aleatorio 500 se especifican a partir de ramas explícitas del mock; no se presentan como respuestas reproducidas en esta pasada.
