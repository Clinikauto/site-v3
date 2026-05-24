<#
Restaure le dernier archive fin-de-session-*.zip dans le dossier courant,
lit checkpoint-metadata.json et effectue un reset git si nécessaire,
puis ouvre VS Code.
#>
param(
    [string]$WorkspaceRoot = (Get-Location).Path
)

Set-StrictMode -Version Latest
cd $WorkspaceRoot

function Get-LatestZip {
    Get-ChildItem -Path . -Filter 'fin-de-session-*.zip' | Sort-Object LastWriteTime -Descending | Select-Object -First 1
}

$z = Get-LatestZip
if (-not $z) { Write-Error "Aucune archive fin-de-session-*.zip trouvée."; exit 1 }

$tempDir = Join-Path $env:TEMP ("reprise_$([guid]::NewGuid().ToString())")
New-Item -ItemType Directory -Path $tempDir | Out-Null
Expand-Archive -Path $z.FullName -DestinationPath $tempDir -Force

# Copy back files (excluding .git to avoid conflicts)
Get-ChildItem -Path $tempDir -Force | Where-Object { $_.Name -ne '.git' } | ForEach-Object {
    $dest = Join-Path $WorkspaceRoot $_.Name
    if (Test-Path $dest) { Remove-Item -Recurse -Force $dest }
    Copy-Item -Path $_.FullName -Destination $WorkspaceRoot -Recurse -Force
}

# If metadata exists, show it and reset git if requested
$metaFile = Join-Path $WorkspaceRoot 'checkpoint-metadata.json'
if (Test-Path $metaFile) {
    $meta = Get-Content $metaFile | ConvertFrom-Json
    Write-Output "Metadata: $($meta | ConvertTo-Json -Depth 5)"
    if (Test-Path .git -PathType Container -ErrorAction SilentlyContinue -ErrorAction Ignore) {
        if ($meta.git_short) {
            git fetch --all 2>$null
            git checkout -f $meta.git_short 2>$null
        }
    }
}

Write-Output "Restauration terminée. Ouverture de VS Code..."
Start-Process code -ArgumentList '.'
