# Integración Vittles POS

Laravel 12 + Blade. El comando se autentica, obtiene todas las locations, intenta leer el menú de cada una y crea **una orden de 2 unidades** solo en la sede indicada. El README del ZIP pide una por sede; seguimos el [enunciado](docs/ENUNCIADO.md) del ejercicio. La web y la consola interactiva son demos adicionales.

**Requisitos:** PHP 8.2+, Composer y Python 3.9+. El instalador conserva `.env`, `APP_KEY` y SQLite existentes. Cuando faltan, pide las credenciales indicadas en el [README original del mock](docs/Docs_API/vittles/README.md), sin mostrar la clave.

```text
# Terminal 1, desde la raíz (Windows PowerShell)
.\scripts\install.ps1
.\scripts\start.ps1

# Terminal 2, comando evaluable
cd app
php artisan vittles:order loc_1001 "Buffalo Wings (12)"
```

El arranque abre el mock en `127.0.0.1:8422` y la web en `http://127.0.0.1:8000`; `Ctrl+C` detiene los procesos que inició. En macOS/Linux: `bash scripts/install.sh` y `bash scripts/start.sh`. Repetir **el mismo comando** sin reiniciar el mock: debe informar `EXISTING` con el mismo ID y total. Otra compra requiere `--request-key=otra-clave`. Pruebas: `php artisan test` desde `app/`. Puertos ocupados y otras opciones: [guía de arranque](docs/LOCAL_SETUP.md).

**Decisiones:** nombre exacto para evitar ambigüedad; disponibilidad por sede porque los menús difieren; total final del POS para no inventar importes. Generamos una referencia estable y buscamos antes del POST porque `client_ref` no deduplica. Ante resultado incierto, conciliamos sin repetir el POST; el estado puede ser `UNKNOWN`. Las diferencias observadas entre documentación y mock, con evidencia y corrección, están en [FIX_API_DOCS.md](docs/Docs_API/vittles/FIX_API_DOCS.md).

**Fuera de alcance a propósito:** pagos, stock propio, recuperación de contraseña, email verificado, despliegue y garantía distribuida de *exactly once*; el flujo solicitado no los necesita y la última requiere soporte del POS.

**Demos opcionales:** con ambos servicios levantados, abrir `http://127.0.0.1:8000/register` o abrir la consola desde la raíz con `.\scripts\console.cmd` (macOS/Linux: `bash scripts/console.sh`). La página `/audit` muestra cómo se descubrió y resolvió cada diferencia; navegarla no llama a Vittles. Ver [comandos](docs/COMMANDS.md) y [consola](docs/CONSOLE.md).

**Uso de IA:** se usó Codex para analizar el mock, diseñar, implementar y revisar.
