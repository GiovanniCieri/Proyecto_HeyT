# API-003 — Auditoría del contrato corregido

**Fecha:** 2026-10-08

**Archivos:** `docs/Docs_API/vittles/FIX_API_DOCS.md`, `docs/ROADMAP/API_FIX.md`, `docs/CHANGES/README.md` y esta entrada. Los archivos originales `API_DOCS.md`, `README.md` y `mock_server.py` del proveedor no se modificaron.

**Motivo:** completar la documentación real solicitada con la revisión del perfil `.codex/agents/api-auditor.toml` y evitar que el resumen previo ocultara comportamientos peligrosos del mock.

**Cambios:** ejemplos de respuesta por endpoint; distinción entre respuesta observada y conclusión derivada del código; aclaración de que no existe 400/JSON uniforme para entradas mal formadas; `client_ref` opcional y no idempotente; `customer` ignorado; conversión defectuosa de `quantity`; rate limit global; fechas del host; falta de garantías bajo concurrencia. La traza de menú 500 seguida de 200 se incorporó como evidencia observada.

**Verificación:** auditoría de lectura de `API_DOCS.md`, `mock_server.py`, el FIX existente y trazas locales; revisión de `git diff` para confirmar que solo cambia documentación propia. Se mantienen como pendientes de reproducción dirigida token vencido y 429; están identificados expresamente como derivados del código.
