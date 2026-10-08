# RESULT_FLOW — Comprobante e historial

**Estado:** implementado para demo local  
**Pertenece al ejercicio:** no; interfaz web solicitada  
**Dependencias:** WEB_UI, ORDER_HISTORY

## Objetivo

Hacer que Resultado sea el comprobante de la operación recién realizada y que Pedidos sea el lugar donde se vuelve a consultar un pedido confirmado.

## Tareas

- [x] Quitar Resultado de la navegación permanente.
- [x] Mostrar el comprobante una sola vez tras el POST mediante sesión flash.
- [x] Redirigir `/result` a Pedidos cuando ya no haya una operación reciente.
- [x] Añadir detalle persistente por `client_ref` desde el historial local.
- [x] Mostrar todas las líneas y cantidades en el comprobante y el detalle.
- [x] Probar que una segunda visita a `/result` vaya al historial.

## Criterio de aceptación

El comprobante no aparenta ser una pantalla de estado en vivo. Un pedido confirmado puede revisarse luego desde Pedidos; un rechazo o resultado incierto se muestra tras el POST sin ingresar al historial de confirmados.
