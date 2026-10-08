# AUTH-001 — Login y registro de la demo

**Fecha:** 2026-10-08

**Archivos:** `AuthController.php`, `User.php`, migraciones, rutas, vistas `auth/login.blade.php` y `auth/register.blade.php`, layout, CSS, PHPUnit, `AuthFlowTest.php`, README y roadmap `USER_AUTH.md`.

**Motivo:** el usuario pidió que login/registro precedan a la página de inicio. La autenticación de personas sigue separada del token de la integración Vittles.

**Cambios:** registro limitado a localhost y entorno local; primera cuenta administradora; sesiones Laravel y contraseñas hasheadas; login, logout, validación, CSRF y límites de intentos. Las rutas web requieren sesión, mientras que el comando Artisan conserva su acceso independiente.

**Verificación:** migración aplicada a la SQLite local existente sin borrar tablas ni cuentas. `php artisan test`: 10 pruebas y 49 aserciones. Pruebas en SQLite en memoria: invitados redirigidos a login, primera cuenta administradora, segunda cuenta sin ADMIN, logout/login y registro inaccesible fuera de local. Páginas de login y registro revisadas visualmente en el navegador; el registro no tiene desbordamiento horizontal a 841 px. No se creó una cuenta de prueba en la base local del usuario.

**Pendiente deliberado:** recuperación de contraseña, verificación de correo, roles más finos, política de alta para despliegue y pruebas de carga concurrente en el primer registro.
