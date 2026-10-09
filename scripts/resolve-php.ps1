# Devuelve la primera instalación de PHP 8.2+ encontrada en PATH.
# No cambia la configuración global del equipo ni imprime rutas no válidas.
$candidates = Get-Command php.exe -All -ErrorAction SilentlyContinue |
    Select-Object -ExpandProperty Source -Unique

foreach ($candidate in $candidates) {
    $version = & $candidate -r 'echo PHP_VERSION_ID;' 2>$null
    if ($LASTEXITCODE -eq 0 -and $version -match '^\d+$' -and [int]$version -ge 80200) {
        Write-Output $candidate
        return
    }
}

throw 'No se encontro PHP 8.2 o superior en PATH.'
