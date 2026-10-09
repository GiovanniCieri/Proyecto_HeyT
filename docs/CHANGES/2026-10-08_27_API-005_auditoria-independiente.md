# API-005 — Auditoría independiente de endpoints

**Fecha:** 2026-10-08.

**Motivo:** contar con un agente que descubra y pruebe el contrato de Vittles desde la documentación oficial y las respuestas HTTP, con evidencia que pueda repetirse durante la entrevista.

**Archivos:** `.codex/agents/api-auditor.toml`, `.codex/README.md`, `docs/AUDIT_INDEPENDIENTE/`, `docs/Docs_API/vittles/FIX_API_DOCS.md`, `docs/ROADMAP/API_FIX.md`, `docs/CHANGES/README.md` y este registro. Los archivos originales del proveedor permanecen intactos.

**Cambios:** el perfil `api_auditor` puede diseñar y ejecutar su propia auditoría y guardar procedimiento y capturas redactadas. Se ejecutaron pruebas de los cinco endpoints publicados, más la búsqueda por `client_ref` descubierta durante la exploración. El informe relaciona afirmaciones oficiales, respuestas, impactos y límites. `FIX_API_DOCS.md` incorporó evidencia nueva sobre cantidad cero, fecha de creación, precio por sede y consulta previa a la creación.

**Verificación:** el agente obtuvo respuestas HTTP para autenticación, todas las páginas de locations, los menús de las cinco sedes, creación y lectura de orden, repetición de POST, búsqueda por referencia, token vencido y varios rechazos. `preflight.json` documenta una referencia ausente, una sola creación y recuperación del mismo ID tras una nueva autenticación. Los JSON de evidencia se validaron sintácticamente y se buscaron secretos, bearer tokens y el teléfono de ejemplo sin encontrar valores expuestos.

**Pendientes:** no se midió el cupo exacto ni se reprodujo un 500 dirigido. La búsqueda previa y el POST no constituyen una operación atómica entre procesos; la recuperación de una respuesta de POST perdida requiere una prueba controlada adicional.
