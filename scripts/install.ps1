param([switch]$NoPrompt)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$app = Join-Path $root 'app'

function Step($number, $message) { Write-Host "`n[$number/5] $message" -ForegroundColor Cyan }
function Fail($message) { Write-Host "ERROR: $message" -ForegroundColor Red; exit 1 }
function EnvValue($name) {
    $line = Get-Content (Join-Path $app '.env') | Where-Object { $_ -match "^$name=" } | Select-Object -Last 1
    if ($line) { return ($line -split '=', 2)[1].Trim().Trim('"', "'") }
    return ''
}
function SetEnvValue($name, $value) {
    if ($value -match '[\r\n]') { Fail "$name contiene saltos de linea." }
    $path = Join-Path $app '.env'
    $content = [System.IO.File]::ReadAllText($path)
    $escaped = $value.Replace('\', '\\').Replace('"', '\"')
    $entry = "$name=`"$escaped`""
    $pattern = "(?m)^$name=.*$"
    if ([regex]::IsMatch($content, $pattern)) { $content = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $entry }) }
    else { $content = $content.TrimEnd() + [Environment]::NewLine + $entry + [Environment]::NewLine }
    [System.IO.File]::WriteAllText($path, $content, [System.Text.UTF8Encoding]::new($false))
}

Write-Host "`n  heytruffle*  |  VITTLES POS" -ForegroundColor Yellow
Write-Host '  Instalacion local | Laravel + mock' -ForegroundColor White

Step 1 'Comprobando herramientas'
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { Fail 'Falta Composer en PATH.' }
try { $php = & (Join-Path $PSScriptRoot 'resolve-php.ps1') } catch { Fail 'Se requiere PHP 8.2 o superior en PATH.' }
# Composer usa php desde PATH; adelantamos el ejecutable elegido solo en este proceso.
$env:PATH = "$(Split-Path -Parent $php);$env:PATH"
try { $python = & (Join-Path $PSScriptRoot 'resolve-python.ps1') } catch { Fail 'Se requiere Python 3.9 o superior en PATH o mediante py -3.' }
Write-Host "  PHP compatible: $php" -ForegroundColor Green
Write-Host '  Composer y Python listos.' -ForegroundColor Green

Step 2 'Instalando dependencias PHP'
Write-Host '  Composer instala desde composer.lock; la generacion de autoload puede tardar varios minutos.' -ForegroundColor DarkGray
$composerJob = Start-Job -ScriptBlock {
    param($appPath)
    Set-Location -LiteralPath $appPath
    & composer install --no-interaction --prefer-dist 2>&1 | ForEach-Object { Write-Output $_.ToString() }
    [pscustomobject]@{ ComposerExitCode = $LASTEXITCODE }
} -ArgumentList $app
$composerWatch = [Diagnostics.Stopwatch]::StartNew()
$lastHeartbeat = 0
$composerExit = $null
$composerPhase = 'resolviendo dependencias'
try {
    do {
        foreach ($entry in @(Receive-Job -Job $composerJob -ErrorAction SilentlyContinue)) {
            if ($entry -is [string]) {
                Write-Host "  $entry"
                if ($entry -match 'Generating optimized autoload files') { $composerPhase = 'generando autoload optimizado' }
                elseif ($entry -match 'Discovering packages|package:discover') { $composerPhase = 'descubriendo paquetes Laravel' }
            } elseif ($null -ne $entry.PSObject.Properties['ComposerExitCode']) {
                $composerExit = $entry.ComposerExitCode
            }
        }
        $seconds = [int]$composerWatch.Elapsed.TotalSeconds
        if ($composerJob.State -eq 'Running' -and $seconds - $lastHeartbeat -ge 8) {
            Write-Host "  ... Composer sigue activo ($seconds s): $composerPhase" -ForegroundColor Yellow
            $lastHeartbeat = $seconds
        }
        if ($composerJob.State -eq 'Running') { Wait-Job -Job $composerJob -Timeout 1 | Out-Null }
    } while ($composerJob.State -eq 'Running' -or $composerJob.HasMoreData)
} finally {
    if ($composerJob.State -eq 'Running') { Stop-Job -Job $composerJob }
    Remove-Job -Job $composerJob -Force
}
if ($composerExit -ne 0) { Fail 'composer install fallo. Revisa las lineas anteriores.' }
Write-Host "  Dependencias listas en $([int]$composerWatch.Elapsed.TotalSeconds) s." -ForegroundColor Green

Step 3 'Preparando configuracion'
$envFile = Join-Path $app '.env'
if (-not (Test-Path $envFile)) { Copy-Item (Join-Path $app '.env.example') $envFile; Write-Host '  .env creado desde el ejemplo.' }
else { Write-Host '  .env existente conservado.' }
if (-not (EnvValue 'APP_KEY')) {
    Push-Location $app
    try { & $php artisan key:generate --no-interaction; if ($LASTEXITCODE -ne 0) { Fail 'No se pudo generar APP_KEY.' } }
    finally { Pop-Location }
}

Step 4 'Configurando acceso al mock'
if (-not (EnvValue 'VITTLES_CLIENT_ID') -or -not (EnvValue 'VITTLES_CLIENT_SECRET')) {
    if ($NoPrompt) { Write-Host '  Credenciales pendientes: completa VITTLES_CLIENT_ID y VITTLES_CLIENT_SECRET en app/.env.' -ForegroundColor Yellow }
    else {
        Write-Host '  Usa las credenciales del README original del mock. La clave no se muestra.'
        $clientId = Read-Host '  Client ID'
        $secure = Read-Host '  Client secret' -AsSecureString
        $ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
        try { $clientSecret = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr) }
        finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
        if ($clientId -and $clientSecret) {
            SetEnvValue 'VITTLES_CLIENT_ID' $clientId
            SetEnvValue 'VITTLES_CLIENT_SECRET' $clientSecret
            Write-Host '  Credenciales guardadas en app/.env (ignorado por Git).' -ForegroundColor Green
        } else { Write-Host '  Credenciales pendientes en app/.env.' -ForegroundColor Yellow }
    }
} else { Write-Host '  Credenciales existentes conservadas.' }

Step 5 'Preparando SQLite sin borrar datos'
$database = Join-Path $app 'database/database.sqlite'
if (-not (Test-Path $database)) { [System.IO.File]::WriteAllBytes($database, [byte[]]@()); Write-Host '  Base SQLite creada.' }
Push-Location $app
try { & $php artisan migrate --force; if ($LASTEXITCODE -ne 0) { Fail 'Las migraciones fallaron.' } }
finally { Pop-Location }
Write-Host "`nLISTO. Arranca ambos servicios con: .\scripts\start.ps1" -ForegroundColor Green
