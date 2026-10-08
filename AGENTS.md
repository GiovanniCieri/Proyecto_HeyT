# Instrucciones del proyecto HeyT / Vittles

## Alcance y fuentes

- Leer docs/ENUNCIADO.md antes de planificar o implementar. El entregable obligatorio es una integración pequeña: autenticación contra Vittles, todas las locations, intento de leer cada menú, una orden de cantidad 2 solo en la location indicada por parámetro, resumen e idempotencia entre dos ejecuciones consecutivas.
- El README provisto en docs/Docs_API/vittles/ formula de otra manera el alcance de órdenes. Para este proyecto rige docs/ENUNCIADO.md, reiterado por el usuario. Registrar la discrepancia; no añadir órdenes masivas.
- Para el contrato técnico, contrastar API_DOCS.md con respuestas del mock. El comportamiento comprobado del mock decide cómo debe funcionar esta integración. No extrapolarlo a una API de producción.
- No modificar los archivos originales docs/Docs_API/vittles/API_DOCS.md, README.md ni mock_server.py. Documentar correcciones solo en FIX_API_DOCS.md.

## Documentación y cambios

- Cada funcionalidad tiene un roadmap en docs/ROADMAP. Mantener estado, tareas, dependencias y criterios de aceptación verificables.
- Registrar cada pasada con fecha e identificador en docs/CHANGES, usando el nombre YYYY-MM-DD_ID_descripcion.md. Indicar archivos cambiados, motivo, verificación y pendientes.
- FIX_API_DOCS.md contiene únicamente el contrato corregido de Vittles y las discrepancias con la documentación original, con evidencia. No poner allí tareas, decisiones de producto ni bitácoras.
- Mantener separado el MVP solicitado de la ampliación web. Páginas, login y registro no forman parte de la entrega obligatoria del ejercicio.

## Implementación futura

- El usuario eligió Laravel 12 + Blade y luego pidió login/registro web. Mantener la integración reutilizable entre comando Artisan y demo web, sin agregar Angular; el acceso web no debe ser requisito del comando CLI.
- No exponer client_secret ni bearer tokens en logs, capturas, código de ejemplo o interfaz web.
- No presentar un HTTP 200 como orden creada sin validar el cuerpo. No repetir a ciegas un POST cuyo resultado sea incierto.
- Mantener ADMIN como diagnóstico del mock local: restringir a loopback, redactar campos sensibles antes de guardar trazas y actualizar FIX_API_DOCS.md solo con discrepancias técnicas verificadas.
- Los eventos de aplicación van a `app/storage/logs/integration.log` con `correlation_id`, `event` y `source`. No añadir cuerpos HTTP, contraseñas, secretos ni bearer tokens; conservar el vínculo con las trazas de ADMIN.
- Probar el mismo comando dos veces contra el mismo proceso del mock y comprobar que la segunda ejecución conserva ID y total sin crear otra orden.
- El README final de entrega debe ser breve, incluir comando exacto, decisiones, exclusiones deliberadas y declaración de uso de IA.
- La consola interactiva `vittles:console` es una ampliación: comparte cuentas y servicios con la web, pero el comando evaluable `vittles:order` debe seguir funcionando sin login humano. ADMIN de consola conserva restricciones de entorno local, mock loopback y rol administrador.
