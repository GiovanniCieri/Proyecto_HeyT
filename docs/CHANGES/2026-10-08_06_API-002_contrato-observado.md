# API-002 — Contrato observado del mock

**Fecha:** 2026-10-08

**Archivos:** `docs/Docs_API/vittles/FIX_API_DOCS.md`, roadmap `API_FIX.md` e índice de changes. Los tres archivos originales recibidos de Vittles permanecen intactos.

**Motivo:** convertir el resumen previo de discrepancias en una referencia técnica completa, endpoint por endpoint, con contraste explícito frente a `API_DOCS.md`.

**Cambios:** tabla de diferencias, solicitudes y cuerpos esperados, errores, campos, paginación, idempotencia, límite global, timestamp y procedencia de cada afirmación. Se distinguen observaciones directas, trazas anteriores y ramas leídas del mock.

**Verificación:** requests directos al mock: token 200 con `expires=90`; locations en páginas 2/2/1; menú activo 200 con `menuItems`; menú inactivo 403; sede y orden desconocidas 404; búsqueda vacía 200; POST sin header y POST sin items devuelven 200 `REJECTED`. La creación y búsqueda por referencia se verificaron en la pasada ADM-001.

**Pendiente:** no se reprodujeron en esta pasada token vencido, 429 ni 500 aleatorio; sus respuestas se documentan como comportamiento del código fuente, no como transcripción ejecutada.
