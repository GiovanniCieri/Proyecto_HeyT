# DOC-002 — README actualizado con la auditoría

**Fecha:** 2026-10-08  
**Estado:** implementado

## Motivo

El README de entrega ya enlazaba el contrato corregido, pero no explicaba dónde ver el proceso de descubrimiento de las discrepancias en la web.

## Archivos cambiados

- `README.md`: resume la entrega y añade `/audit` a las demos opcionales; distingue evidencia observada de conclusiones por lectura del mock e indica que navegarlo no hace requests al POS.
- `docs/ROADMAP/CONTRACT_AUDIT_GUIDE.md`: registra el enlace desde el README.
- `docs/CHANGES/README.md`: mantiene la secuencia de pasadas.

## Verificación

- Revisión del comando exacto, decisiones con sus motivos, exclusiones e IA requeridos en el README; texto de unas 300 palabras.
- `git diff --check` sin errores de formato.

## Pendientes

- La reproducción HTTP de token vencido y 429 sigue en `docs/ROADMAP/API_FIX.md`; el README no la presenta como ejecutada.
