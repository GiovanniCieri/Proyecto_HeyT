# OBSERVABILITY — Diagnóstico de extremo a extremo

**Estado:** implementado para la demo local

**Dependencias:** integración Vittles, ADMIN y rutas web

**Alcance:** observabilidad local solicitada por el usuario; fuera del entregable CLI obligatorio

## Objetivo

Permitir que una persona reproduzca un error y encuentre la ruta, el método PHP y la respuesta de Vittles desde VS Code y ADMIN, sin leer secretos.

## Tareas y evidencia

- [x] Asignar un `correlation_id` a cada petición web y mostrarlo en el pie y en `X-Diagnostic-Id`.
- [x] Compartir ese ID entre eventos de Laravel y cada traza HTTP de Vittles; asignar uno por ejecución CLI.
- [x] Registrar `event` y `source` buscables en VS Code para auth, catálogo, órdenes, pruebas ADMIN y HTTP externo.
- [x] Mantener un log local dedicado `app/storage/logs/integration.log` y no guardar cuerpos, tokens ni contraseñas en él.
- [x] Añadir filtro por ID y `source` en el detalle de trazas de ADMIN.
- [x] Corregir el mensaje de autenticación 429 para indicar rate limit.
- [x] Probar que un ID une respuesta web, traza y log; probar redacción y mensaje 429 con respuestas simuladas.

## Criterio de aceptación

Al copiar el ID de una página con fallo, una búsqueda en `integration.log` muestra los eventos y el método fuente. La misma cadena filtra las trazas del mock en ADMIN. Si la carga vino de caché, un evento lo indica. La operación de orden sigue funcionando aunque falle el sistema de logs.

## Límite

Es un registro local de demostración. No incluye métricas, alertas, retención centralizada, tracing distribuido ni una política de datos de producción.
