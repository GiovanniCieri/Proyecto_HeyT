@echo off
setlocal
cd /d "%~dp0..\app" || exit /b 1
if not exist "vendor\autoload.php" (
    echo ERROR: La aplicacion no esta instalada. Ejecuta scripts\install.ps1 desde la raiz.
    exit /b 1
)
where php >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no esta disponible en PATH.
    exit /b 1
)
php artisan vittles:console %*
exit /b %errorlevel%
