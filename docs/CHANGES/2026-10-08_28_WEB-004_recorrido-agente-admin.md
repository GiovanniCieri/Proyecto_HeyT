# WEB-004 — Recorrido del auditor en web y ADMIN

**Fecha:** 2026-10-08.

**Motivo:** mostrar en la demo cómo el agente descubrió las discrepancias y qué pruebas sustentan las decisiones de integración.

**Archivos:** `app/config/vittles_audit.php`, `app/app/Http/Controllers/VittlesController.php`, `app/app/Http/Controllers/AdminController.php`, `app/app/Services/Vittles/VittlesClient.php`, `app/resources/views/vittles/audit.blade.php`, `app/resources/views/admin/index.blade.php`, `app/public/css/heytruffle.css`, `app/tests/Feature/ContractAuditPageTest.php`, roadmaps `CONTRACT_AUDIT_GUIDE.md` y `ADMIN_DIAGNOSTICS.md`, índice de cambios y este registro.

**Cambios:** Auditoría presenta seis momentos de la investigación independiente: contrato publicado, POST rechazado, aislamiento del header de sede, POST duplicado con la misma referencia, búsqueda por referencia y alcance de la garantía. Cada momento señala la evidencia redactada del agente. ADMIN resume el mismo recorrido, distingue las capturas históricas de las trazas de la sesión actual y permite reproducir manualmente el POST publicado sin el header de sede. La creación corregida permanece como una prueba distinta.

**Verificación:** `ContractAuditPageTest` pasó con 3 tests y 23 assertions. Comprueba que navegar Auditoría no contacta Vittles y que la prueba de ADMIN envía el JSON del ejercicio sin `X-Vittles-Location` y muestra `REJECTED`. `git diff --check` sin errores.

**Pendientes:** la prueba de ADMIN requiere mock local disponible para reproducir una respuesta real en la demo. Las respuestas guardadas siguen disponibles en `docs/AUDIT_INDEPENDIENTE/` cuando el mock no esté levantado. La búsqueda previa a un POST no garantiza atomicidad entre procesos.
