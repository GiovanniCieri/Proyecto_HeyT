# IMPL-001 — Integración Laravel y demo Blade

**Fecha:** 2026-10-08

**Archivos:** aplicación en `app/`; `README.md`; documentación corregida `docs/Docs_API/vittles/FIX_API_DOCS.md`; roadmaps y notas de diseño actualizados.

**Motivo:** implementar el ejercicio con el stack elegido por el usuario, mantener un comando evaluable y mostrar el mismo flujo en una web local con el lenguaje visual de heytruffle.ai.

**Cambios:** cliente HTTP con autenticación y renovación de token; paginación de sedes; consulta de los cinco menús; normalización de tipos observados; creación en una sola sede con cantidad 2; búsqueda por referencia y bloqueo local; conciliación de POST incierto. Páginas Blade de Nueva orden, Sedes y menús, y Resultado. El navegador solo recibe el catálogo y el resultado; las credenciales permanecen en `.env` del servidor.

**Verificación:** `php artisan vittles:order loc_1001 "Buffalo Wings (12)"` contra un proceso nuevo del mock devolvió `CREATED`, `ord_5501`, `$31.00`. La segunda ejecución devolvió `EXISTING` con el mismo ID y total; ambas informaron 5 locations y 5 menús consultados. La web mostró las tres páginas y una operación desde el formulario recuperó la misma orden. `php artisan test --filter=VittlesIntegrationTest`: 4 pruebas, 16 aserciones correctas. A 390 px, `scrollWidth` no supera el ancho de contenido.

**Pendiente deliberado:** garantía distribuida de exactly once, login de usuarios, despliegue, comparación visual fina y pruebas específicas de 401/429/500. La sesión del mock almacena órdenes solo en memoria; reiniciarlo borra ese estado.
