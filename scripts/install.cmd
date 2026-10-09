@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1" %*
set "result=%errorlevel%"
echo.
if not "%result%"=="0" echo La instalacion termino con un error ^(codigo %result%^). Revisa el mensaje anterior.
if "%result%"=="0" echo Instalacion finalizada.
echo Presiona una tecla para cerrar esta ventana.
pause >nul
exit /b %result%
