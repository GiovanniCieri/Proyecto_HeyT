# Integración Vittles POS

Laravel 12 + Blade. El comando se autentica, pagina todas las locations, consulta cada menú y crea **una orden de 2 unidades** solo en la sede indicada. El README del ZIP pide una por sede; seguimos el [enunciado](docs/ENUNCIADO.md) reiterado por el candidato. La web es una demo adicional.

**Requisitos:** PHP 8.2+, Composer y Python 3.9+. Copiar `app/.env.example` a `app/.env` y configurar las credenciales Vittles indicadas en el README del mock.

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

En macOS/Linux, usar `python3`. Repetir **el mismo comando** sin reiniciar el mock: debe informar `EXISTING` con el mismo ID y total. Otra compra requiere `--request-key=otra-clave`. Pruebas: `php artisan test` desde `app/`.

**Decisiones:** nombre exacto para evitar ambigüedad; disponibilidad por sede porque los menús difieren; total final del POS para no inventar importes. Generamos una referencia estable y buscamos antes del POST porque `client_ref` no deduplica. Ante resultado incierto, conciliamos sin repetir el POST; el estado puede ser `UNKNOWN`. Las discrepancias verificadas están en [FIX_API_DOCS.md](docs/Docs_API/vittles/FIX_API_DOCS.md).

**Fuera de alcance a propósito:** pagos, stock propio, recuperación de contraseña, email verificado, despliegue y garantía distribuida de *exactly once*; el flujo solicitado no los necesita y la última requiere soporte del POS.

**Demos opcionales:** iniciar `php artisan serve --host=127.0.0.1 --port=8000` desde `app/` y abrir `/register`, o ejecutar `php artisan vittles:console`. La página `/audit` muestra cómo se descubrió y resolvió cada diferencia, separando respuestas observadas de conclusiones por lectura del mock; navegarla no llama a Vittles. Ver [comandos](docs/COMMANDS.md) y [consola](docs/CONSOLE.md).

**Uso de IA:** se usó Codex para analizar el mock, diseñar, implementar y revisar.
