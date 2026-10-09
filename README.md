# Integración Vittles POS

Laravel 12 + Blade. El comando se autentica, obtiene todas las locations, intenta leer el menú de cada una y crea **una orden de 2 unidades** solo en la sede indicada. El README del ZIP pide una por sede; seguimos el [enunciado](docs/ENUNCIADO.md) del ejercicio. La web y la consola interactiva son demos adicionales.

**Requisitos:** PHP 8.2+, Composer y Python 3.9+. El instalador conserva `.env`, `APP_KEY` y SQLite existentes. Cuando faltan, pide las credenciales indicadas en el [README original del mock](docs/Docs_API/vittles/README.md), sin mostrar la clave.

**Scripts `resolve` (Windows):** `resolve-php.ps1` busca un PHP 8.2+ aunque otro PHP aparezca primero en `PATH`; `resolve-python.ps1` busca Python 3.9+ para el mock. Instalación y arranque los usan automáticamente; consola y `artisan.cmd` usan el selector de PHP. No hay que ejecutarlos a mano: no instalan programas ni cambian el `PATH` global.

```text
# Terminal 1, desde la raíz (Windows)
.\scripts\install.cmd
.\scripts\start.cmd

# Terminal 2, comando evaluable con PHP compatible
.\scripts\artisan.cmd vittles:order loc_1001 "Buffalo Wings (12)"
```

El arranque abre el mock en `127.0.0.1:8422` y la web en `http://127.0.0.1:8000`, o reconoce los servicios si ya están abiertos. En macOS/Linux: `bash scripts/install.sh`, `bash scripts/start.sh` y `bash scripts/artisan.sh vittles:order loc_1001 "Buffalo Wings (12)"`. Repetir **el mismo comando** sin reiniciar el mock: debe informar `EXISTING` con el mismo ID y total. Otra compra requiere `--request-key=otra-clave`. Pruebas: `scripts/artisan.cmd test` o `bash scripts/artisan.sh test`. Opciones: [guía de arranque](docs/LOCAL_SETUP.md).

**Decisiones:** nombre exacto para evitar ambigüedad; disponibilidad por sede porque los menús difieren; total final del POS para no inventar importes. Generamos una referencia estable y buscamos antes del POST porque `client_ref` no deduplica. Ante resultado incierto, conciliamos sin repetir el POST; el estado puede ser `UNKNOWN`. Las diferencias observadas entre documentación y mock, con evidencia y corrección, están en [FIX_API_DOCS.md](docs/Docs_API/vittles/FIX_API_DOCS.md).

**Fuera de alcance a propósito:** pagos, stock propio, recuperación de contraseña, email verificado, despliegue y garantía distribuida de *exactly once*; el flujo solicitado no los necesita y la última requiere soporte del POS.

**Demos opcionales:** con ambos servicios levantados, abrir `http://127.0.0.1:8000/register` o abrir la consola desde la raíz con `.\scripts\console.cmd` (macOS/Linux: `bash scripts/console.sh`). La página `/audit` muestra cómo se descubrió y resolvió cada diferencia; navegarla no llama a Vittles. Ver [comandos](docs/COMMANDS.md) y [consola](docs/CONSOLE.md).

**Uso de IA:** se usó Codex para analizar el mock, diseñar, implementar y revisar.

<img width="1919" height="914" alt="image" src="https://github.com/user-attachments/assets/16fa8ed2-85e9-49cc-9c7a-6308275dfc42" />
<img width="1898" height="907" alt="image" src="https://github.com/user-attachments/assets/2c610fce-e40c-4081-96db-927422ee3c6b" />
<img width="1895" height="913" alt="image" src="https://github.com/user-attachments/assets/eb493d6f-73cf-4d30-9a96-5eabf7444213" />
<img width="1900" height="911" alt="image" src="https://github.com/user-attachments/assets/4001d4c4-66cc-432d-9f5f-a5f478a6cb82" />
<img width="1903" height="916" alt="image" src="https://github.com/user-attachments/assets/63e4f42b-70b9-4e51-aba0-c62834d511ad" />
<img width="1896" height="911" alt="image" src="https://github.com/user-attachments/assets/037464a1-0111-404b-a6da-34c46f85e314" />
<img width="694" height="430" alt="image" src="https://github.com/user-attachments/assets/245f6858-cb0a-40ac-8b89-b533e7a7e5c5" />
<img width="809" height="506" alt="image" src="https://github.com/user-attachments/assets/b6145250-fac4-45ad-a396-62daa49cb7ba" />





