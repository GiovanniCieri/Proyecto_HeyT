# INTERACTIVE_CONSOLE — Demo completa en terminal

**Estado:** implementado para demo local  
**Pertenece al ejercicio:** no; el comando evaluable sigue siendo `vittles:order`  
**Dependencias:** USER_AUTH, POS_CLIENT, ORDER_FLOW, ORDER_HISTORY, ADMIN_DIAGNOSTICS, RESULT_FLOW

## Objetivo

Permitir recorrer desde una terminal las mismas funciones de la demo web, con navegación clara y sin exigir una sesión del navegador.

## Tareas

- [x] Mostrar portada, menú inicial y salida sin efectos secundarios.
- [x] Ingresar y registrar cuentas locales con contraseña oculta, validación y misma tabla `users` que la web.
- [x] Mantener ADMIN solo para la cuenta administradora en entorno y mock locales.
- [x] Explorar todas las locations y menús, con actualización explícita del catálogo.
- [x] Crear un pedido con uno o varios productos, cantidades 1–20, vista previa y confirmación antes del POST.
- [x] Mostrar comprobante, historial local filtrado, detalle y consulta en vivo por ID.
- [x] Mostrar README, comando exacto del ejercicio y opción de invocar ese comando desde el menú.
- [x] Ofrecer pruebas de endpoints y trazas redactadas en ADMIN.
- [x] Registrar acciones con método fuente y ocultar contraseñas, secreto y bearer token.
- [x] Probar registro, login, roles, pedido multítem y redacción del token.

## Criterios de aceptación

`php artisan vittles:console` abre un menú en una terminal interactiva. Las cuentas creadas allí funcionan en la web y viceversa. La opción “Ejecutar comando del ejercicio” llama a `vittles:order`, conservando un producto y cantidad 2. `--no-interaction` falla con una instrucción para usar el comando directo. ADMIN no aparece para usuarios comunes ni con un POS remoto.

## Límites

El menú no crea una sesión web; se ingresa de nuevo al abrir otra terminal. El historial listado es local, no todas las órdenes remotas. El mock puede limitar requests al recorrer o probar repetidamente endpoints.
