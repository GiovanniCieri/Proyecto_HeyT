# Propuesta de páginas — integración Vittles POS

**Estado:** implementado en Laravel + Blade. El ejercicio original exige una integración ejecutable por comando; estas páginas son una **interfaz visual adicional**. No sustituyen el comando ni amplían la regla de negocio: una sola sede recibe la orden en cada operación.

## Mapa de páginas

Nueva orden → Sedes y menús → Nueva orden → Resultado → Nueva orden.

### 1. Nueva orden

**Objetivo:** preparar una orden segura y comprensible antes de enviarla.

- Selector de la **única location destino**; el resto de las sedes se consulta pero no recibe órdenes.
- Selector de producto tomado del menú de esa sede, con disponibilidad visible. Se evita elegir automáticamente nombres parecidos.
- Cantidad fija 2, indicada como parte de la consigna.
- Vista previa del precio unitario y un **estimado**, claramente distinto del total definitivo devuelto por Vittles.
- Resumen de cobertura: 5 sedes encontradas, 5 menús intentados, 4 obtenidos y 1 sin acceso en el mock actual.
- Acción principal: “Crear orden”. Deshabilitada si el menú no se pudo leer o el ítem no está disponible.

**Estados:** carga, sede inexistente, menú 403, producto no disponible, error de contrato. El usuario ve la causa antes de intentar crear.

### 2. Sedes y menús

**Objetivo:** hacer visible que la integración recorrió todas las locations y sus menús.

- Lista de sedes con ID, nombre y estado del menú.
- Panel de detalle con productos, precio por sede y disponibilidad.
- La sede inactiva muestra “menú sin acceso (403)” en vez de una lista vacía que podría confundirse con un menú válido.
- Seleccionar una sede aquí puede llevarla al formulario de Nueva orden; no crea nada desde esta página.

**Estados:** menú cargado, menú vacío válido, menú sin acceso, error transitorio tras reintentos. El precio se muestra para esa sede; no se comparte como si fuera global.

### 3. Resultado

**Objetivo:** dejar inequívoco el efecto de la operación.

- Estado destacado: CREADA, YA EXISTÍA, NO CREADA, FALLÓ o RESULTADO INCIERTO.
- Sede, ítem, cantidad 2, ID de orden y **total devuelto por Vittles** cuando existe una orden.
- Breve secuencia de verificaciones: autenticación, lectura de sedes/menús, validación del producto y consulta/creación de la orden.
- Cuando el estado es incierto, mostrar la referencia completa para conciliación y no ofrecer un reenvío ciego de la creación.
- Una reconsulta del mismo pedido puede mostrar YA EXISTÍA con el mismo ID y total.

## Principios visuales

El diseño usa una navegación corta de tres secciones, un formulario principal y un panel de detalle contextual. Los estados de orden se expresan con **texto además de color**. La pantalla de resultado prioriza ID y total; la página de sedes muestra datos operativos sin convertirse en un dashboard de métricas. En pantallas angostas, los paneles se apilan y se conserva la acción principal visible.

## Límite de la demo

El prototipo visual previo solo simulaba el recorrido. La implementación actual en `app/` sí consulta el mock y puede crear una orden. Comparte `OrderService` con Artisan y mantiene el entregable obligatorio del ejercicio. Los datos de la página de selección se refrescan al enviar el formulario, por lo que la validación final usa el menú vigente del POS.
