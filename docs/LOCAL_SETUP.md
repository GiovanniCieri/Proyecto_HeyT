# Instalación y arranque local

Los scripts se ejecutan desde la raíz del repositorio y preparan los **dos servicios**: el mock original de Vittles en `127.0.0.1:8422` y la demo Laravel en `127.0.0.1:8000`.

| Sistema | Instalar | Iniciar servicios | Abrir consola interactiva |
| --- | --- | --- | --- |
| Windows PowerShell | `.\scripts\install.ps1` | `.\scripts\start.ps1` | `.\scripts\console.cmd` |
| macOS/Linux | `bash scripts/install.sh` | `bash scripts/start.sh` | `bash scripts/console.sh` |

El instalador verifica PHP 8.2+, Composer y Python 3.9+, ejecuta `composer install`, crea `app/.env` si falta, genera `APP_KEY` solo si está vacía y aplica migraciones SQLite sin borrar datos. Durante Composer muestra la salida real y un mensaje cada ocho segundos con el tiempo transcurrido; generar el autoload optimizado puede tardar varios minutos aun cuando no hay paquetes nuevos. Cuando faltan credenciales pregunta por el identificador y la clave indicados en `docs/Docs_API/vittles/README.md`; la clave se introduce sin eco. Para automatización, usar `-NoPrompt` en PowerShell o `--no-prompt` en Bash y completar `app/.env` por otro medio.

El lanzador revisa configuración y puertos antes de abrir servicios. Si un puerto está ocupado, termina con un mensaje claro sin detener procesos ajenos. El puerto web puede cambiarse: `.\scripts\start.ps1 -WebPort 8080` o `bash scripts/start.sh 8080`. Si el mock ya está abierto, se puede reutilizar explícitamente con `.\scripts\start.ps1 -ReuseMock` o `bash scripts/start.sh 8000 --reuse-mock`. El mock siempre usa 8422, según su código original. `Ctrl+C` detiene solo los procesos que abrió el lanzador. Los errores de arranque se guardan en `app/storage/logs/`; la salida estándar del mock se descarta porque contiene credenciales.

Con el lanzador activo, ejecutar desde otra terminal:

```text
cd app
php artisan vittles:order loc_1001 "Buffalo Wings (12)"
```

Repetir el comando sin reiniciar el mock para comprobar `EXISTING` con el mismo ID y total. La web es adicional; el comando evaluable no requiere cuenta web.

Para entrar directamente al menú de terminal, usar el acceso de la última columna desde otra terminal. Este abre `vittles:console` sin pasos manuales de `cd`. La consola comparte cuentas y servicios con la web, pero no necesita que el navegador esté abierto.

Si PowerShell bloquea los `.ps1` por su política local, ejecutar `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\install.ps1` y luego el equivalente con `start.ps1`. Esto modifica la política únicamente para cada proceso invocado.
