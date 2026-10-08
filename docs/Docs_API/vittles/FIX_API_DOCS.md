# Vittles POS — contrato real del mock provisto

Esta es la corrección técnica de `API_DOCS.md` para el `mock_server.py` entregado. **Solo describe ese mock local** en `http://127.0.0.1:8422`; no afirma nada sobre un POS externo. Las muestras se obtuvieron con requests directos el 2026-10-08 y con las trazas redactadas del panel ADMIN. Cada apartado distingue **observado** (respuesta del servidor) de **derivado** (rama del código del mock sin reproducción dirigida). Los tokens y el secreto se omiten.

## Diferencias con la documentación oficial

| Tema | `API_DOCS.md` afirma | Comportamiento del mock | Evidencia |
| --- | --- | --- | --- |
| Autenticación | Todas las requests llevan bearer; token válido 1 hora; `expires_in: 3600` | `POST /oauth/token` no necesita bearer y devuelve `expires: 90`, en segundos | POST directo 200; `TOKEN_TTL_SEC`, `do_POST` |
| Locations | `GET /v1/locations` trae todas | Página de 2 con `next_cursor`; hay 5 sedes, una inactiva | GET con cursor vacío, `2`, `4`: 2/2/1 |
| Menú | Campo `menu_items`; `price` decimal y `available` booleano | Campo `menuItems`; `price` también puede ser string numérico y `available` puede ser 0/1 | GET de `loc_1001` y `MENUS` |
| Sede inactiva | No indica tratamiento | `GET` del menú devuelve 403 | GET de `loc_1004` |
| Creación | Basta el JSON documentado; inválido devuelve 400 | Requiere `X-Vittles-Location`; varios errores de negocio devuelven HTTP 200 con `status: REJECTED` | POST sin header y POST con `items: []` |
| Idempotencia | Repetir `client_ref` devuelve la orden original | Cada POST aceptado crea otra orden aunque repita `client_ref`; incluso lo acepta ausente | Dos POST previos; creación incondicional en `do_POST` |
| Búsqueda de orden | No documentada | `GET /v1/orders?client_ref=...` devuelve `{ "data": [...] }` | GET directo y `do_GET` |
| Lectura de orden | `created_at` es UTC; solo muestra `id`, `status`, `created_at`, `total` | Incluye `updated` y `client_ref`; `created_at` usa hora local aunque termina en `Z` | GET de `ord_5501`; `_public_order` |
| Rate limit | 60/min por token; `Retry-After` en segundos | 30 requests por ventana de 60 s, global al proceso; `Retry-After-Ms: 4000` | `RATE_LIMIT`, `REQUESTS`, `rate_limited` |
| Errores | Tabla 400/401/404/429/500 | También 403; errores de negocio con 200 `REJECTED`; menú puede dar 500 transitorio; ciertos payloads mal formados no reciben JSON estable | GET 403, POST 200 y traza GET 500; ramas `do_GET`/`do_POST` |

## Reglas generales

- Base URL: `http://127.0.0.1:8422` (`localhost` también sirve). Las respuestas emitidas por `_send` son JSON; algunas entradas mal formadas disparan excepciones no controladas, sin contrato de respuesta HTTP/JSON.
- Para los endpoints conocidos de `/v1` se envía `Authorization: Bearer <access_token>`. El endpoint de token es la excepción.
- El límite se comprueba **antes** de autenticar o despachar rutas, así que también cuenta `POST /oauth/token` e incluso rutas inexistentes. Al excederlo, la respuesta es HTTP 429, `{"error":"slow down"}`, con `Retry-After-Ms: 4000`. El límite es una ventana móvil global de 60 segundos; esperar 4000 ms no garantiza que haya cupo. Este comportamiento procede del código del mock; la prueba dirigida de saturación no forma parte de las transcripciones de este documento.
- Un bearer ausente devuelve 401 `{"error":"missing token"}`; un token desconocido, 401 `{"error":"unknown token"}`. Si venció, devuelve 401 `{"error":"token no longer valid"}`. Los tres casos se comprueban en `_authed`; la expiración no se reprodujo esperando 90 segundos en esta pasada.
- Un endpoint desconocido responde 404 `{"error":"not found"}`.

Las ramas relevantes del archivo original son: `rate_limited` (líneas 65–72), `_body` (90–97), `_authed` (99–116), `do_GET` (119–177), `do_POST` (180–241) y `_public_order` (245–255). Esas referencias permiten distinguir un ejemplo observado de una conclusión por inspección.

### Respuestas observadas y ejemplos

Los IDs de orden son efímeros: el mock guarda las órdenes en memoria y los cambia al reiniciarse. Los fragmentos de abajo son ejemplos de la estructura, no valores fijos que deba esperar el cliente.

| Solicitud | HTTP | Cuerpo JSON representativo | Evidencia |
| --- | --- | --- | --- |
| `POST /oauth/token` | 200 | `{"access_token":"<omitido>","token_type":"bearer","expires":90}` | Request directo |
| `GET /v1/locations` | 200 | `{"data":[{"id":"loc_1001","name":"Vittles Demo - Midtown","timezone":"America/New_York","active":true},{"id":"loc_1002","name":"Vittles Demo - Riverside","timezone":"America/New_York","active":true}],"next_cursor":"2"}` | Request directo |
| `GET /v1/locations?cursor=4` | 200 | `{"data":[{"id":"loc_1005","name":"Vittles Demo - Beachside","timezone":"America/Los_Angeles","active":true}]}` | Request directo |
| `GET /v1/locations/loc_1001/menu` | 200 | `{"menuItems":[{"id":"itm_88","name":"Buffalo Wings (12)","price":15.5,"available":true,"category":"wings"},{"id":"itm_91","name":"Loaded Fries","price":"8.25","available":1,"category":"sides"},{"id":"itm_95","name":"Nashville Hot Cauliflower","price":11.0,"available":0,"category":"sides"}]}` | Request directo y datos de `MENUS` |
| `GET /v1/locations/loc_1004/menu` | 403 | `{"error":"location is not enabled for partner access"}` | Request directo |
| `GET /v1/locations/loc_1003/menu` | 500 | `{"error":"internal error","trace_id":"<id variable>"}` | Traza local del ADMIN; segundo intento 200 |
| `GET /v1/orders?client_ref=ausente` | 200 | `{"data":[]}` | Request directo |
| `GET /v1/orders/id-inexistente` | 404 | `{"error":"no such order"}` | Request directo |
| `POST /v1/orders` sin `X-Vittles-Location` | 200 | `{"status":"REJECTED","reason":"missing location context"}` | Request directo |
| `POST /v1/orders` con `items: []` | 200 | `{"status":"REJECTED","reason":"no items"}` | Request directo |

Las respuestas de creación/lectura contienen campos variables; su esquema completo aparece en los apartados de órdenes. `trace_id` y `access_token` no deben registrarse como valores fijos.

**Respuestas derivadas del código, aún sin prueba dirigida:** 401 `{"error":"token no longer valid"}` al usar un token vencido; 429 `{"error":"slow down"}` con header `Retry-After-Ms: 4000` al superar el cupo; 401 `{"error":"bad credentials"}` al autenticar con credenciales equivocadas. No se deben presentar como transcripciones de esta ejecución.

## `POST /oauth/token`

**Solicitud:** `Content-Type: application/json` con `client_id` y `client_secret`. No enviar bearer. Ejemplo sin mostrar el secreto:

```json
{ "client_id": "partner-demo", "client_secret": "<valor del mock>" }
```

**Observado:** HTTP 200 con `access_token` (string), `token_type: "bearer"` y `expires: 90`. No existe `expires_in`. **Derivado de `do_POST`:** credenciales incorrectas o JSON inválido/vacío devuelven 401 `{"error":"bad credentials"}`. El token vive en la memoria del proceso; un reinicio del mock invalida los anteriores. Un JSON válido cuya raíz no sea un objeto provoca una excepción no controlada al usar `.get`.

## `GET /v1/locations`

**Solicitud:** bearer. Primer GET sin cursor; repetir con `?cursor=<next_cursor>` mientras exista ese campo.

**Observado:** HTTP 200 y `data` (array). Las tres respuestas tienen tamaños 2, 2 y 1; `next_cursor` es `"2"`, luego `"4"`, y desaparece en la última página. Cada sede incluye `id`, `name`, `timezone` y `active`. En el dataset provisto `loc_1004` tiene `active: false`. **Derivado de `do_GET`:** un cursor no numérico provoca una excepción no controlada en `int(...)`; los cursores negativos o arbitrarios tampoco se validan. Solo usar valores recibidos en `next_cursor`.

## `GET /v1/locations/{location_id}/menu`

**Solicitud:** bearer y un ID de sede.

**Respuesta observada:** HTTP 200, objeto `{ "menuItems": [...] }`. Cada producto trae `id`, `name`, `price`, `available` y `category`. En los datos del mock `price` aparece tanto como número (`15.5`) como string (`"8.25"`), y `available` como booleano o 0/1. `itm_88` cuesta 15.50 en `loc_1001` y `loc_1002`, pero 16.25 en `loc_1003`; en `loc_1005` está agotado. Por eso el producto debe elegirse dentro del menú de la sede indicada.

**Errores observados:** sede desconocida → 404 `{"error":"no such location"}`; sede inactiva `loc_1004` → 403 `{"error":"location is not enabled for partner access"}`. Una traza local registra 500 `{"error":"internal error","trace_id":"..."}` en `loc_1003` y 200 en el siguiente intento. **Derivado de `do_GET`:** ese fallo tiene probabilidad nominal de 12% por intento y se evalúa después de autenticar y comprobar existencia y actividad de la sede; no se debe confundir con menú vacío.

## `POST /v1/orders`

**Solicitud aceptable:** bearer, header `X-Vittles-Location: <location_id>` y JSON con el mismo `location_id` e `items`. Cada línea usa `item_id` y `quantity`. Conviene enviar `Content-Type: application/json`, aunque el mock no comprueba ese header. **Derivado de `do_POST`:** `client_ref` no es obligatorio ni validado; sin él, una orden aceptada devuelve `client_ref: null` y luego no puede buscarse por referencia. El campo `customer` mostrado en la documentación oficial se ignora: no es requerido, no se almacena y no aparece en la respuesta.

```http
POST /v1/orders
Authorization: Bearer <access_token>
X-Vittles-Location: loc_1001
Content-Type: application/json
```

```json
{ "location_id": "loc_1001", "client_ref": "ejemplo-unico", "items": [{ "item_id": "itm_88", "quantity": 2 }] }
```

**Respuesta aceptada observada:** HTTP 201 con `id`, `status: "ACCEPTED"`, `total`, `created_at`, `updated` y `client_ref`. Para dos unidades de `itm_88` en `loc_1001`, el total observado fue `31.0`. El total procede del menú del servidor, no de un importe enviado por el cliente. Ejemplo con valores variables reemplazados:

```json
{
  "id": "ord_5502",
  "status": "ACCEPTED",
  "total": 31.0,
  "created_at": "2026-10-08T09:36:13Z",
  "updated": 1791462973550,
  "client_ref": "<referencia enviada>"
}
```

**Rechazos:** el cuerpo es `{"status":"REJECTED","reason":"..."}` con **HTTP 200**, no 400. Se observaron directamente `missing location context` al omitir el header y `no items` con lista vacía. El código también contempla `unknown location_id`, `location context mismatch`, `item not on this menu` e `item unavailable at this location`. La sede inactiva no tiene un rechazo especial de creación: su menú está vacío, por lo que un producto termina como `item not on this menu`. No interpretar el 200 como orden creada.

**Validación incompleta, derivada de `do_POST`:** el servidor calcula cada cantidad como `int(line.get("quantity") or 1)`. Si falta o vale `0`, cobra una unidad; `2.9` se trunca a 2 y un negativo puede producir total negativo con HTTP 201. Una cantidad no convertible a entero puede disparar una excepción sin JSON estable. JSON inválido o vacío se interpreta como `{}`: sin header se rechaza por `missing location context`; con header se rechaza por `unknown location_id`. Un JSON válido cuya raíz sea lista o string puede fallar al llamar `.get`. La API no ofrece un 400 uniforme ni garantiza una cantidad entera positiva.

**Idempotencia real:** `client_ref` se almacena en cada orden, pero el POST no consulta duplicados. Dos POST aceptados secuenciales con la misma referencia producen dos IDs. No hay garantía `exactly once` del proveedor. La búsqueda por referencia permite conciliar un resultado incierto, pero no es atómica con la creación y sigue siendo vulnerable a carreras entre clientes. **Derivado del código:** `ThreadingHTTPServer` comparte `ORDERS` y genera IDs a partir de `len(ORDERS)` sin bloqueo; las operaciones concurrentes pueden colisionar o sobrescribirse. El mock tampoco garantiza unicidad de órdenes ni de IDs bajo concurrencia.

## `GET /v1/orders?client_ref=<valor>`

Endpoint omitido en `API_DOCS.md`. Requiere bearer; responde HTTP 200 `{ "data": [órdenes] }`. Con una referencia inexistente se observó `{ "data": [] }`. **Derivado de `do_GET`:** si se omite `client_ref` o se envía vacío, devuelve array vacío; **no lista todas las órdenes**. La comparación de referencias es exacta y sensible a mayúsculas. Si hay duplicados, devuelve todos los coincidentes, sin paginación, porque recorre `ORDERS.values()`.

## `GET /v1/orders/{order_id}`

Requiere bearer. Una orden existente devuelve HTTP 200 con `id`, `status`, `total`, `created_at`, `updated` y `client_ref`; un ID desconocido devolvió 404 `{"error":"no such order"}`. **Derivado de `_public_order`:** `updated` es epoch Unix en milisegundos del instante de creación; no indica una actualización posterior. `created_at` se construye con `time.localtime(...)` de la **máquina que ejecuta el mock**, no con `timezone` de la location, y se formatea con sufijo `Z`: ese sufijo sugiere UTC, pero el valor **no es UTC fiable**. La lectura por ID utiliza la misma estructura JSON que la respuesta 201 de creación.

## Alcance de las pruebas

En esta pasada se ejecutaron solicitudes directas a token, las tres páginas de locations, menú activo/inactivo/desconocido, búsqueda y lectura de orden inexistente, y dos rechazos de POST. El panel ADMIN de la pasada anterior mostró creación `CREATED` de `ord_5502` y recuperación `EXISTING` por la integración con el mismo ID y total; las trazas registran un solo POST 201. Otra traza registró un 500 de menú con `trace_id` seguido de 200. Token vencido, saturación 429, cantidades inválidas y concurrencia se especifican a partir de ramas explícitas del mock; no se presentan como pruebas ejecutadas.
