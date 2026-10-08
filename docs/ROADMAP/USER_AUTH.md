# USER_AUTH — Acceso local a la demo web

**Estado:** implementado para demo local; no pertenece al ejercicio CLI

**Dependencia:** Laravel + Blade y SQLite local

## Objetivo

Mostrar login o registro antes de la página de inicio. Separar esta sesión de usuario de la autenticación OAuth entre el backend y Vittles.

## Política elegida

- Registro abierto solo desde loopback y con `APP_ENV=local`. La primera cuenta recibe `is_admin` para abrir el laboratorio de API; las siguientes son usuarios normales. Nunca crear una cuenta de prueba en la base local del usuario.
- El login funciona con email y contraseña. Laravel guarda el hash de contraseña y una sesión de archivo; el navegador no recibe credenciales del POS.
- La página de inicio, sedes, resultado y creación de órdenes requieren sesión. ADMIN exige además `is_admin` y las restricciones locales previas.
- No hay confirmación de email ni recuperación de contraseña en esta demo. Antes de exponer el sitio fuera de localhost habría que definir alta de usuarios, roles, recuperación y controles de acceso de producción.

## Tareas

- [x] Agregar modelo de usuario y migraciones compatibles con la SQLite ya inicializada.
- [x] Crear vistas de login y registro en el estilo visual de la demo.
- [x] Validar inputs, contraseña mínima, email único y usar hashing de Laravel.
- [x] Regenerar sesión tras login o registro; invalidarla tras logout.
- [x] Limitar intentos de login y registro, y mantener CSRF en los formularios.
- [x] Proteger operaciones web con `auth` y ADMIN con `is_admin`.
- [x] Probar redirección de invitados, primera y segunda cuenta, logout/login y cierre del registro fuera de local.

## Criterio de aceptación

Una persona sin sesión llega a `/login`; después de registrarse o ingresar llega a `/`. La primera cuenta puede abrir `/admin`; las demás reciben 403. El comando Artisan del ejercicio sigue funcionando sin login web.
