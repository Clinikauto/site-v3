param(
    [ValidateSet('dry-run','apply')]
    [string]$Mode = 'dry-run',

    [string]$RemotePath = '/www',

    [string]$ConfigPath = '.\deploy\rclone-o2switch.conf',

    [string]$ExcludePath = '.\deploy\o2sync.exclude'
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

function Write-Step {
    param([string]$Message)
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

if (-not (Get-Command rclone -ErrorAction SilentlyContinue)) {
    throw 'rclone est introuvable. Installez-le: winget install --id Rclone.Rclone -e --source winget'
}

if (-not (Test-Path $ExcludePath)) {
    throw "Fichier d'exclusion introuvable: $ExcludePath"
}

if (-not (Test-Path $ConfigPath)) {
    $exampleConfig = Join-Path (Split-Path -Parent $ConfigPath) 'rclone-o2switch.example.conf'
    if (Test-Path $exampleConfig) {
        Copy-Item $exampleConfig $ConfigPath -Force
    }
    throw "Configuration absente: $ConfigPath. Le modele a ete copie, completez-le puis relancez."
}

Write-Step 'Validation de la configuration distante'
$rcloneConfigAbs = (Resolve-Path $ConfigPath).Path
$rcloneExcludeAbs = (Resolve-Path $ExcludePath).Path

$rcloneBaseArgs = @(
    '--config', $rcloneConfigAbs,
    '--exclude-from', $rcloneExcludeAbs,
    '--ask-password=false',
    '--contimeout', '10s',
    '--timeout', '20s',
    '--retries', '1',
    '--low-level-retries', '1',
    '--checksum',
    '--fast-list',
    '--transfers', '6',
    '--checkers', '12',
    '--verbose'
)

$lsdArgs = @('lsd', "o2switch:$RemotePath") + @('--config', $rcloneConfigAbs, '--ask-password=false', '--contimeout', '10s', '--timeout', '20s', '--retries', '1', '--low-level-retries', '1')
& rclone @lsdArgs | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw 'Connexion o2switch impossible. Verifiez host/login/mot de passe FTP dans deploy/rclone-o2switch.conf.'
}

Write-Step 'Checkpoint local (zip + SHA256)'
$backupDir = Join-Path $projectRoot 'backups'
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$zipPath = Join-Path $backupDir "CLINIKAUTO.FR-$timestamp.zip"
$hashPath = "$zipPath.sha256"

$itemsToArchive = Get-ChildItem -Force $projectRoot | Where-Object { $_.Name -notin @('backups') }
Compress-Archive -Path $itemsToArchive.FullName -DestinationPath $zipPath -CompressionLevel Optimal
$hash = Get-FileHash -Path $zipPath -Algorithm SHA256
"$($hash.Hash)  $(Split-Path -Leaf $zipPath)" | Out-File -FilePath $hashPath -Encoding ascii

Write-Host "Checkpoint cree: $zipPath"
Write-Host "Hash cree: $hashPath"

Write-Step "Synchronisation locale -> o2switch:$RemotePath ($Mode)"
$syncArgs = @('sync', '.\', "o2switch:$RemotePath") + $rcloneBaseArgs + @('--progress')
if ($Mode -eq 'dry-run') {
    $syncArgs += '--dry-run'
}

& rclone @syncArgs
if ($LASTEXITCODE -ne 0) {
    throw 'La synchronisation a echoue. Consultez les logs ci-dessus.'
}

Write-Step 'Termine'
if ($Mode -eq 'dry-run') {
    Write-Host 'Simulation terminee. Aucune modification distante n a ete appliquee.' -ForegroundColor Yellow
    Write-Host 'Pour appliquer: .\deploy\sync-o2switch.ps1 -Mode apply -RemotePath /www'
} else {
    Write-Host 'Synchronisation appliquee avec succes.' -ForegroundColor Green
}
