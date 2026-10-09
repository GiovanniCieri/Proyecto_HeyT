# Auditoría independiente de caja negra: Vittles mock

Fecha: 2026-10-08 (America/Buenos_Aires). Alcance: el mock local en `127.0.0.1:8422`, no una API de producción.

## Fuentes y procedimiento

Leí `docs/ENUNCIADO.md`, `docs/Docs_API/vittles/API_DOCS.md` y el README adjunto antes de probar. El enunciado exige una sola orden en la location indicada por parámetro; el README adjunto pide una por location. Para este ejercicio rige el enunciado. No inspeccioné `mock_server.py` ni los informes previos de auditoría.

El servicio ya estaba respondiendo en `127.0.0.1:8422` al comenzar. Se puede repetir con un proceso fresco:

```text
cd docs/Docs_API/vittles
python3 mock_server.py
```

En otra terminal, desde la raíz del proyecto:

```text
python3 docs/AUDIT_INDEPENDIENTE/run_blackbox.py > docs/AUDIT_INDEPENDIENTE/evidence.json
python3 docs/AUDIT_INDEPENDIENTE/probe_order_matrix.py > docs/AUDIT_INDEPENDIENTE/order_matrix.json
python3 docs/AUDIT_INDEPENDIENTE/probe_header_isolation.py > docs/AUDIT_INDEPENDIENTE/header_isolation.json
python3 docs/AUDIT_INDEPENDIENTE/probe_confirmed_order.py > docs/AUDIT_INDEPENDIENTE/confirmed_order.json
python3 docs/AUDIT_INDEPENDIENTE/probe_order_validation.py > docs/AUDIT_INDEPENDIENTE/order_validation.json
python3 docs/AUDIT_INDEPENDIENTE/probe_bad_json.py > docs/AUDIT_INDEPENDIENTE/bad_json.json
python3 docs/AUDIT_INDEPENDIENTE/probe_location_specific.py > docs/AUDIT_INDEPENDIENTE/location_specific.json
python3 docs/AUDIT_INDEPENDIENTE/probe_token_expiry.py > docs/AUDIT_INDEPENDIENTE/token_expiry.json
python3 docs/AUDIT_INDEPENDIENTE/probe_recovery.py > docs/AUDIT_INDEPENDIENTE/recovery.json
python3 docs/AUDIT_INDEPENDIENTE/probe_preflight.py > docs/AUDIT_INDEPENDIENTE/preflight.json
python3 docs/AUDIT_INDEPENDIENTE/probe_order_context_round2.py > docs/AUDIT_INDEPENDIENTE/order_context_round2.json
python3 docs/AUDIT_INDEPENDIENTE/probe_order_context.py > docs/AUDIT_INDEPENDIENTE/order_context.json
```

En esta máquina se ejecutaron los scripts con el Python incluido en el runtime de Codex (`C:\Users\giova\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe`). Usan únicamente la biblioteca estándar. Las credenciales de demostración se leen de `API_DOCS.md` en memoria; las salidas redactan secreto, bearer token y cliente sintético. El registrador conserva los encabezados de ubicación e indica `Authorization: Bearer [REDACTED]` sin guardar su valor. Los `client_ref` son aleatorios para evitar colisiones. Los scripts de variantes ensayan hipótesis de contexto; sus variantes no son parte confirmada del contrato. Conviene correr cada script tras reiniciar el mock o tras el reinicio de la ventana de cuota: las respuestas `429` pueden interrumpir una secuencia. La prueba de vencimiento espera 98 segundos.

Evidencia guardada: [evidence.json](evidence.json), [order_matrix.json](order_matrix.json), [header_isolation.json](header_isolation.json), [confirmed_order.json](confirmed_order.json), [order_validation.json](order_validation.json), [bad_json.json](bad_json.json), [location_specific.json](location_specific.json), [token_expiry.json](token_expiry.json), [recovery.json](recovery.json), [preflight.json](preflight.json), [order_context_round2.json](order_context_round2.json) y [order_context.json](order_context.json). Cada registro contiene método, ruta, cuerpo redactado, estado, encabezados relevantes y cuerpo HTTP. `confirmed_order.json` documenta el flujo positivo y la repetición con el mismo `client_ref`; `order_validation.json` cubre cuerpos límite. Las respuestas `429` se consideran solo evidencia del propio límite y no de la validación de un endpoint. Las consultas potencialmente colectivas de órdenes guardan únicamente estructura, cantidad de resultados e IDs de órdenes propias de la auditoría.

## Hallazgos respaldados por HTTP

| Contrato publicado | Respuesta observada | Impacto |
| --- | --- | --- |
| Token con `expires_in: 3600` y validez de una hora. | `POST /oauth/token` válido: `200` con `expires: 90`, sin `expires_in` (`evidence.json`, `auth_valid`). En una medición separada, el token permitió un GET inmediato y devolvió `401 {"error":"token no longer valid"}` tras 98 segundos (`token_expiry.json`). | Un cliente que solo lea `expires_in` podría fallar o reutilizar el token demasiado tiempo. Se comprobó que deja de ser válido antes de una hora; no se fijó el segundo exacto de vencimiento. |
| `GET /v1/locations` devuelve todas las locations. | Página inicial: dos registros y `next_cursor: "2"`; `?cursor=2`: dos y `next_cursor: "4"`; `?cursor=4`: uno (`evidence.json`). | Hay que paginar para obtener las cinco. El parámetro `cursor` y `next_cursor` no están documentados. |
| Se puede leer el menú de cada location obtenida. | `loc_1004` figura en la lista con `active: false` y su menú devuelve `403 {"error":"location is not enabled for partner access"}`. Los otros cuatro devuelven `200`. | La integración debe registrar el fallo de ese menú y seguir con los demás; no puede asumir que la lista implica acceso al menú. |
| Menú en `menu_items`; `price` decimal y `available` booleano. | Los cuatro menús accesibles devuelven `menuItems`. Hay `price` tanto JSON number como string (`"8.25"`, `"15.50"`); `available` aparece como booleano y como `0`/`1` (`evidence.json`, `menu_*`). | El parser documentado no encontraría ítems y debe normalizar tipos antes de elegir disponibilidad y calcular valores. |
| El cuerpo documentado basta para crear la orden. | El cuerpo publicado, con `location_id`, `client_ref`, cliente sintético e ítem real de cantidad 2, devolvió `200 {"status":"REJECTED","reason":"missing location context"}`. Al agregar **solo** `X-Vittles-Location: loc_1001`, el mismo esquema de cuerpo devolvió `201 ACCEPTED`, ID y total `31.0` (`confirmed_order.json`). `header_isolation.json` muestra que ese encabezado cambia el error de un cuerpo vacío de `missing location context` a `unknown location_id`; los demás candidatos aislados no lo hicieron. | El encabezado requerido no aparece en API_DOCS.md. Sin él, el ejemplo documentado falla; la integración debe enviarlo y comprobar `status`, ID y total. |
| Payload inválido devuelve `400`. | Con el encabezado correcto, cuerpo vacío, `items: []`, ítem desconocido y location desconocida devolvieron `200 {"status":"REJECTED",...}` (`order_validation.json`). Incluso un cuerpo JSON sintácticamente incompleto, exactamente `{`, devolvió `200 REJECTED` (`bad_json.json`). `quantity: 0` devolvió `201 ACCEPTED` con total `15.5` (`order_validation.json`). | No confiar solo en el código HTTP para validar rechazo. El mock tampoco rechaza cantidad cero como sería esperable; la integración debe validar localmente la cantidad que envía. |
| Repetir el mismo `client_ref` devuelve la orden original. | Dos POST idénticos con `X-Vittles-Location` y el mismo `client_ref` devolvieron `201` con ID `ord_5512` y luego `ord_5513`, ambos con total `31.0` (`confirmed_order.json`). `order_matrix.json` muestra otra repetición con IDs distintos. | `client_ref` no aporta la idempotencia prometida en el mock. Para dos ejecuciones consecutivas hace falta una referencia estable, consulta previa y recuperación prudente ante resultados inciertos. |
| La documentación solo describe `GET /v1/orders/{order_id}` para recuperar órdenes. | `GET /v1/orders?client_ref=<ref>` con bearer devolvió `200 {"data": [...]}` filtrado: dos órdenes propias para una referencia repetida (`recovery.json`), cero antes de crear y una después de crear con otra referencia (`preflight.json`). `?clientRef=` devolvió lista vacía para la referencia que tenía dos órdenes. | Hay una consulta por `client_ref` no documentada en el mock. Permite consultar antes de crear y reconciliar después de un resultado incierto. No deduplica por sí misma. |
| `created_at` es UTC ISO-8601. | La respuesta de creación trajo `created_at: "2026-10-08T22:08:53Z"` mientras el encabezado HTTP `Date` del mismo response fue `Fri, 09 Oct 2026 01:08:53 GMT` (`confirmed_order.json`). | La marca parece hora local de Buenos Aires etiquetada como UTC; usarla como UTC desplazaría la hora tres horas. Esta comparación es contra el reloj HTTP del propio mock. |
| En `429` se entrega `Retry-After`. | Dos requests de lectura al final de una secuencia devolvieron `429 {"error":"slow down"}` sin `Retry-After` (`evidence.json`). | No se puede programar el reintento usando siempre ese encabezado. La cuota exacta y su ámbito no se deducen de esta prueba compartida. |

## Respuestas que también delimitan el contrato

- `POST /oauth/token` con credencial incorrecta devolvió `401 {"error":"bad credentials"}`. El válido devolvió token bearer redactado y `expires: 90`.
- `GET /v1/locations` sin token devolvió `401 {"error":"missing token"}` y con token falso `401 {"error":"unknown token"}`.
- `GET /v1/locations/loc_nonexistent_audit/menu` autenticado devolvió `404 {"error":"no such location"}`; sin token, un menú conocido devolvió `401`.
- `POST /v1/orders` sin token devolvió `401`. Los intentos adicionales con `X-Location-Context`, `X-Store-Id`, `location_id` en query, `locationId`, `store_id`, campos `location_context`, `location`, `context.location_id`, objeto `location` y `X-Partner-Location-Id` también devolvieron `200 REJECTED`; una ruta anidada de prueba devolvió `404`. Estos ensayos no prueban cuál es el formato correcto.
- `GET /v1/orders/ord_nonexistent_audit` autenticado devolvió `404 {"error":"no such order"}`; sin token devolvió `401 {"error":"missing token"}` (`order_context*.json`).
- `GET /v1/orders/ord_5512` autenticado devolvió `200` con ID, `status: ACCEPTED`, `total: 31.0`, `created_at`, `updated` y `client_ref` coincidentes con la creación (`confirmed_order.json`).
- `POST /v1/orders` en `loc_1003`, con `itm_88` y cantidad 2, devolvió `201` con total `32.5`, coherente con su precio de menú `16.25` (`location_specific.json`). El mismo ítem figura no disponible en `loc_1005` y ese POST devolvió `200 REJECTED`, razón `item unavailable at this location`. En `loc_1004`, un POST con ese ítem devolvió `200 REJECTED`, razón `item not on this menu`; esto no cambia el `403` de lectura de su menú.

## Cobertura del documento y del ejercicio

| Área | Alcance de la prueba |
| --- | --- |
| Autenticación | Credenciales válidas e inválidas; forma de respuesta; acceso sin token y con token falso. Un token inicialmente válido fue rechazado tras 98 segundos. |
| Regla global de bearer y formato JSON | El token se obtuvo mediante `POST /oauth/token` sin bearer previo, una excepción necesaria a la regla literal de “todas las solicitudes”. Todas las respuestas muestreadas de los cinco endpoints publicados fueron JSON, incluso los rechazos y el JSON de solicitud incompleto; esto no demuestra que toda condición posible lo sea. |
| Locations | Tres páginas hasta ausencia de `next_cursor`; cinco locations en total, incluida una inactiva. |
| Menús | Un GET por cada location listada, más location desconocida y sin token; nombres, IDs, `category`, precio y disponibilidad observados. La moneda USD no se puede deducir de la respuesta. |
| Crear orden | Ejemplo documentado, contexto requerido, creación con cantidad 2 en dos locations, rechazo por ítem no disponible y cuerpos límite. Se identificó `itm_88` a partir del nombre `Buffalo Wings (12)` en el menú; la API recibe `item_id`, no el nombre. |
| Leer orden | Orden existente, orden inexistente y solicitud sin token. |
| Repetir orden | Dos POST idénticos sobre una orden aceptada: IDs diferentes. Esto muestra que la idempotencia documentada no funciona en el mock para ese caso. |
| Recuperar por referencia | Consulta `?client_ref=` con 0, 1 y 2 resultados propios; lectura por ID tras obtener un nuevo token. No se observó un endpoint equivalente al usar `?clientRef=` ni rutas alternativas ensayadas. |
| Límites y errores | Se observaron `401`, `403`, `404` y `429`, además de `200 REJECTED`. No se estableció la cuota exacta ni se forzó `500`. |

## Repetición sin duplicados en dos ejecuciones consecutivas

El flujo observado en [preflight.json](preflight.json) respalda este procedimiento para el mock:

1. Derivar y conservar el mismo `client_ref` de la operación para ambas ejecuciones; para este ejercicio, puede derivarse de la location objetivo, el nombre de ítem normalizado y la cantidad 2. Tomar un bloqueo local por esa operación si dos procesos pueden ejecutarse a la vez.
2. Autenticarse y consultar `GET /v1/orders?client_ref=<valor codificado>`. Si hay una orden, leerla por ID y verificar `client_ref`, estado y total antes de resumirla. Si hay varias, detenerse y señalar duplicados; no crear otra.
3. Solo si la consulta devuelve `200` y lista vacía, enviar una vez `POST /v1/orders` con `X-Vittles-Location` igual a `location_id`. Aceptar el resultado únicamente si devuelve `201`, `status: ACCEPTED`, ID y total.
4. Si la respuesta al POST se pierde o es ambigua, repetir la consulta por `client_ref` antes de decidir. Si sigue vacía, dejar el intento como incierto para conciliación; la prueba no demuestra consistencia temporal ni una garantía atómica para volver a enviar el POST.

El experimento tuvo `data_length: 0`, luego un `201` para `ord_5514`, luego `data_length: 1` con ese ID. Con una nueva autenticación, la consulta siguió devolviendo ese ID y `GET /v1/orders/ord_5514` respondió `200`. Los encabezados `Authorization: Bearer [REDACTED]` y `X-Vittles-Location: loc_1001` están conservados en esa evidencia. El bloqueo local limita carreras en una instalación; no se probó una deduplicación atómica entre instalaciones independientes.

## Límites de la comprobación

Se comprobó creación, lectura y repetición de órdenes aceptadas en `loc_1001`, y creación en `loc_1003`; no se intentó crear órdenes en todas las locations porque el ejercicio solo requiere una location indicada. No se fijó el segundo exacto de caducidad del token, no se estableció la cuota exacta ni se forzó un `500`. El proceso ya estaba en uso al comenzar, así que el momento del `429` no permite inferir una tasa por token. La consulta por `client_ref` mostró visibilidad inmediata en un caso y persistencia tras nueva autenticación, pero no se midió su consistencia ante fallos ni su paginación para cantidades grandes. No se comprobó cómo resuelve el mock un POST cuya respuesta se pierde antes de llegar al cliente; el resultado duplicado al repetir una llamada exitosa hace especialmente importante ese límite.
