@echo off
setlocal
start "Vittles POS" powershell.exe -NoProfile -NoExit -ExecutionPolicy Bypass -File "%~dp0start.ps1" %*
if errorlevel 1 (
    echo ERROR: No se pudo abrir PowerShell. Ejecuta scripts\start.ps1 desde una terminal.
    pause
    exit /b 1
)
exit /b 0
