<#
Installe ps2exe au besoin et compile les scripts en `dist\`.
#>
param()

Set-StrictMode -Version Latest
cd (Split-Path -Parent $MyInvocation.MyCommand.Definition)

if (-not (Get-Command Invoke-PS2EXE -ErrorAction SilentlyContinue)) {
    Write-Output "Installation du module ps2exe (nécessite Internet et consentement)."
    Install-Module -Name ps2exe -Scope CurrentUser -Force -AllowClobber
    Import-Module ps2exe -Force
}

New-Item -ItemType Directory -Path "dist" -Force | Out-Null

$scripts = @('fin-de-session.ps1','reprise-de-session.ps1')
foreach ($s in $scripts) {
    $in = Join-Path '..' "tools\$s"
    $out = Join-Path '..\dist' ([IO.Path]::ChangeExtension($s,'.exe'))
    if (Test-Path $in) {
        Write-Output "Compilation $in -> $out"
        Invoke-PS2EXE $in $out -NoConsole -ErrorAction Stop
    } else { Write-Warning "Introuvable: $in" }
}

Write-Output "Compilation terminée. Fichiers dans dist\\"
