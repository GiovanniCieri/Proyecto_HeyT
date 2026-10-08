# Roadmaps del proyecto

El archivo docs/ENUNCIADO.md define la entrega evaluable. Los roadmaps separan esa entrega de las ampliaciones que podrían convertirla después en un sistema web. Cada roadmap tiene objetivo, tareas, dependencias y aceptación. Marcar una casilla solo cuando exista evidencia.

| Orden | Roadmap | Estado | Pertenece al ejercicio |
| --- | --- | --- | --- |
| 1 | [API_FIX.md](API_FIX.md) | Contrato corregido; auditoría ampliable | Sí: verificar el contrato técnico |
| 2 | [POS_CLIENT.md](POS_CLIENT.md) | Implementado | Sí: autenticación POS, locations y menús |
| 3 | [ORDER_FLOW.md](ORDER_FLOW.md) | Implementado | Sí: una orden, idempotencia y resumen |
| 4 | [TESTING_DELIVERY.md](TESTING_DELIVERY.md) | Entrega local armada | Sí: pruebas y entregables |
| 5 | [WEB_UI.md](WEB_UI.md) | Demo implementada | No: páginas adicionales |
| 6 | [USER_AUTH.md](USER_AUTH.md) | Implementado para demo local | No: cuentas, login y registro |
| 7 | [ADMIN_DIAGNOSTICS.md](ADMIN_DIAGNOSTICS.md) | Implementado para mock local | No: diagnóstico web solicitado |
| 8 | [OBSERVABILITY.md](OBSERVABILITY.md) | Implementado para demo local | No: logs y correlación solicitados |
| 9 | [ORDER_HISTORY.md](ORDER_HISTORY.md) | Implementado para demo local | No: pedidos por location |
| 10 | [WEB_README.md](WEB_README.md) | Implementado para demo local | No: página explicativa |

La demo WEB_UI y USER_AUTH fueron pedidos expresamente y reutilizan el flujo de integración. Siguen siendo ampliaciones: el enunciado solo pide autenticación con Vittles, no cuentas de personas. Otros roadmaps futuros, solo si surge la necesidad: despliegue, observabilidad de producción y conexión a un POS real.

Las decisiones y resultados de cada pasada se registran en el [historial ordenado](../CHANGES/README.md) con fecha, secuencia e identificador. Las discrepancias técnicas de Vittles se documentan en FIX_API_DOCS.md; ese archivo no sustituye a los roadmaps.
