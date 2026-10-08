# WEB_UI — Interfaz web de la integración

**Estado:** demo local implementada; ampliación voluntaria al ejercicio  
**Dependencia:** flujo de integración funcional y reutilizable

**Referencia visual:** [ESTILO_HEYTRUFFLE.md](../ESTILO_HEYTRUFFLE.md)

## Objetivo

Mantener las páginas implementadas en Laravel + Blade: Nueva orden, Sedes y menús, y Resultado. La interfaz reutiliza el servicio del comando Artisan exigido por docs/ENUNCIADO.md.

## Decisiones previas

- [x] Proponer stack web: Laravel + Blade si se decide construir la demo; conservar un comando Artisan ejecutable por separado. Angular no aporta suficiente valor a estas tres vistas.
- [x] Stack definitivo: Laravel 12 + Blade, elegido por el usuario.
- [ ] Comparar capturas de escritorio y móvil con heytruffle.ai antes de dar por terminado el estilo.
- [x] Conservar credenciales del POS en el backend; el navegador no recibe client_secret ni bearer tokens.
- [x] Reutilizar `OrderService` desde CLI y web.
- [x] Usar búsqueda y conciliación del proveedor; la persistencia distribuida queda fuera de la demo.

## Tareas posteriores

- [x] Página Nueva orden: sede única, ítem del menú, cantidad fija 2, disponibilidad y estimado.
- [x] Página Sedes y menús: cinco lecturas visibles, incluido el 403 de la sede inactiva.
- [x] Página Resultado: creada, existente, recuperada, rechazada e incierta, con ID y total cuando proceda.
- [x] Comprobar adaptación a 390 px sin desbordamiento horizontal.
- [ ] Completar auditoría de accesibilidad y comparación fina de capturas con la web de referencia.
- [x] Recorrido manual de la UI contra el mock: segunda operación recuperó `ord_5501`.

## Criterio de aceptación

La interfaz nunca permite crear una orden en otra sede que no sea la seleccionada y nunca presenta un estado incierto como “no creada”. La entrega CLI del ejercicio sigue funcionando por separado.
