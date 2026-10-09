# LOCAL_SETUP — Instalación y arranque de ambos servicios

**Estado:** implementado; validación Bash pendiente en un equipo macOS/Linux  
**Pertenece al ejercicio:** apoyo a la entrega; el comando evaluable sigue independiente de la web  
**Dependencias:** integración Laravel y mock original

## Tareas

- [x] Verificar PHP, Composer y Python antes de instalar.
- [x] Instalar dependencias con `composer install` sin actualizar versiones.
- [x] Conservar `.env`, `APP_KEY` y SQLite en instalaciones repetidas.
- [x] Permitir configurar credenciales sin eco y sin guardarlas en un script.
- [x] Arrancar mock y web desde un solo comando, comprobar puertos y limpiar procesos propios.
- [x] Separar salida del mock que imprime credenciales de logs de errores.
- [x] Documentar comandos, opciones y prueba de idempotencia.
- [x] Mostrar actividad y tiempo transcurrido durante la fase larga de Composer.
- [x] Abrir el menú interactivo desde la raíz con un solo comando, sin depender del navegador.
- [x] Mantener visible el resultado al abrir `console.cmd` como ventana nueva y fallar rápidamente sin TTY.
- [x] Seleccionar PHP 8.2+ cuando Windows tiene varias versiones en `PATH`.
- [x] Reutilizar servicios propios ya activos, identificar colisiones ajenas y comprobar salud HTTP.
- [x] Abrir instalación y arranque Windows con `.cmd` y ejecutar cualquier Artisan con PHP compatible.
- [x] Crear logs de arranque por ejecución para no truncar archivos de procesos activos.
- [x] Explicar en el README qué seleccionan los scripts `resolve` y qué efectos no tienen.
- [ ] Ejecutar los scripts Bash en macOS/Linux real.

## Criterios de aceptación

- Una instalación nueva se prepara con un comando y el arranque muestra ambas URLs.
- Una segunda instalación conserva las cuentas locales y la configuración existente.
- Si 8422 u 8000 ya están ocupados por esta aplicación, se reutilizan; si pertenecen a otro servicio, se informa el conflicto sin matarlo.
- El comando `vittles:order` funciona sin sesión web y conserva ID y total al repetirlo contra el mismo mock.
- `scripts/console.cmd` y `scripts/console.sh` abren el mismo menú `vittles:console` desde la raíz.
