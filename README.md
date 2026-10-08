# Integración Vittles POS

Laravel 12 + Blade. El comando cumple el [enunciado](docs/ENUNCIADO.md): se autentica, pagina todas las locations, consulta cada menú y crea **una orden de 2 unidades** solo en la sede indicada. La web es una demo adicional.

**Requisitos:** PHP 8.2+, Composer y Python 3.9+. Copiar `app/.env.example` a `app/.env` y configurar `VITTLES_CLIENT_ID` y `VITTLES_CLIENT_SECRET` con los valores del mock.

```text
# Terminal 1, desde la raíz (Windows)
py -3 docs/Docs_API/vittles/mock_server.py

# Terminal 2
cd app
composer install
php artisan key:generate
php artisan migrate
php artisan vittles:order loc_1001 "Buffalo Wings (12)"
```

En macOS/Linux, iniciar el mock con `python3`. Repetir **el mismo comando** sin reiniciar el mock: debe informar `EXISTING` con el mismo ID y total. Para otra intención de compra, usar `--request-key=otra-clave`. Pruebas: `cd app` y `php artisan test`.

**Decisiones:** nombre exacto y disponibilidad por sede; total final del POS; referencia estable y búsqueda antes del POST. Ante respuesta incierta, conciliar sin repetir el POST. El mock no garantiza idempotencia atómica entre máquinas; el estado puede ser `UNKNOWN`. Contrato corregido: [FIX_API_DOCS.md](docs/Docs_API/vittles/FIX_API_DOCS.md).

**Fuera de alcance a propósito:** pagos, stock propio, recuperación de contraseña, email verificado, despliegue y garantía distribuida de *exactly once*. No hacen falta para el flujo solicitado; la última depende de soporte del POS.

**Demo web:** `php artisan serve --host=127.0.0.1 --port=8000`; abrir `/register` para crear la primera cuenta local. La web permite varios productos y cantidades de 1 a 20, pero el comando evaluable conserva un producto y cantidad 2. Incluye pedidos locales, README, [ADMIN](docs/ADMIN_DIAGNOSTICS.md) y [guía de logs](docs/LOGGING.md). [Comandos de consulta](docs/COMMANDS.md).

**Uso de IA:** se usó Codex para analizar el mock, diseñar, implementar y revisar; las decisiones y el código deben poder explicarse en la entrevista.
