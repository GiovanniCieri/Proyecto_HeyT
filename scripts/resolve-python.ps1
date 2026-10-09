# Devuelve Python 3.9+ desde el launcher de Windows o PATH.
# Ignora aliases de Microsoft Store y launchers sin una instalación válida.
foreach ($candidate in @(
    @{ command = 'py'; prefix = @('-3') },
    @{ command = 'python3'; prefix = @() },
    @{ command = 'python'; prefix = @() }
)) {
    if (-not (Get-Command $candidate.command -ErrorAction SilentlyContinue)) { continue }
    try {
        $path = & $candidate.command @($candidate.prefix) -c 'import sys; print(sys.executable)' 2>$null
        if ($LASTEXITCODE -ne 0 -or -not $path) { continue }
        $path = ($path | Select-Object -First 1).Trim()
        if (-not (Test-Path -LiteralPath $path)) { continue }
        & $path -c 'import sys; sys.exit(0 if sys.version_info >= (3, 9) else 1)' 2>$null
        if ($LASTEXITCODE -eq 0) { Write-Output $path; return }
    } catch { continue }
}

throw 'No se encontro Python 3.9 o superior.'
