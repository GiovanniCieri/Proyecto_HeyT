# TESTING_DELIVERY — Pruebas y entrega del ejercicio

**Estado:** entrega local armada; ampliar pruebas de fallos externos si pasa a producción  
**Pertenece al ejercicio:** sí  
**Dependencias:** POS_CLIENT y ORDER_FLOW

## Objetivo

Demostrar los requisitos con pruebas significativas y entregar el código de forma que otra persona pueda ejecutarlo sin preguntar por pasos faltantes.

## Tareas

- [x] Probar disponibilidad y ausencia de POST ante producto agotado; verificar precio string en el fixture.
- [x] Probar paginación y 200 con REJECTED mediante respuestas controladas.
- [x] Probar respuesta incompleta del POST, conciliación por referencia y ausencia de reenvío.
- [ ] Agregar pruebas deterministas específicas de renovación 401, 429 y 500.
- [x] Levantar un mock nuevo y correr dos veces el mismo comando sin reiniciarlo entre corridas.
- [x] Confirmar cinco locations, cinco intentos de menú y una sola orden final para la sede elegida.
- [x] Escribir README breve con comandos exactos, configuración, decisiones, exclusiones e IA.
- [x] Documentar el límite de idempotencia concurrente.
- [ ] Revisar contenido de Git antes de publicarlo y excluir tokens, secretos, cachés y archivos temporales.
- [ ] Crear enlace de repo o ZIP de entrega cuando se decida el canal de envío.

## Criterios de aceptación

- El comando documentado funciona con PHP 8.2+, Composer y el mock en Python 3.9+.
- La segunda ejecución informa el mismo ID y total, sin crear una orden adicional.
- Una persona puede explicar cada módulo, prueba y decisión en la entrevista.
- La entrega contiene los tres elementos pedidos en docs/ENUNCIADO.md.
