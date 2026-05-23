<#
Crée un checkpoint git (si nécessaire), écrit un fichier metadata,
archive l'espace de travail en ZIP nommé fin-de-session-<timestamp>.zip
et ferme les instances VS Code ouvertes.
#>
param(
    [string]$WorkspaceRoot = (Get-Location).Path
)

Set-StrictMode -Version Latest
cd $WorkspaceRoot

function Timestamp() { Get-Date -Format "yyyy-MM-dd_HH-mm-ss" }

$ts = Timestamp
$zipName = "fin-de-session-$ts.zip"

# Create git commit if there are staged or unstaged changes
if (Test-Path .git) {
    $status = git status --porcelain
    if ($status) {
        git add -A
        $msg = "Checkpoint before fin-de-session $ts"
        git commit -m $msg 2>$null | Out-Null
        $commit = git rev-parse --short HEAD
    } else {
        $commit = git rev-parse --short HEAD
    }
} else { $commit = $null }

$meta = [pscustomobject]@{
    timestamp = (Get-Date).ToString('o')
    workspace = $WorkspaceRoot
    git_short = $commit
}
$meta | ConvertTo-Json -Depth 5 | Out-File -Encoding UTF8 checkpoint-metadata.json

# Create archive
if (Test-Path $zipName) { Remove-Item $zipName }
Compress-Archive -Path * -DestinationPath $zipName -Force

Write-Output "Archive créée: $zipName"

# Close VS Code gracefully
$vscodeProcs = Get-Process -Name Code -ErrorAction SilentlyContinue
if ($vscodeProcs) {
    foreach ($p in $vscodeProcs) { $p.CloseMainWindow() | Out-Null }
    Start-Sleep -Seconds 2
    # Force quit remaining
    Get-Process -Name Code -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
}

Write-Output "fin-de-session terminé"
