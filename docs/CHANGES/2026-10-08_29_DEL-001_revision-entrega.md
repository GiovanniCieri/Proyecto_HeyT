# DEL-001 — Revisión previa a la entrega

**Fecha:** 2026-10-08.

**Motivo:** verificar que el entregable principal se pueda ejecutar y leer según el enunciado, separando las demos adicionales.

**Archivos:** `README.md`, `docs/DEMO_GALLERY.md`, `docs/PROPUESTA.md`, `.gitignore`, `scripts/package-release.py`, índice de cambios y este registro.

**Cambios:** el README principal quedó en unas 230 palabras, con comando exacto, decisiones, exclusiones deliberadas y declaración de IA. Las capturas de la demo pasaron a una página separada. El ejemplo histórico de configuración ya no imprime la clave del mock. El repositorio ignora archivos temporales de Python. El script de empaquetado produce un ZIP del árbol actual sin historial Git ni datos locales de ejecución.

**Verificación:** contra el mismo proceso del mock, dos ejecuciones de `scripts/artisan.cmd vittles:order loc_1001 "Buffalo Wings (12)" --request-key=entrega-20261008-verificacion` devolvieron `CREATED` y `EXISTING` con ID `ord_5518` y total `$31.00`. La suite Laravel pasó: 32 tests, 205 assertions. La búsqueda de secretos fuera de los archivos originales del proveedor no encontró la clave de ejemplo ni bearer tokens. El ZIP contiene 198 archivos, incluidos README, instalador, mock, lockfile y evidencia, sin `.env`, SQLite, logs ni vendor. Se extrajo en una carpeta limpia y `scripts/install.ps1 -NoPrompt` terminó correctamente: instaló las dependencias, creó la configuración y ejecutó las migraciones. `git diff --check` no mostró errores.

**Pendiente de publicación:** los cambios continúan en el árbol local y no están en `origin/main`. El ZIP está preparado junto al repositorio; antes de enviar la respuesta a los reclutadores, elegir ese archivo o publicar los cambios y copiar el enlace definitivo.
