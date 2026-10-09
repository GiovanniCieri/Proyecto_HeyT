# API-004 — Auditoría de caja negra

**Fecha:** 2026-10-08.

**Motivo:** disponer de evidencia reproducible, endpoint por endpoint, para explicar durante la entrevista cómo se descubrieron las diferencias entre la documentación oficial y las respuestas de Vittles.

**Archivos:** `scripts/audit-vittles-blackbox.py`, `docs/Docs_API/vittles/FIX_API_DOCS.md`, `docs/Docs_API/vittles/BLACKBOX_AUDIT_CORE.json`, `BLACKBOX_AUDIT_EDGE.json`, `docs/ROADMAP/API_FIX.md`, `app/config/vittles_audit.php` y este registro. Los archivos originales de Vittles no cambiaron.

**Verificación:** la prueba HTTP cubrió token, las tres páginas de locations, los cinco menús, recursos inexistentes, dos rechazos de POST, dos creaciones idénticas con el mismo `client_ref`, búsqueda por referencia y lectura por ID. Redactó el bearer antes de guardar las respuestas. Los dos POST devolvieron HTTP 201 y distintos IDs; la búsqueda devolvió ambos. Se ejecutó con `py -3 scripts/audit-vittles-blackbox.py` contra el mock local en ejecución.

**Verificación adicional:** 92 segundos después de autenticarse, un GET devolvió 401 `token no longer valid`. Tras una nueva autenticación, una ráfaga de GET produjo 429 `slow down` con `Retry-After-Ms: 4000`. La cantidad exacta de requests permitidos y el alcance del límite no quedaron aislados.

**Pruebas locales:** `py -3 scripts/audit-vittles-blackbox.py` produjo 19 observaciones principales; `--extended` produjo la captura adicional de expiración y 429. `ContractAuditPageTest` pasó con 2 tests y 16 assertions. `git diff --check` no encontró errores. Se comprobó que los JSON de evidencia no contienen el secreto de ejemplo ni bearer tokens.

**Decisión:** `FIX_API_DOCS.md` describe el contrato observado por HTTP y distingue los aspectos que todavía requieren pruebas dirigidas: cupo exacto, cantidades inválidas y concurrencia.

**Pendientes:** medir el cupo exacto y su alcance en una pasada aislada. El recorrido web de auditoría presenta cada hallazgo junto con las pruebas HTTP guardadas.
