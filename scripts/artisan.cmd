@echo off
setlocal
cd /d "%~dp0..\app" || exit /b 1
if not exist "vendor\autoload.php" (
    echo ERROR: La aplicacion no esta instalada. Ejecuta scripts\install.cmd.
    exit /b 1
)
set "php_exe="
for /f "usebackq delims=" %%P in (`powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0resolve-php.ps1" 2^>nul`) do if not defined php_exe set "php_exe=%%P"
if not defined php_exe (
    echo ERROR: Se requiere PHP 8.2 o superior en PATH.
    exit /b 1
)
"%php_exe%" artisan %*
exit /b %errorlevel%
