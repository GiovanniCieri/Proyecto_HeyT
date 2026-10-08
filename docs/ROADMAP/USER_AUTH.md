# USER_AUTH — Cuentas, login y registro de usuarios

**Estado:** diferido; ampliación fuera del ejercicio  
**Dependencia:** decisión de construir una aplicación web con backend y almacenamiento

## Objetivo

Si el sistema evoluciona a una herramienta multiusuario, definir y construir autenticación de personas. No confundirla con POST /oauth/token de Vittles: ese token autentica a la integración ante el POS, no a los usuarios de la aplicación.

## Decisiones previas obligatorias

- [ ] Determinar quién puede registrarse: público, invitación o alta por administrador. Para un backoffice, no asumir registro público.
- [ ] Definir roles y permisos, especialmente quién puede crear órdenes o ver datos de todas las sedes.
- [ ] Elegir backend, base de datos, proveedor de identidad o librería mantenida; no inventar criptografía propia.
- [ ] Definir sesiones, duración, cierre de sesión, recuperación de cuenta y verificación de email si aplica.
- [ ] Definir protección contra fuerza bruta, CSRF, abuso de registro y exposición de datos.

## Tareas posteriores

- [ ] Modelar usuarios y permisos con migraciones y restricciones.
- [ ] Implementar login, registro según política elegida, logout y recuperación.
- [ ] Proteger rutas y operaciones de creación del lado del servidor.
- [ ] Registrar acciones sensibles sin guardar contraseñas ni tokens en logs.
- [ ] Probar controles de acceso, sesiones expiradas y flujos de error.

## Criterios de aceptación

Un usuario sin permiso no puede crear órdenes aunque llame directamente al backend. Las credenciales de Vittles permanecen del lado servidor. La política de registro queda explícita y probada.
