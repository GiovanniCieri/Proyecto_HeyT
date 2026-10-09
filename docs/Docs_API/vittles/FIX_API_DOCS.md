# Vittles POS — contrato observado por caja negra

Esta corrección se limita al mock local expuesto en `http://127.0.0.1:8422`. Se construyó comparando `API_DOCS.md` con respuestas HTTP. La [auditoría independiente](../../AUDIT_INDEPENDIENTE/INFORME.md) contiene su procedimiento y capturas redactadas; otra prueba reproducible es [`scripts/audit-vittles-blackbox.py`](../../../scripts/audit-vittles-blackbox.py), con respuestas en `BLACKBOX_AUDIT_CORE.json` y `BLACKBOX_AUDIT_EDGE.json`. Las órdenes del mock son efímeras y sus IDs cambian con el estado del proceso.

## Discrepancias demostradas

| Tema | Documentación oficial | Respuesta HTTP observada | Caso de evidencia |
| --- | --- | --- | --- |
| Autenticación | Todas las solicitudes requieren bearer; `expires_in: 3600` | `POST /oauth/token` sin bearer respondió 200 con `expires: 90`; un GET sin bearer respondió 401 | `auth_without_bearer`, `locations_without_bearer` |
| Locations | Un GET devuelve todas | El primer GET devolvió 2 y `next_cursor: "2"`; luego 2 y `"4"`; luego 1 sin cursor | `locations_page_1..3` |
| Menú | `menu_items`; precio decimal y disponibilidad booleana | `menuItems`; precios numéricos o string numérico; disponibilidad booleana o 0/1 | `menu_loc_1001..1005` |
| Sede inactiva | Sin tratamiento indicado | La sede `loc_1004` figuró como `active: false` y su menú respondió 403 | `locations_page_2`, `menu_loc_1004` |
| Creación inválida | HTTP 400 con `error` | Sin `X-Vittles-Location`, y con `items: []` tras enviarlo, respondió HTTP 200 con `status: REJECTED` y `reason` | `post_without_location_header`, `post_empty_items` |
| Idempotencia | Repetir `client_ref` devuelve la orden original | Dos POST iguales, consecutivos y aceptados devolvieron dos IDs diferentes | `post_first`, `post_same_client_ref` |
| Búsqueda | Solo documenta lectura por ID | `GET /v1/orders?client_ref=...` devolvió ambas órdenes en `data` | `lookup_by_client_ref` |
| Lectura de orden | Ejemplo con cuatro campos | La respuesta incluyó también `updated` y `client_ref` | `read_created_order` |
| Errores | No enumera 403 | Una sede inactiva respondió 403; recursos inexistentes, 404 | `menu_loc_1004`, `menu_unknown_location`, `order_unknown_id` |
| Token vencido | Válido 1 hora | Tras esperar 92 segundos desde la autenticación, un GET respondió 401 `token no longer valid` | `expired_token_after_92s` en `BLACKBOX_AUDIT_EDGE.json` |
| Rate limit | 60/min por token; header `Retry-After` en segundos | Una ráfaga recibió 429 `slow down` con `Retry-After-Ms: 4000`; la prueba no aisló el cupo exacto ni si es global | `rate_limit` en `BLACKBOX_AUDIT_EDGE.json` |
| Cantidad | El ejemplo solo muestra una cantidad positiva y promete 400 para payload inválido | `quantity: 0` fue aceptada con HTTP 201 y total de una unidad | `order_validation.json` en la auditoría independiente |
| Fecha UTC | `created_at` es UTC | En una misma respuesta, `created_at` terminó en `Z` pero difirió tres horas del header HTTP `Date` | `confirmed_order.json` en la auditoría independiente |

## Contrato comprobado por endpoint

### `POST /oauth/token`

El JSON de credenciales del ejemplo oficial, enviado **sin** `Authorization`, devolvió HTTP 200 con `access_token`, `token_type: "bearer"` y `expires: 90`. En esta respuesta no apareció `expires_in`. Tras 92 segundos, un GET con el mismo token devolvió HTTP 401 `{"error":"token no longer valid"}`. Esto refuta la validez de una hora; no mide el segundo exacto del vencimiento. Los tokens están redactados en la evidencia.

### `GET /v1/locations`

Requiere bearer. Sin él se observó 401 `{"error":"missing token"}`; con uno desconocido, 401 `{"error":"unknown token"}`. Una lectura completa requirió seguir `next_cursor`: tamaños 2, 2 y 1. Las cinco sedes traen `id`, `name`, `timezone` y `active`; `loc_1004` apareció inactiva. Se deben usar los cursores devueltos, no asumir un formato de paginación por el ejemplo oficial.

### `GET /v1/locations/{location_id}/menu`

Las sedes activas consultadas respondieron HTTP 200 con `menuItems`. En esos productos se observaron `id`, `name`, `price`, `available` y `category`. `price` apareció como número y como string numérico; `available` como booleano y 0/1. `itm_88` apareció a 15.50 en `loc_1001` y 16.25 en `loc_1003`, por lo que el precio se toma del menú de la sede solicitada. `loc_1004` respondió 403 `{"error":"location is not enabled for partner access"}`. Una sede inexistente respondió 404 `{"error":"no such location"}`. Una pasada posterior observó también un 500 transitorio, pero el recorrido principal registrado en `BLACKBOX_AUDIT_CORE.json` no lo reprodujo.

### `POST /v1/orders`

Con bearer, `X-Vittles-Location: loc_1001` y el JSON siguiente, el servidor devolvió HTTP 201 con `id`, `status: "ACCEPTED"`, `total`, `created_at`, `updated` y `client_ref`:

```json
{"location_id":"loc_1001","client_ref":"referencia-unica","items":[{"item_id":"itm_88","quantity":2}]}
```

Dos unidades dieron `total: 31.0` en la prueba. Si se omite el header de sede, respondió HTTP 200 `{"status":"REJECTED","reason":"missing location context"}`. Con el header pero `items: []`, respondió HTTP 200 `{"status":"REJECTED","reason":"no items"}`. Por eso se debe verificar el cuerpo además del status HTTP.

La auditoría independiente confirmó además que un JSON incompleto (`{`) con el header de sede devolvió HTTP 200 `REJECTED`, y que `quantity: 0` devolvió HTTP 201 `ACCEPTED` con total `15.5` para un producto de ese precio. En `loc_1003`, dos unidades de `itm_88` dieron `32.5`; el mismo ítem no disponible en `loc_1005` produjo HTTP 200 `REJECTED`. Son respuestas del mock, no una validación aceptable para el cliente: la integración debe enviar cantidades enteras positivas y comprobar disponibilidad en la sede elegida.

La afirmación de idempotencia de `API_DOCS.md` quedó refutada: dos POST aceptados con el **mismo** `client_ref`, sede y producto generaron `ord_5503` y `ord_5504` en esta ejecución. Los IDs son solo evidencia de esta sesión, no valores constantes. El proveedor no garantizó unicidad de `client_ref` en la prueba.

### `GET /v1/orders?client_ref=...` y `GET /v1/orders/{order_id}`

La búsqueda por la referencia usada arriba devolvió HTTP 200 con `data` que contenía ambas órdenes. La lectura por ID de la primera devolvió HTTP 200 con los mismos datos de la creación. Un ID inexistente respondió 404 `{"error":"no such order"}`. La búsqueda por referencia puede ayudar a conciliar, pero estas respuestas **no prueban** una operación atómica entre buscar y crear ni una garantía para dos clientes concurrentes.

Una prueba independiente consultó una referencia nueva (`data: []`), creó una orden y volvió a consultarla (`data` con una orden). Tras obtener otro token, una segunda consulta por la misma referencia recuperó el mismo ID sin otro POST. Esto demuestra un camino para repetir el flujo secuencialmente; no convierte la búsqueda y el POST en una transacción atómica.

### Rate limit observado

Tras la prueba de expiración, una nueva autenticación respondió 200. Una ráfaga de GET a locations recibió HTTP 429 `{"error":"slow down"}` en el intento 29 después de esa autenticación y el header `Retry-After-Ms: 4000`. Como había solicitudes previas al mismo proceso, **no se puede inferir de este intento el cupo exacto ni si se aplica por token o globalmente**. La corrección segura es leer `Retry-After-Ms` en milisegundos y tratar 429 como una respuesta temporal.

## Límites de la evidencia

- Se observó HTTP 429 y `Retry-After-Ms: 4000`. Falta una prueba aislada para medir el cupo exacto y determinar si el límite es global o por token.
- `expires: 90` y el 401 tras 92 segundos no demuestran el segundo exacto de expiración.
- En `confirmed_order.json`, `created_at: "2026-10-08T22:08:53Z"` y el header HTTP `Date: Fri, 09 Oct 2026 01:08:53 GMT` difieren tres horas en una misma respuesta. El campo `created_at` no debe interpretarse como UTC fiable; esta comparación no determina la regla de zona horaria en todos los entornos.
- Se probaron un JSON incompleto y `quantity: 0`, pero no todos los cuerpos malformados, campos opcionales ni condiciones concurrentes. Tampoco se infiere un contrato de producción a partir de este mock.
- La prueba crea órdenes reales en la memoria del mock y puede alcanzar el rate limit si se ejecuta repetidamente. En ese caso un 429 de una pasada nueva no constituye evidencia del endpoint que se intentaba probar; se debe esperar o reiniciar el mock y repetir la prueba.
