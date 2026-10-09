# Integración Vittles POS

Laravel 12 + Blade. El comando se autentica, trae **todas** las locations y sus menús, y crea **una sola orden** de 2 unidades en la sede indicada. La web y la consola interactiva son una demo adicional; el comando funciona sin login humano.

**Requisitos:** PHP 8.2+, Composer y Python 3.9+. Desde la raíz, en Windows:

```powershell
.\scripts\install.cmd
.\scripts\start.cmd
.\scripts\artisan.cmd vittles:order loc_1001 "Buffalo Wings (12)"
```

En macOS/Linux: `bash scripts/install.sh`, `bash scripts/start.sh` y `bash scripts/artisan.sh vittles:order loc_1001 "Buffalo Wings (12)"`. El instalador solicita las credenciales del [README original](docs/Docs_API/vittles/README.md) si faltan. Repetí el **mismo comando sin reiniciar el mock**: informa `EXISTING` con el mismo ID y total. Para otra compra, usá `--request-key=otra-clave`. Tests: `scripts/artisan.cmd test` (o `bash scripts/artisan.sh test`).

**Decisiones:** nombre exacto para evitar comprar un ítem parecido; precio y disponibilidad del menú de la sede elegida; total confirmado por Vittles. Una referencia estable y una búsqueda previa evitan duplicados en dos ejecuciones consecutivas, porque el POST del mock **no** deduplica `client_ref`. Un POST de resultado incierto se concilia sin reenviarlo a ciegas. [Contrato corregido y evidencia HTTP](docs/Docs_API/vittles/FIX_API_DOCS.md); [investigación independiente](docs/AUDIT_INDEPENDIENTE/INFORME.md).

**Fuera de alcance a propósito:** pagos, stock propio, recuperación de contraseña, despliegue y garantía *exactly once* entre procesos. La búsqueda y la creación no son atómicas.

**Uso de IA:** se usó Codex para auditar las respuestas HTTP, diseñar, implementar y revisar. [Arranque detallado](docs/LOCAL_SETUP.md) · [Comandos](docs/COMMANDS.md) · [Demo y capturas](docs/DEMO_GALLERY.md).
