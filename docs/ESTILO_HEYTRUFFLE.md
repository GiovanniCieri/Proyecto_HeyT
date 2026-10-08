# Propuesta visual basada en heytruffle.ai

**Estado:** implementado en la demo Blade; comparación móvil pendiente. **Referencia observada:** https://heytruffle.ai/ el 2026-10-08. La web de referencia puede cambiar.

## Rasgos comprobados

| Elemento | Observación en la web real | Aplicación propuesta |
| --- | --- | --- |
| Marca | Logotipo tipográfico blanco `heytruffle` | Cabecera simple, sin menú lateral de administración |
| Color principal | Naranja `#ef7200` | Botón principal, selección activa y detalles puntuales |
| Texto claro | Crema `#f6f3ec` | Títulos y textos sobre fondo oscuro |
| Fondo | Carbón cálido y gradientes difusos azul, violeta y naranja en el hero | Hero de la integración y fondo de la pantalla inicial |
| Tipografía | Titulares en Gowun Batang; cuerpo/interfaz en Google Sans e Inter (con alternativas del sistema) | Título editorial y controles legibles |
| Componentes | Botones e inputs tipo píldora; tarjetas oscuras redondeadas; bordes suaves | Formulario compacto y panel de resultado |
| Composición | Mucho espacio, titulares grandes, numeración de secciones `01`, `02`, `03` | Recorrido visual de selección, comprobación y resultado |

Los valores de color y familias tipográficas se leyeron de los estilos computados y variables CSS de la página. Las proporciones, imágenes y animaciones pueden variar por tamaño de pantalla; hay que contrastarlas en móvil antes de implementar.

## Adaptación al ejercicio

La web pública vende un servicio de concierge y afirma que el cliente no recibe un dashboard para gestionar. Por eso la interfaz del ejercicio se plantea como **demo local de una operación**, con tres vistas breves: `01 Nueva orden`, `02 Sedes y menús`, `03 Resultado`. Es una herramienta de demostración del flujo POS, no un panel de gestión atribuido a heytruffle.

1. **Nueva orden.** Hero con título serif: «Una orden, directo al POS». Debajo, selector de location, selector de item por nombre exacto, cantidad fija `2`, estimado rotulado como tal y botón naranja «Crear orden». Un resumen pequeño informa cuántas sedes y menús se consultaron.
2. **Sedes y menús.** Lista compacta de todas las sedes, cada una con estado de lectura del menú. El 403 de la sede inactiva se muestra explícitamente. El detalle enseña disponibilidad y precio de *esa* sede.
3. **Resultado.** Estado textual grande, ID y total devuelto por Vittles. Si la misma solicitud ya existía, se muestra `YA EXISTÍA` con el mismo ID; si el resultado es incierto, se indica que requiere conciliación y se evita un segundo POST automático.

## Arquitectura recomendada

Primero, terminar una integración ejecutable por comando y demostrar dos ejecuciones consecutivas sin una segunda orden. La interfaz web usa el mismo servicio de aplicación que el comando. Si se elige Laravel por familiaridad del autor, usar **Artisan + cliente HTTP + Blade** en un solo proyecto. Blade cubre estas tres vistas sin necesitar una segunda aplicación Angular, su build, routing y contrato frontend/backend. El navegador nunca recibe `client_secret` ni bearer tokens.

El entorno local comprobado tiene PHP 8.2.12 y Composer 2.8.12. Con ese PHP, Laravel 12 es compatible; Laravel 13 requiere PHP 8.3 o superior. Al iniciar el proyecto hay que fijar explícitamente la versión de framework y comprobar su soporte, o actualizar PHP si se elige Laravel 13.

La interfaz visual y la posible autenticación de usuarios son ampliaciones. El enunciado pide autenticarse contra Vittles, no implementar login y registro propios. Si el tiempo es corto, entregar el comando correcto y capturas/mockups de la demo visual como propuesta.

## Alcance de la fidelidad visual

Reproducir paleta, tipografía, ritmo, superficies, botones y tono. No copiar el texto comercial ni presentar la demo como producto oficial de heytruffle. La fidelidad visual debe verificarse por comparación de capturas en escritorio y móvil; este documento es una especificación previa, no una afirmación de equivalencia píxel a píxel.
