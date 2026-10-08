# POS_CLIENT — Cliente de la API Vittles

**Estado:** implementado en Laravel 12  
**Pertenece al ejercicio:** sí  
**Dependencia:** API_FIX, al menos los endpoints necesarios verificados

## Objetivo

Construir un cliente pequeño que se autentique con Vittles, obtenga todas las locations y haga un intento de lectura del menú de cada una. Este roadmap cubre autenticación del POS, no login de usuarios.

## Tareas

- [x] Usar servicios PHP en `app/app/Services/Vittles` reutilizados por Artisan y Blade; el mock requiere Python 3.9+.
- [x] Leer client_id y client_secret desde `.env`; no imprimirlos.
- [x] Implementar POST /oauth/token y el campo real `expires`.
- [x] Renovar el token una vez ante 401 de una ruta protegida.
- [x] Recorrer next_cursor hasta reunir todas las locations, con protección ante ciclos o páginas inválidas.
- [x] Consultar el menú de cada location; registrar el 403 de la sede inactiva sin inventar un menú vacío.
- [x] Normalizar solo los formatos comprobados de menuItems, precio y disponibilidad.
- [x] Definir timeouts y reintentos acotados para lecturas ante 500/429, respetando el encabezado observado.
- [x] Entregar al flujo de órdenes arrays validados y errores distinguibles.

## Criterios de aceptación

- El cliente descubre las cinco sedes del mock mediante las tres páginas observadas.
- Intenta cinco lecturas de menú y distingue cuatro menús obtenidos de uno inaccesible.
- Un fallo transitorio no provoca un bucle infinito ni impide ocultamente el error.
- Los secretos no aparecen en salida, logs ni excepciones mostradas al usuario.
