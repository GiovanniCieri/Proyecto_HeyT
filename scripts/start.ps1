param([ValidateRange(1,65535)][int]$WebPort = 8000, [switch]$ReuseMock)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
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
function MockReady {
    # HEAD no consume el cupo de requests del mock y expone su header Server.
    try { $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8422/' -Method Head -TimeoutSec 2 -UseBasicParsing -ErrorAction Stop }
    catch { $response = $_.Exception.Response }
    if (-not $response) { return $false }
    if ($response.GetType().FullName -eq 'System.Net.Http.HttpResponseMessage') {
        $server = ($response.Headers.Server | Where-Object { $null -ne $_ } | ForEach-Object { $_.ToString() }) -join ' '
    } else { $server = [string]$response.Headers['Server'] }
    return $server -match 'vittles-pos/'
}
function WebReady($port) {
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:$port/healthz" -TimeoutSec 3 -UseBasicParsing -ErrorAction Stop
        $payload = $response.Content | ConvertFrom-Json
        return $response.StatusCode -eq 200 -and $payload.service -eq 'heytruffle-vittles-demo' -and $payload.status -eq 'ok'
    } catch { return $false }
}
function EnvValue($name) {
    $line = Get-Content (Join-Path $app '.env') | Where-Object { $_ -match "^$name=" } | Select-Object -Last 1
    if ($line) { return ($line -split '=', 2)[1].Trim().Trim('"', "'") }
    return ''
}

Write-Host "`n  heytruffle*  |  VITTLES POS" -ForegroundColor Yellow
Write-Host '  Arranque local | dos servicios' -ForegroundColor White
try { $php = & (Join-Path $PSScriptRoot 'resolve-php.ps1') } catch { Fail 'Se requiere PHP 8.2 o superior en PATH. Ejecuta scripts/install.ps1.' }
if (-not (Test-Path (Join-Path $app 'vendor/autoload.php')) -or -not (Test-Path (Join-Path $app '.env'))) { Fail 'Instalacion incompleta. Ejecuta scripts/install.ps1.' }
if (-not (EnvValue 'APP_KEY')) { Fail 'Falta APP_KEY. Ejecuta scripts/install.ps1.' }
if (-not (EnvValue 'VITTLES_CLIENT_ID') -or -not (EnvValue 'VITTLES_CLIENT_SECRET')) { Fail 'Configura las credenciales del mock en app/.env.' }
if ($WebPort -eq 8422) { Fail 'El puerto web debe ser distinto de 8422.' }
$mockActive = PortOpen 8422
$webActive = PortOpen $WebPort
if ($mockActive -and -not (MockReady)) { Fail 'El puerto 8422 esta ocupado por un servicio que no parece Vittles. Cierra ese proceso.' }
if ($webActive -and -not (WebReady $WebPort)) { Fail "El puerto $WebPort esta ocupado por otra web o por Laravel con error. Revisa ese proceso o usa -WebPort." }
if ($ReuseMock -and -not $mockActive) { Fail '-ReuseMock requiere un mock activo en 8422.' }
if ($mockActive -and $webActive) {
    Write-Host '  Ambos servicios ya estan activos:' -ForegroundColor Green
    Write-Host '  Vittles: http://127.0.0.1:8422'
    Write-Host "  Web:     http://127.0.0.1:$WebPort"
    exit 0
}
$logDir = Join-Path $app 'storage/logs'
New-Item -ItemType Directory -Path $logDir -Force | Out-Null
$runId = "$(Get-Date -Format 'yyyyMMdd-HHmmss')-$PID"
$mockErr = Join-Path $logDir "mock-start-$runId.err.log"
$webOut = Join-Path $logDir "web-start-$runId.out.log"
$webErr = Join-Path $logDir "web-start-$runId.err.log"
try {
    if ($mockActive) { Write-Host '[1/2] Vittles ya activo en 127.0.0.1:8422.' -ForegroundColor Cyan }
    else {
        try { $python = & (Join-Path $PSScriptRoot 'resolve-python.ps1') }
        catch { throw 'Se requiere Python 3.9 o superior en PATH o mediante py -3.' }
        Write-Host '[1/2] Iniciando Vittles mock en 127.0.0.1:8422...' -ForegroundColor Cyan
        # El mock imprime credenciales al iniciar; no persistimos su stdout.
        $mockProcess = Start-Process -FilePath $python -ArgumentList 'mock_server.py' -WorkingDirectory $mock -RedirectStandardError $mockErr -WindowStyle Hidden -PassThru
        $processes += $mockProcess
        $ready = $false
        for ($i=0; $i -lt 40; $i++) { if (MockReady) { $ready = $true; break }; if ($mockProcess.HasExited) { break }; Start-Sleep -Milliseconds 250 }
        if (-not $ready) { throw "Vittles no inicio. Revisa $mockErr" }
    }
    if ($webActive) { Write-Host "[2/2] Laravel ya activo en 127.0.0.1:$WebPort." -ForegroundColor Cyan }
    else {
        Write-Host '[2/2] Iniciando Laravel...' -ForegroundColor Cyan
        $router = Join-Path $app 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
        $public = Join-Path $app 'public'
        $webProcess = Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$WebPort", '-t', '.', "`"$router`"") -WorkingDirectory $public -RedirectStandardOutput $webOut -RedirectStandardError $webErr -WindowStyle Hidden -PassThru
        $processes += $webProcess
        $ready = $false
        for ($i=0; $i -lt 40; $i++) { if (WebReady $WebPort) { $ready = $true; break }; if ($webProcess.HasExited) { break }; Start-Sleep -Milliseconds 250 }
        if (-not $ready) { throw "Laravel no inicio. Revisa $webErr" }
    }
    Write-Host "`n  Vittles: http://127.0.0.1:8422" -ForegroundColor Green
    Write-Host "  Web:     http://127.0.0.1:$WebPort" -ForegroundColor Green
    Write-Host "  Logs:    $logDir" -ForegroundColor DarkGray
    Write-Host '  Ctrl+C detiene solo los servicios iniciados aqui.' -ForegroundColor Yellow
    while ($true) { Start-Sleep -Seconds 1; foreach ($p in $processes) { if ($p.HasExited) { throw "Un servicio termino inesperadamente (PID $($p.Id)). Revisa los logs de arranque." } } }
}
catch { $failed = $true; Write-Host "`n$($_.Exception.Message)" -ForegroundColor Red }
finally {
    foreach ($p in $processes) { if ($p -and -not $p.HasExited) { Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue } }
    Write-Host 'Servicios detenidos.' -ForegroundColor Yellow
}
if ($failed) { exit 1 }
