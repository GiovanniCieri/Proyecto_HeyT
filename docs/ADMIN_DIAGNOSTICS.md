# Uso de ADMIN

Levantar el mock y Laravel como indica el README principal. Registrar la primera cuenta local, iniciar sesión y abrir `http://127.0.0.1:8000/admin` desde la misma máquina. La ruta responde 404 fuera de `APP_ENV=local`, si `VITTLES_BASE_URL` no apunta a localhost o si el visitante no llega desde loopback; exige además la cuenta administradora, o responde 403.

## Recorrido sugerido

1. Probar autenticación y revisar que `expires` sea 90 y que el token aparezca redactado.
2. Consultar `GET /v1/locations` con cursor vacío, `2` y `4`; comparar `data` y `next_cursor`.
3. Probar el catálogo completo y abrir la traza del menú de `loc_1004` para ver el 403. Probar también un menú individual.
4. Buscar una orden por `client_ref` y leerla por ID. El resultado debe coincidir con la traza HTTP.
5. Probar el rechazo controlado para ver HTTP 200 con `status: REJECTED`. En el mock esta prueba no crea una orden.
6. Crear una orden con la clave `admin-demo`; repetir sin cambiarla y comprobar `CREATED` seguido de `EXISTING` con el mismo ID. Esta acción sí crea una orden en memoria del mock la primera vez.

Cada intento queda en `app/storage/app/private/vittles-traces.jsonl`, ignorado por Git. El filtro **Errores** incluye HTTP 4xx/5xx, fallos de transporte y respuestas `REJECTED`. Las trazas muestran un `operation_id` para relacionar llamadas de un mismo cliente y un `attempt` para distinguir reintentos. Se retienen las 120 más recientes en pantalla; el archivo conserva hasta 2 MB antes de reducirse a las últimas 250 líneas. Las pruebas automatizadas usan un archivo separado.

Para documentar una diferencia, anotar el endpoint, el caso probado, el HTTP y los campos observados. Corroborar con `mock_server.py` cuando se trate de un fallo aleatorio o una condición difícil de reproducir. Registrar solo la corrección técnica y su evidencia en `docs/Docs_API/vittles/FIX_API_DOCS.md`; no copiar secretos ni datos personales. El ADMIN no modifica ese archivo automáticamente.

El mock limita globalmente a 30 requests por 60 segundos. El recorrido completo consume varias llamadas, incluida la autenticación; si aparece 429, esperar a la siguiente ventana antes de repetir la prueba. Evitar repetir POST de creación tras un resultado incierto: usar la búsqueda por `client_ref` o dejar el estado como desconocido.
