# ADMIN_DIAGNOSTICS — Diagnóstico local de la API

**Estado:** implementado para el mock local

**Dependencias:** cliente HTTP, catálogo y órdenes existentes

**Alcance:** ampliación web solicitada por el usuario; no es parte obligatoria del enunciado

## Objetivo

Poder probar los endpoints del mock, observar las peticiones y respuestas reales, y reunir evidencia para mantener `FIX_API_DOCS.md` sin alterar la documentación original.

## Tareas

- [x] Registrar cada intento HTTP del cliente, incluyendo autenticación, duración, estado, encabezados útiles, cuerpo JSON y fallos de conexión.
- [x] Redactar credenciales, tokens y campos personales conocidos antes de persistir la traza.
- [x] Separar las trazas de pruebas automatizadas de las trazas de la demo y limitar el tamaño del archivo local.
- [x] Crear `/admin` con filtros por todas, errores y POST, y detalle expandible de solicitud y respuesta.
- [x] Permitir probar autenticación, paginación de locations, catálogo completo, menú individual, búsqueda y detalle de órdenes, rechazo controlado y creación idempotente.
- [x] Restringir ADMIN a `APP_ENV=local`, URL del mock en localhost y acceso desde loopback.
- [x] Verificar en el navegador un 403 de menú inactivo, un 200 `REJECTED` y una creación seguida de `EXISTING` con el mismo ID.

## Criterio de aceptación

Una discrepancia debe poder reconstruirse desde una traza concreta: método, ruta, parámetros, cuerpo redactado, HTTP, respuesta y número de intento. Las pruebas de órdenes aceptadas reutilizan `OrderService`; los POST no se reintentan a ciegas. La corrección del contrato se escribe manualmente en `FIX_API_DOCS.md` tras revisar la evidencia.

## Límite deliberado

Es un laboratorio local, no un panel de observabilidad de producción. La redacción cubre los campos sensibles conocidos del mock; no sustituye una política de datos para un POS real. No hay usuarios, roles, exportación ni almacenamiento centralizado.
