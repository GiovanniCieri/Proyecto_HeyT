# Comandos de la integración

Ejecutar desde `app/`, con el mock levantado y las credenciales configuradas en `.env`.

| Comando | Qué hace | Fuente |
| --- | --- | --- |
| `php artisan vittles:console` | Abre el [menú interactivo](CONSOLE.md) con acceso local, pedidos, historial, README y diagnóstico ADMIN. | Servicios compartidos con la web |
| `php artisan vittles:order loc_1001 "Buffalo Wings (12)"` | Autentica, pagina todas las locations, intenta leer cada menú, valida ese producto en `loc_1001`, busca la referencia y crea **una orden de 2** solo si no existe. Imprime estado, ID y total. | Vittles |
| `php artisan vittles:show ord_5501` | Consulta una orden concreta por ID, en vivo. Si se reinició el mock, un ID anterior puede devolver 404. | Vittles |
| `php artisan vittles:orders` | Muestra hasta 100 órdenes confirmadas que registró esta integración. | Historial SQLite local |
| `php artisan vittles:orders loc_1001` | Igual, filtrado por location. | Historial SQLite local |

`vittles:orders` **no enumera todas las órdenes de Vittles**: el mock no tiene ese endpoint. La búsqueda remota `GET /v1/orders?client_ref=...` solo sirve cuando se conoce la referencia. Para iniciar una nueva intención con el mismo producto del comando obligatorio, usar `--request-key=otra-clave`.

La web permite varios productos y cantidades variables; son una ampliación. El comando exigido por el enunciado conserva exactamente un nombre de producto y cantidad 2.
