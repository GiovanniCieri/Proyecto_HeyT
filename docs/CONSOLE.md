# Consola interactiva

Desde `app/`, con la migración aplicada y el mock en marcha:

```bash
php artisan vittles:console
```

La primera pantalla permite **Ingresar**, **Registrarse**, leer el README, ver el comando exacto del ejercicio o salir. Usa las mismas cuentas SQLite que `/login` y `/register`; la contraseña se pide sin eco en la terminal. El registro está habilitado solo en entorno local. La primera cuenta es ADMIN, como en la web. La sesión dura mientras el menú está abierto y no inicia una sesión del navegador.

## Menú después de ingresar

| Opción | Acción |
| --- | --- |
| Nueva orden | Elige una location, uno o varios productos y cantidades. Muestra un resumen y pide confirmación antes de llamar a `OrderService::placeMany`. |
| Sedes y menús | Recorre todas las locations, muestra estado de cada menú y deja inspeccionar o actualizar un menú. |
| Pedidos confirmados | Lista hasta 100 confirmaciones del historial **local**, filtra por location y muestra el detalle guardado. |
| Ver pedido en Vittles | Pide un ID y ejecuta `vittles:show`, que consulta el POS en vivo. |
| README | Resume alcance, decisiones, exclusiones e IA. |
| Ejecutar comando del ejercicio | Muestra `php artisan vittles:order loc_1001 "Buffalo Wings (12)"` y, si se confirma, invoca ese comando desde el menú. |
| ADMIN · Diagnóstico | Solo cuenta administradora, entorno local y mock loopback. Prueba autenticación, locations, menús, búsqueda, lectura, rechazo y creación; revisa trazas redactadas. |
| Cerrar sesión / Salir | Vuelve al menú inicial o termina el proceso. |

La opción de pedido interactivo y la web comparten la clave por cuenta local. Para la misma sede, combinación de productos, cantidades e identificador, recuperan la misma orden. El comando evaluable directo conserva su clave y cantidad 2 independientemente de las cuentas. Las lecturas de catálogo se mantienen en memoria dentro del menú hasta elegir “Actualizar”; `OrderService` vuelve a validar el menú antes de crear.

La consola guarda eventos en `app/storage/logs/integration.log`, con `correlation_id`, `event` y método `source`; las llamadas a Vittles quedan también en las trazas de ADMIN. El token, el secreto y las contraseñas no se imprimen. Si se necesita automatización o redirección de salida, usar los comandos directos: `vittles:console --no-interaction` no abre un menú.
