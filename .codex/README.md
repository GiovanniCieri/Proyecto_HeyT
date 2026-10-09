# Agentes de Codex para este proyecto

Esta carpeta contiene perfiles de subagentes, no agentes que corren automáticamente ni componentes de la aplicación. Las instrucciones comunes viven en AGENTS.md en la raíz del proyecto. Los perfiles están en .codex/agents y se usan solo al delegar una tarea concreta.

| Perfil | Responsabilidad | Puede editar |
| --- | --- | --- |
| api_auditor | Ejecutar una matriz de requests HTTP por endpoint y guardar evidencia redactada | Sí, solo script y capturas de auditoría |
| integration_builder | Implementar una tarea acotada del MVP de integración | Sí |
| quality_reviewer | Revisar riesgos, pruebas y garantías afirmadas | No |
| docs_curator | Mantener roadmaps, contrato corregido y bitácora | Sí, solo documentación |
| web_designer | Mantener la demo Blade y su fidelidad visual sin alterar el flujo POS | Sí, solo interfaz |

No fijamos modelos ni permisos globales en un config.toml del proyecto. Cada perfil hereda la configuración activa de la sesión; el auditor puede escribir únicamente su procedimiento y evidencia, mientras que el revisor de calidad es de lectura. Antes de delegar, indicar objetivo, archivos pertinentes y resultado esperado. No abrir varios agentes para una tarea corta o dependiente.

El archivo AGENTS.md antiguo de argenfilv2 corresponde a otro repositorio Angular y no se usa aquí.
