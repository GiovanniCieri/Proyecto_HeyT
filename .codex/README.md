# Agentes de Codex para este proyecto

Esta carpeta contiene perfiles de subagentes, no agentes que corren automáticamente ni componentes de la aplicación. Las instrucciones comunes viven en AGENTS.md en la raíz del proyecto. Los perfiles están en .codex/agents y se usan solo al delegar una tarea concreta.

| Perfil | Responsabilidad | Puede editar |
| --- | --- | --- |
| api_auditor | Comparar documentación original y comportamiento del mock con evidencia | No |
| integration_builder | Implementar una tarea acotada del MVP de integración | Sí |
| quality_reviewer | Revisar riesgos, pruebas y garantías afirmadas | No |
| docs_curator | Mantener roadmaps, contrato corregido y bitácora | Sí, solo documentación |
| web_designer | Mantener la demo Blade y su fidelidad visual sin alterar el flujo POS | Sí, solo interfaz |

No fijamos modelos ni permisos globales en un config.toml del proyecto. Cada perfil hereda la configuración activa de la sesión; los perfiles de auditoría y revisión se declaran de lectura. Antes de delegar, indicar objetivo, archivos pertinentes y resultado esperado. No abrir varios agentes para una tarea corta o dependiente.

El archivo AGENTS.md antiguo de argenfilv2 corresponde a otro repositorio Angular y no se usa aquí.
