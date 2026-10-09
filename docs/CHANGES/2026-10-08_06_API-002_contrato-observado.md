# API-002 — Contrato observado del mock

**Fecha:** 2026-10-08

**Archivos:** `docs/Docs_API/vittles/FIX_API_DOCS.md`, roadmap `API_FIX.md` e índice de changes. Los tres archivos originales recibidos de Vittles permanecen intactos.

**Motivo:** convertir el resumen previo de discrepancias en una referencia técnica completa, endpoint por endpoint, con contraste explícito frente a `API_DOCS.md`.

**Cambios:** tabla de diferencias, solicitudes y cuerpos esperados, errores, campos, paginación, idempotencia y procedencia de la evidencia disponible.

**Verificación:** requests directos al mock: token 200 con `expires=90`; locations en páginas 2/2/1; menú activo 200 con `menuItems`; menú inactivo 403; sede y orden desconocidas 404; búsqueda vacía 200; POST sin header y POST sin items devuelven 200 `REJECTED`. La creación y búsqueda por referencia se verificaron en la pasada ADM-001.

**Pendiente en esta pasada:** reproducir token vencido, 429 y fallos transitorios mediante solicitudes dirigidas. Las capturas posteriores de token vencido y 429 se conservan en `BLACKBOX_AUDIT_EDGE.json`.
