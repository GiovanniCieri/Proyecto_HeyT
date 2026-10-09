# API_FIX — Auditoría externa del contrato Vittles

**Estado:** los endpoints publicados y el flujo de repetición fueron auditados también por un agente independiente; quedan pruebas específicas de concurrencia, 500 y cuota exacta.

**Fuente técnica:** `API_DOCS.md` frente a respuestas HTTP del mock levantado como servidor.
**Salida:** `docs/Docs_API/vittles/FIX_API_DOCS.md`, capturas redactadas `BLACKBOX_AUDIT_*.json` y `docs/AUDIT_INDEPENDIENTE/`.

## Tareas y evidencia

- [x] Ejecutar `py -3 scripts/audit-vittles-blackbox.py` contra el mock local.
- [x] Comprobar autenticación sin bearer y lectura con bearer ausente/desconocido.
- [x] Seguir la paginación hasta obtener las cinco sedes.
- [x] Consultar el menú de cada sede y una sede inexistente.
- [x] Probar POST sin header de sede, con lista vacía y aceptado.
- [x] Repetir el POST con el mismo `client_ref` y comparar IDs.
- [x] Buscar por `client_ref` y leer una orden por ID; probar ID inexistente.
- [x] Redactar tokens y credenciales antes de guardar las respuestas.
- [x] Esperar 92 segundos y comprobar que el token ya no sirve: 401 `token no longer valid`.
- [x] Saturar el rate limit y capturar 429, cuerpo y `Retry-After-Ms`.
- [ ] Medir en un mock limpio el cupo exacto y si el límite es global o por token.
- [ ] Reproducir errores transitorios y otros casos límite solo si aportan una decisión a la integración.
- [x] Corregir el recorrido web de auditoría para citar la evidencia HTTP y no la implementación interna del mock.
- [x] Ejecutar una auditoría autónoma de todos los endpoints publicados, sin entregarle hallazgos previos al agente.
- [x] Reproducir por HTTP el header de sede omitido, la creación aceptada, la lectura por ID y dos POST con igual referencia.
- [x] Descubrir por HTTP la búsqueda por `client_ref` y probar la consulta antes y después de crear, incluida una segunda autenticación.
- [x] Comprobar por HTTP JSON incompleto, cantidad cero, producto agotado y precio distinto según la sede.
- [x] Comparar `created_at` con el header HTTP `Date` de la misma respuesta.
- [ ] Reproducir un 500 transitorio con una captura dirigida sin saturar el rate limit.
- [ ] Evaluar carreras entre procesos y recuperación tras un POST de resultado incierto en un entorno controlado.

## Auditoría independiente

El agente `api_auditor` definió y ejecutó sus propios casos sobre los endpoints del ejercicio. Su [informe](../AUDIT_INDEPENDIENTE/INFORME.md) incluye procedimiento repetible, respuestas redactadas, discrepancias confirmadas y preguntas abiertas. La prueba secuencial de repetición está en `preflight.json`; no afirma atomicidad distribuida.

## Criterios de aceptación

- Cada diferencia afirmada en `FIX_API_DOCS.md` remite a una respuesta HTTP reproducible.
- Las observaciones se distinguen de hipótesis y preguntas abiertas.
- El mock, su documentación oficial y el README original permanecen intactos.
- El proceso se puede repetir con un mock limpio y sin leer su implementación.
