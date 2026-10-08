# API_FIX — Verificar y corregir el contrato de Vittles

**Estado:** contrato endpoint por endpoint con evidencia; quedan pruebas dirigidas de expiración y rate limit
**Alcance:** documentación de la API ficticia, no código de la integración  
**Entrada:** docs/Docs_API/vittles/API_DOCS.md, mock_server.py y README.md  
**Salida:** docs/Docs_API/vittles/FIX_API_DOCS.md

## Objetivo

Comparar cada afirmación técnica de API_DOCS.md con el comportamiento real del mock y escribir una versión corregida, reproducible y coherente del contrato. Mantener intactos los tres archivos originales.

## Tareas

- [x] Levantar el mock y reproducir la integración por comando (`py -3 docs/Docs_API/vittles/mock_server.py`).
- [x] Revisar payload de autenticación y respuesta `expires`; el vencimiento de 90 segundos se confirma además en el código.
- [x] Verificar paginación y cinco sedes, incluida la inactiva.
- [x] Verificar campo `menuItems`, variantes de tipos, 403 y el 500 transitorio en el código.
- [x] Verificar creación aceptada, header de contexto y respuesta `REJECTED`.
- [x] Verificar búsqueda por `client_ref` y su ausencia de idempotencia del lado servidor.
- [x] Documentar límite, encabezado y zona horaria según el código del mock.
- [x] Escribir el contrato corregido en FIX_API_DOCS.md, con tabla de discrepancias, ejemplos y procedencia.
- [ ] Agregar transcripciones breves request/response de token vencido y 429 tras una prueba dirigida.

## Evidencia disponible

La exploración anterior observó expires=90, páginas de 2/2/1 locations, menuItems, 403 en la sede inactiva, 200 con REJECTED al omitir X-Vittles-Location y dos órdenes diferentes con el mismo client_ref. En esta pasada se repitieron requests directos a token, locations, menú activo/inactivo/desconocido, búsqueda y lectura de orden inexistente, y rechazos por header y lista vacía. La implementación confirmó el flujo de cinco sedes y cinco menús, y creó/recuperó órdenes contra el mock. FIX_API_DOCS distingue observación directa de lectura del código.

## Criterios de aceptación

- Cada endpoint de API_DOCS.md tiene una descripción corregida o una nota explícita de comportamiento no comprobado.
- Ninguna corrección se basa solo en una suposición. El documento distingue observación de inferencia.
- FIX_API_DOCS.md contiene contrato y discrepancias, no una lista de tareas ni decisiones de producto.
- API_DOCS.md, README.md y mock_server.py conservan su contenido original.

## Fuera de alcance

Modificar el mock, corregir una API real de producción o implementar el cliente de integración.
