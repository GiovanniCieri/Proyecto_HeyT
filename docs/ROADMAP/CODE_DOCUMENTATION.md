# CODE_DOCUMENTATION — Guía de lectura junto al código

**Estado:** implementado  
**Pertenece al ejercicio:** apoyo para explicar y defender la integración; no cambia su alcance funcional  
**Dependencias:** POS_CLIENT, ORDER_FLOW, TESTING_DELIVERY

## Objetivo

Dejar junto a cada función su propósito y la razón de la decisión, especialmente donde el contrato observado de Vittles difiere de `API_DOCS.md`.

## Tareas

- [x] Documentar los métodos de controllers y servicios sin modificar su comportamiento.
- [x] Describir cada ruta web y el comando de ejemplo de rutas console.
- [x] Explicar qué protege cada caso de prueba y por qué se simula el POS.
- [x] Registrar la pasada en `docs/CHANGES`.
- [x] Verificar sintaxis y ejecutar la suite de tests.

## Criterios de aceptación

- Cada función en controllers, servicios y tests tiene una explicación próxima al código.
- Cada ruta tiene un comentario con su finalidad y límite de acceso o fuente de datos.
- Los comentarios distinguen el MVP de la demo web y no prometen idempotencia distribuida.
- La suite sigue pasando sin cambios de comportamiento.
