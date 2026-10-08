# LOG-001 — Diagnóstico buscable de extremo a extremo

**Fecha:** 2026-10-08

**Archivos:** `DiagnosticLog.php`, `TraceWebRequest.php`, `TraceStore.php`, cliente y servicios Vittles, controladores, configuración de logging, vistas y CSS, pruebas, `docs/LOGGING.md`, roadmap y README.

**Motivo:** Network del navegador solo muestra la llamada a Laravel. Se necesitaba relacionar el error visible con la llamada interna a Vittles y encontrar fácilmente el método PHP en VS Code.

**Cambios:** ID de diagnóstico por request web y por ejecución CLI; header `X-Diagnostic-Id` y pie de página; eventos estructurados con clase/método fuente; log dedicado `integration.log`; ID y fuente en las trazas HTTP; búsqueda por ID en ADMIN; mensaje de 429 correcto. Los detalles de aplicación se limitan a campos permitidos; no se registran cuerpos ni secretos en ese log.

**Verificación:** pruebas automatizadas de correlación entre respuesta web, log y traza, redacción de campos excluidos y mensaje 429; revisión manual de una respuesta `/login` con header e ID presente en `integration.log`. `php artisan test`: 14 pruebas, 67 aserciones; Pint y compilación de vistas Blade correctos.

**Pendiente deliberado:** métricas, alertas, exportación, retención centralizada y redacción de datos para un POS real.
