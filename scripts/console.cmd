@echo off
setlocal
cd /d "%~dp0..\app"
if errorlevel 1 (
    echo ERROR: No se pudo abrir la carpeta app.
    goto :failed
)
if not exist "vendor\autoload.php" (
    echo ERROR: La aplicacion no esta instalada. Ejecuta scripts\install.ps1 desde la raiz.
    goto :failed
)
set "php_exe="
for /f "usebackq delims=" %%P in (`powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0resolve-php.ps1" 2^>nul`) do if not defined php_exe set "php_exe=%%P"
if not defined php_exe (
    echo ERROR: Se requiere PHP 8.2 o superior. No se encontro una version compatible en PATH.
    echo Revisa la instalacion de PHP o agrega su carpeta a PATH.
    goto :failed
)
"%php_exe%" artisan vittles:console %*
set "result=%errorlevel%"
echo.
if not "%result%"=="0" echo La consola termino con un error ^(codigo %result%^). Revisa el mensaje anterior.
if "%result%"=="0" echo Saliste de la consola.
echo Presiona una tecla para cerrar esta ventana.
pause >nul
exit /b %result%

:failed
echo Presiona una tecla para cerrar esta ventana.
pause >nul
exit /b 1
