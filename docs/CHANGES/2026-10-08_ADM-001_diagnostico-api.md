# ADM-001 — Laboratorio local de API

**Fecha:** 2026-10-08

**Archivos:** `AdminController.php`, `TraceStore.php`, `VittlesClient.php`, vista `admin/index.blade.php`, navegación, CSS, rutas, configuración PHPUnit, prueba `TraceStoreTest.php`, README y documentación de ADMIN.

**Motivo:** inspeccionar el contrato observado de Vittles desde la demo, incluyendo respuestas de error y cada intento de request, para sustentar correcciones en `FIX_API_DOCS.md`.

**Cambios:** endpoint local `/admin` con pruebas guiadas de todos los endpoints del mock; filtros y detalle de trazas; redacción de campos sensibles conocidos; archivo JSONL local con rotación; separación de trazas de tests; restricción a loopback, entorno local y URL de mock local. La creación usa el flujo idempotente existente.

**Verificación:** `php artisan test` pasó 6 pruebas y 22 aserciones. En el navegador se verificaron autenticación, recorrido completo del catálogo (5 sedes), menú inactivo (403), búsqueda y detalle de orden, rechazo HTTP 200 con `REJECTED`, creación `CREATED` de `ord_5502` y segunda ejecución `EXISTING` de `ord_5502` con total 31.00. El filtro de trazas mostró las respuestas HTTP. Las credenciales no aparecieron en las trazas persistidas.

**Límites:** el panel es una herramienta de diagnóstico del mock. No añade autenticación de usuarios ni garantiza idempotencia distribuida. La redacción se limita a campos conocidos; una integración real necesitaría una política formal de datos, almacenamiento y control de acceso.
