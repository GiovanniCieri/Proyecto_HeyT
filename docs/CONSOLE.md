# Consola interactiva

Desde la raíz del repositorio, con la migración aplicada y el mock en marcha:

```bash
# Windows (PowerShell o CMD)
.\scripts\console.cmd

# macOS/Linux
bash scripts/console.sh
```

Los accesos directos entran en `app/` y abren el mismo comando Artisan. También se puede ejecutar `php artisan vittles:console` manualmente desde `app/`. El mock se inicia con `scripts/start.ps1` o `bash scripts/start.sh` en otra terminal; la consola interactiva no requiere que la web esté abierta.

En Windows, si se abre `scripts/console.cmd` con doble clic, la ventana espera una tecla después de salir o de mostrar un error. Si no hay una terminal interactiva disponible, el comando informa el problema y termina sin repetir el menú.
El acceso busca PHP 8.2 o superior entre las instalaciones de `PATH` y ejecuta esa ruta explícitamente. Esto evita que un PHP 7.4 colocado primero por otra aplicación rompa Laravel. Si no encuentra una versión compatible, muestra el requisito antes de cargar Artisan.

La primera pantalla permite **Ingresar**, **Registrarse**, leer el README, ver el comando exacto del ejercicio o salir. Usa las mismas cuentas SQLite que `/login` y `/register`; la contraseña se pide sin eco en la terminal. El registro está habilitado solo en entorno local. La primera cuenta es ADMIN, como en la web. La sesión dura mientras el menú está abierto y no inicia una sesión del navegador.

En una terminal real se abre una pantalla exclusiva: encabezado y navegación arriba, contenido y preguntas debajo. Cada opción redibuja la pantalla; al terminar, Enter devuelve al menú. Los pedidos y las trazas se paginan de seis en seis para mantener visible la navegación; se elige una fila por número y se cambia de página con `S` o `A`. Al salir se restaura la pantalla anterior de la terminal. En tests o salidas no TTY se conserva el flujo de texto normal.

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
