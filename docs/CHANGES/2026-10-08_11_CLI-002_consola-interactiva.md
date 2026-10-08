# CLI-002 — Consola interactiva de la demo

**Fecha:** 2026-10-08

**Archivos cambiados:** `app/app/Console/Commands/VittlesConsole.php`, `app/app/Support/DiagnosticLog.php`, pruebas `VittlesConsoleTest.php`, README.md, página README, AGENTS.md, `docs/CONSOLE.md`, COMMANDS.md, ADMIN_DIAGNOSTICS.md, LOGGING.md y roadmap INTERACTIVE_CONSOLE.md.

**Motivo:** recorrer desde la terminal las funciones disponibles en la web sin perder el comando evaluable y su formato exacto.

**Cambios:** `php artisan vittles:console` ofrece portada y menús de registro/login, sedes y menús, pedido de uno o varios productos, comprobante, historial local y detalle, consulta en vivo por ID, README, cierre de sesión y salida. La opción “Ejecutar comando del ejercicio” lo imprime y llama a `vittles:order` al confirmar. ADMIN, visible solo para la cuenta administradora con entorno y mock locales, permite probar endpoints y examinar trazas redactadas. Las cuentas son las mismas de la web, pero la sesión de la terminal no crea una sesión del navegador. Contraseñas ocultas; token y secreto no se imprimen. Acciones y errores esperados tienen eventos de diagnóstico.

**Verificación:** menú real abierto en PTY, vista previa del comando y salida correcta; `--no-interaction` terminó con código 1 y orientó al comando directo. `php artisan test`: 29 pruebas, 182 aserciones, incluidas registro, login, restricción ADMIN, pedido guiado con dos productos, invocación del comando original desde el menú y autenticación de diagnóstico con token redactado. Pint y `git diff --check` correctos.

**Pendientes y límites:** el listado de pedidos continúa siendo el historial local, ya que el mock no enumera todas las órdenes por sede. El catálogo se conserva en memoria durante el menú y se puede actualizar explícitamente; cada creación vuelve a validarlo desde el POS. La comparación visual de terminales fuera de Windows no se ejecutó en esta pasada.
