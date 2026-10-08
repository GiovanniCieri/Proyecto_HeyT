# MULTI_ITEM_WEB — Una orden web con varios productos

**Estado:** implementado para demo local  
**Pertenece al ejercicio:** no; el comando obligatorio conserva un producto y cantidad 2  
**Dependencias:** ORDER_FLOW, WEB_UI

## Objetivo

Permitir seleccionar varios productos del menú de una sola location y enviarlos en un único POST de orden.

## Tareas

- [x] Mostrar los productos disponibles y una cantidad 0–20 por línea; 0 excluye la línea.
- [x] Validar en servidor que haya entre 1 y 20 productos seleccionados, cada uno con 1–20 unidades, disponible y perteneciente al menú de la location.
- [x] Rechazar IDs duplicados y evitar POST si falla la validación.
- [x] Incluir las líneas ordenadas canónicamente en `client_ref`, de modo que cambiar el orden de selección no cree otra intención.
- [x] Mantener la referencia anterior para una sola línea y el comando existente.
- [x] Guardar y mostrar todas las líneas en el historial y el comprobante; el total final procede del POS.
- [x] Probar un único POST con dos líneas e idempotencia al invertir su orden.

## Criterio de aceptación

Una selección de dos productos produce una sola orden con dos líneas; repetirla con el mismo identificador no hace un segundo POST. El comando del enunciado mantiene su formato y cantidad fija.
