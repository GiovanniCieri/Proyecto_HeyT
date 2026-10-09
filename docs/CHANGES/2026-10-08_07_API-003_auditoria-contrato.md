# API-003 — Auditoría del contrato corregido

**Fecha:** 2026-10-08

**Archivos:** `docs/Docs_API/vittles/FIX_API_DOCS.md`, `docs/ROADMAP/API_FIX.md`, `docs/CHANGES/README.md` y esta entrada. Los archivos originales `API_DOCS.md`, `README.md` y `mock_server.py` del proveedor no se modificaron.

**Motivo:** ampliar la documentación técnica con ejemplos por endpoint y riesgos relevantes para la integración, usando el perfil `.codex/agents/api-auditor.toml`.

**Cambios:** ejemplos de respuesta por endpoint, comparación con `API_DOCS.md` y explicación de los riesgos de paginación, rechazo con HTTP 200, tipos de menú e idempotencia. Se incorporó una traza de menú 500 seguida de 200 como evidencia de un fallo transitorio.

**Verificación:** comparación de `API_DOCS.md` con las respuestas directas y las trazas locales disponibles; revisión de `git diff` para confirmar que solo cambia documentación propia. La evidencia reproducible ampliada de las pasadas posteriores está en `BLACKBOX_AUDIT_CORE.json` y `BLACKBOX_AUDIT_EDGE.json`.
