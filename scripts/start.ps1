param([ValidateRange(1,65535)][int]$WebPort = 8000, [switch]$ReuseMock)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$app = Join-Path $root 'app'
$mock = Join-Path $root 'docs/Docs_API/vittles'
$processes = @()
$failed = $false
function Fail($message) { Write-Host "ERROR: $message" -ForegroundColor Red; exit 1 }
function PortOpen($port) {
    $client = [Net.Sockets.TcpClient]::new()
    try { $result = $client.BeginConnect('127.0.0.1', $port, $null, $null); return ($result.AsyncWaitHandle.WaitOne(250) -and $client.Connected) }
    catch { return $false }
    finally { $client.Close() }
}
function EnvValue($name) {
    $line = Get-Content (Join-Path $app '.env') | Where-Object { $_ -match "^$name=" } | Select-Object -Last 1
    if ($line) { return ($line -split '=', 2)[1].Trim().Trim('"', "'") }
    return ''
}

Write-Host "`n  heytruffle*  |  VITTLES POS" -ForegroundColor Yellow
Write-Host '  Arranque local · dos servicios' -ForegroundColor White
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { Fail 'Falta PHP. Ejecuta primero scripts/install.ps1.' }
if (-not (Test-Path (Join-Path $app 'vendor/autoload.php')) -or -not (Test-Path (Join-Path $app '.env'))) { Fail 'Instalacion incompleta. Ejecuta scripts/install.ps1.' }
if (-not (EnvValue 'APP_KEY')) { Fail 'Falta APP_KEY. Ejecuta scripts/install.ps1.' }
if (-not (EnvValue 'VITTLES_CLIENT_ID') -or -not (EnvValue 'VITTLES_CLIENT_SECRET')) { Fail 'Configura las credenciales del mock en app/.env.' }
if ($WebPort -eq 8422) { Fail 'El puerto web debe ser distinto de 8422.' }
if ((PortOpen 8422) -and -not $ReuseMock) { Fail 'El puerto 8422 ya esta ocupado. Cierra el mock anterior o usa -ReuseMock.' }
if ($ReuseMock -and -not (PortOpen 8422)) { Fail '-ReuseMock requiere un mock activo en 8422.' }
if (PortOpen $WebPort) { Fail "El puerto $WebPort ya esta ocupado. Usa -WebPort con otro puerto." }
$python = if (Get-Command py -ErrorAction SilentlyContinue) { (& py -3 -c 'import sys; print(sys.executable)' 2>$null) } elseif (Get-Command python3 -ErrorAction SilentlyContinue) { (& python3 -c 'import sys; print(sys.executable)' 2>$null) } else { (& python -c 'import sys; print(sys.executable)' 2>$null) }
if (-not $python -or $LASTEXITCODE -ne 0) { Fail 'No se encontro Python 3. Ejecuta el instalador.' }
$python = ($python | Select-Object -First 1).Trim()
$logDir = Join-Path $app 'storage/logs'
New-Item -ItemType Directory -Path $logDir -Force | Out-Null
$mockErr = Join-Path $logDir 'mock-start.err.log'
$webOut = Join-Path $logDir 'web-start.out.log'
$webErr = Join-Path $logDir 'web-start.err.log'
try {
    if ($ReuseMock) { Write-Host '[1/2] Reutilizando mock activo en 127.0.0.1:8422.' -ForegroundColor Cyan }
    else {
        Write-Host '[1/2] Iniciando Vittles mock en 127.0.0.1:8422...' -ForegroundColor Cyan
        # El mock imprime credenciales al iniciar; no persistimos su stdout.
        $mockProcess = Start-Process -FilePath $python -ArgumentList 'mock_server.py' -WorkingDirectory $mock -RedirectStandardError $mockErr -WindowStyle Hidden -PassThru
        $processes += $mockProcess
        $ready = $false
        for ($i=0; $i -lt 40; $i++) { if (PortOpen 8422) { $ready = $true; break }; if ($mockProcess.HasExited) { break }; Start-Sleep -Milliseconds 250 }
        if (-not $ready) { throw "Vittles no inicio. Revisa $mockErr" }
    }
    Write-Host '[2/2] Iniciando Laravel...' -ForegroundColor Cyan
    $router = Join-Path $app 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
    $public = Join-Path $app 'public'
    $webProcess = Start-Process -FilePath (Get-Command php).Source -ArgumentList @('-S', "127.0.0.1:$WebPort", '-t', '.', "`"$router`"") -WorkingDirectory $public -RedirectStandardOutput $webOut -RedirectStandardError $webErr -WindowStyle Hidden -PassThru
    $processes += $webProcess
    $ready = $false
    for ($i=0; $i -lt 40; $i++) { if (PortOpen $WebPort) { $ready = $true; break }; if ($webProcess.HasExited) { break }; Start-Sleep -Milliseconds 250 }
    if (-not $ready) { throw "Laravel no inicio. Revisa $webErr" }
    Write-Host "`n  Vittles: http://127.0.0.1:8422" -ForegroundColor Green
    Write-Host "  Web:     http://127.0.0.1:$WebPort" -ForegroundColor Green
    Write-Host "  Logs:    $logDir" -ForegroundColor DarkGray
    Write-Host '  Ctrl+C detiene los dos servicios iniciados aqui.' -ForegroundColor Yellow
    while ($true) { Start-Sleep -Seconds 1; foreach ($p in $processes) { if ($p.HasExited) { throw "Un servicio termino inesperadamente (PID $($p.Id)). Revisa los logs de arranque." } } }
}
catch { $failed = $true; Write-Host "`n$($_.Exception.Message)" -ForegroundColor Red }
finally {
    foreach ($p in $processes) { if ($p -and -not $p.HasExited) { Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue } }
    Write-Host 'Servicios detenidos.' -ForegroundColor Yellow
}
if ($failed) { exit 1 }
