# Cómo encontrar un error en VS Code

La aplicación escribe eventos de diagnóstico en `app/storage/logs/integration.log`. El archivo es local y está ignorado por Git. Cada línea tiene un `event` estable, un `source` con la clase y el método de PHP, y un `correlation_id`. Las llamadas al POS también quedan en `app/storage/app/private/vittles-traces.jsonl` y se pueden explorar en **ADMIN → Registro HTTP**.

## Recorrido de un error web

1. Reproducir el problema y copiar el **ID de diagnóstico** que aparece al pie de la página. El mismo valor está en el header `X-Diagnostic-Id` de la respuesta que se ve en Network.
2. En VS Code, abrir `app/storage/logs/integration.log` con **Archivo → Abrir archivo** y buscar ese ID con **Ctrl+F**. Si se usa la búsqueda global, activar la inclusión de archivos ignorados por Git o abrir el log explícitamente.
3. Leer los eventos de ese ID en orden. `source` indica el método correspondiente, por ejemplo `App\Services\Vittles\VittlesClient::authenticate`. Abrir `app/app/Services/Vittles/VittlesClient.php` y buscar `function authenticate`.
4. En ADMIN, filtrar las trazas por el mismo ID. Cada una muestra método, ruta, intento, HTTP, duración, respuesta redactada y `source`. Así se distingue un error de Laravel de una respuesta de Vittles.

Para una ejecución por Artisan no hay respuesta web: buscar el `correlation_id` de `order.place.started` en `integration.log`. El mismo ID aparece en sus trazas a Vittles. Las ejecuciones CLI distintas tienen IDs distintos.

## Eventos principales

| Evento | Método fuente | Significado |
| --- | --- | --- |
| `web.request.started` / `web.request.finished` | `TraceWebRequest::handle` | Entrada y salida HTTP de Laravel; incluye ruta y estado final |
| `auth.login.failed` / `auth.login.succeeded` | `AuthController::login` | Acceso de una persona, sin guardar email ni contraseña |
| `web.catalog.requested` / `web.catalog.failed` | `VittlesController::catalogData` | Consulta de catálogo, cache hit y posible error |
| `catalog.locations.page`, `catalog.menu.loaded` / `catalog.menu.failed` | `CatalogService::load` | Paginación y resultado de cada menú |
| `order.validation_failed`, `order.post.started`, `order.place.finished` | `OrderService::place` | Decisión sobre una orden y resultado |
| `order.reconcile.started` / `order.reconcile.unknown` | `OrderService::reconcileUnknown` | Conciliación tras un POST incierto |
| `vittles.http.attempt` | `VittlesClient::authenticate` o `VittlesClient::send` | Cada intento real al mock, incluidos 429, 500 y fallos de conexión |
| `admin.probe.started` / `admin.probe.failed` | `AdminController::probe` | Prueba guiada desde ADMIN |

El log de aplicación **no guarda cuerpos HTTP**, contraseñas, secretos ni bearer tokens. Solo acepta un conjunto definido de campos diagnósticos. Las trazas del POS conservan cuerpos JSON con redacción de campos sensibles conocidos; son una herramienta del mock local, no una política de almacenamiento para producción.

### Ejemplo: página 200 con Vittles 429

Buscar el ID de la página mostrará `web.request.started`, `web.catalog.requested`, `catalog.load.started`, `vittles.http.attempt` con `http_status: 429`, `web.catalog.failed` y `web.request.finished` con `http_status: 200`. El navegador recibió una página HTML correctamente; el 429 pertenece a la llamada interna a Vittles. Para ver el cuerpo exacto del 429, abrir la traza con ese mismo ID en ADMIN.
