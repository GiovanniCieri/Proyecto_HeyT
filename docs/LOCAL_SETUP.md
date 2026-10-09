# Instalación y arranque local

Los scripts se ejecutan desde la raíz del repositorio y preparan los **dos servicios**: el mock original de Vittles en `127.0.0.1:8422` y la demo Laravel en `127.0.0.1:8000`.

| Sistema | Instalar | Iniciar servicios | Abrir consola interactiva |
| --- | --- | --- | --- |
| Windows PowerShell/CMD | `.\scripts\install.cmd` | `.\scripts\start.cmd` | `.\scripts\console.cmd` |
| macOS/Linux | `bash scripts/install.sh` | `bash scripts/start.sh` | `bash scripts/console.sh` |

El instalador verifica PHP 8.2+, Composer y Python 3.9+, ejecuta `composer install`, crea `app/.env` si falta, genera `APP_KEY` solo si está vacía y aplica migraciones SQLite sin borrar datos. Durante Composer muestra la salida real y un mensaje cada ocho segundos con el tiempo transcurrido; generar el autoload optimizado puede tardar varios minutos aun cuando no hay paquetes nuevos. Cuando faltan credenciales pregunta por el identificador y la clave indicados en `docs/Docs_API/vittles/README.md`; la clave se introduce sin eco. Para automatización, usar `-NoPrompt` en PowerShell o `--no-prompt` en Bash y completar `app/.env` por otro medio.

En Windows, `scripts/resolve-php.ps1` busca PHP 8.2+ en `PATH` y `scripts/resolve-python.ps1` busca Python 3.9+ mediante `py -3` o `PATH`. Instalación, arranque, consola y `artisan.cmd` usan las rutas encontradas; el instalador antepone PHP solo durante su proceso para que Composer use la misma versión. No modifica el `PATH` global del equipo. Los `.cmd` abren PowerShell cuando hace falta y evitan depender de la política de ejecución del usuario. `start.cmd` abre una ventana propia que conserva el resultado; `Ctrl+C` detiene los procesos que inició.

El lanzador comprueba que el mock y Laravel responden como esta aplicación. Si ambos ya están activos, muestra sus URLs y termina correctamente; si falta uno, inicia solo ese. Rechaza un servicio ajeno en un puerto requerido y nunca lo detiene. El puerto web puede cambiarse: `.\scripts\start.ps1 -WebPort 8080` o `bash scripts/start.sh 8080`. `-ReuseMock` y `--reuse-mock` siguen disponibles como opciones explícitas. El mock siempre usa 8422. `Ctrl+C` detiene solo procesos propios. Cada arranque crea logs con identificador único en `app/storage/logs/`; descarta la salida estándar del mock porque imprime credenciales.

Con el lanzador activo, ejecutar desde otra terminal:

```text
# Windows, desde la raíz
.\scripts\artisan.cmd vittles:order loc_1001 "Buffalo Wings (12)"

# macOS/Linux, desde la raíz
bash scripts/artisan.sh vittles:order loc_1001 "Buffalo Wings (12)"
```

Repetir el comando sin reiniciar el mock para comprobar `EXISTING` con el mismo ID y total. La web es adicional; el comando evaluable no requiere cuenta web.

Para entrar directamente al menú de terminal, usar el acceso de la última columna desde otra terminal. Este abre `vittles:console` sin pasos manuales de `cd`. La consola comparte cuentas y servicios con la web, pero no necesita que el navegador esté abierto.
En Windows, también se puede abrir `scripts/console.cmd` con doble clic: al salir o fallar, la ventana espera una tecla para que el mensaje siga visible.

Los `.ps1` también pueden ejecutarse directamente desde PowerShell. Los `.cmd` son la opción más simple para Windows y no cambian la política global. El comando nativo `php artisan vittles:order ...` sigue disponible desde `app/` cuando `php` apunta a 8.2+.
