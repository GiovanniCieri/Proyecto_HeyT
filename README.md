# Integración Vittles POS — demo local

Laravel 12 + Blade. El comando Artisan cumple el [enunciado](docs/ENUNCIADO.md): autentica, pagina todas las locations, consulta el menú de cada una y crea **una sola orden** de cantidad 2 en la sede indicada. La web local muestra el mismo flujo con el estilo visual de heytruffle.

**Requisitos:** PHP 8.2+, Composer y Python 3.9+ para el mock. No se necesita Node ni base de datos; tras `composer install`, el mock y la app pueden ejecutarse sin internet (las fuentes web tienen alternativas locales).

1. En una terminal, desde la raíz: `py -3 docs/Docs_API/vittles/mock_server.py` (en macOS/Linux: `python3 docs/Docs_API/vittles/mock_server.py`).
2. En otra terminal: `cd app`, `composer install`, copiar `.env.example` a `.env` (`Copy-Item .env.example .env` en PowerShell), configurar allí `VITTLES_CLIENT_ID` y `VITTLES_CLIENT_SECRET` con los valores del mock y ejecutar `php artisan key:generate` y `php artisan migrate`.
3. **Comando exacto:** `php artisan vittles:order loc_1001 "Buffalo Wings (12)"`. Ejecutarlo dos veces sin reiniciar el mock: la primera muestra `CREATED`; la segunda, `EXISTING` con el mismo ID y total.

Para la demo visual: `php artisan serve --host=127.0.0.1 --port=8000` y abrir `http://127.0.0.1:8000`. Registrar la primera cuenta local en `/register` y luego ingresar por `/login`; se abre la página de inicio. La primera cuenta accede al [ADMIN](docs/ADMIN_DIAGNOSTICS.md) en `/admin`, que permite probar endpoints y revisar trazas locales redactadas. El comando Artisan funciona sin cuenta web. Pruebas: `php artisan test`.

Para investigar errores: copiar el ID de diagnóstico del pie, buscarlo en `app/storage/logs/integration.log` y filtrar las trazas de ADMIN por ese ID. La [guía de logs](docs/LOGGING.md) indica cómo llegar al método PHP correspondiente.

**Decisiones:** nombre exacto; precio y total final del POS; `client_ref` estable por clave de solicitud, sede e ítem; búsqueda antes del POST y bloqueo local entre procesos; sin reintento ciego del POST. Para una intención nueva con el mismo producto, usar `--request-key=otro-identificador`. El mock no hace `client_ref` idempotente, por lo que una carrera entre máquinas o un POST sin respuesta no permite prometer *exactly once*. Ante duda se muestra `UNKNOWN`. La [documentación corregida](docs/Docs_API/vittles/FIX_API_DOCS.md) registra lo observado.

**Dejé fuera a propósito:** recuperación de contraseña, email verificado, pagos, base de datos de negocio, despliegue y garantía de idempotencia distribuida. Esta última requiere soporte atómico del POS o una operación de creación idempotente del proveedor. **Uso de IA:** se usó Codex para analizar el mock, proponer diseño, implementar y revisar; el código y las decisiones deben defenderse en la entrevista.
