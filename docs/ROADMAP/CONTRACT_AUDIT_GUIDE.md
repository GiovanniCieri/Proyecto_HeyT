# CONTRACT_AUDIT_GUIDE — Recorrido de discrepancias

**Estado:** implementado para la demo web
**Pertenece al ejercicio:** la investigación del contrato sí; la página visual es una ampliación
**Dependencias:** API_FIX, POS_CLIENT, ORDER_FLOW, WEB_UI

## Objetivo

Mostrar en orden cómo se descubrieron cinco diferencias entre API_DOCS.md y las respuestas HTTP del mock, qué riesgo creaba cada una y qué decisión se implementó. Cada paso cita la solicitud y la respuesta que sustentan el hallazgo.

## Tareas

- [x] Crear /audit como recorrido de cinco pasos accesible a usuarios de la demo.
- [x] Mostrar documentación oficial, respuesta observada, impacto y corrección por paso.
- [x] Exponer hipótesis, prueba dirigida, discrepancia, confirmación y procedencia de la evidencia.
- [x] Permitir URLs compartibles por paso, navegación anterior/siguiente y acceso desde el menú.
- [x] Mantener la navegación sin llamadas al POS ni POST de prueba.
- [x] Probar acceso, contenido crítico y ausencia de peticiones externas.
- [x] Enlazar el recorrido desde el README de entrega sin ampliar el alcance obligatorio.
- [x] Mostrar la investigación independiente desde la hipótesis oficial hasta el POST aceptado, el duplicado y la recuperación por referencia.
- [x] Citar los archivos redactados del agente para cada momento sin ejecutar requests al abrir la página.

## Criterios de aceptación

- El flujo empieza por autenticación y avanza por locations, menús, orden e idempotencia.
- El relato inicial explica cómo el agente aisló `X-Vittles-Location` y por qué una búsqueda previa permite la segunda ejecución secuencial.
- Cada paso indica su fuente en FIX_API_DOCS.md y el método de la integración que cambió.
- El caso de client_ref declara el límite de exactly once y no crea duplicados al visualizarse.
- La página usa el diseño del resto de la demo y funciona sin JavaScript.

## Mantenimiento

Los textos curados viven en app/config/vittles_audit.php. Si cambia la evidencia en FIX_API_DOCS.md, revisar ambos archivos para conservar la misma procedencia y no convertir una inferencia en una supuesta observación.
