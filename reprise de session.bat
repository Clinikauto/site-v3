@echo off
powershell -NoProfile -ExecutionPolicy Bypass -Command "& {
  Set-StrictMode -Version Latest
  try {
    $proj = Get-Location
    $parent = Split-Path -Parent $proj.Path
    $zips = Get-ChildItem -Path $parent -Filter 'fin-de-session-*.zip' -File | Sort-Object LastWriteTime -Descending
    if (-not $zips) { Write-Host 'Aucune archive de reprise trouvée dans le dossier parent.'; exit 1 }
    $zip = $zips[0].FullName
    Write-Host "Archive trouvée : $zip"

    $temp = Join-Path $env:TEMP ('reprise_' + [guid]::NewGuid().ToString())
    New-Item -ItemType Directory -Path $temp | Out-Null
    Expand-Archive -LiteralPath $zip -DestinationPath $temp -Force

    # Copier le contenu restitué dans le dossier courant (écrase)
    Get-ChildItem -Path $temp -Force | ForEach-Object {
      Copy-Item -Path $_.FullName -Destination $proj -Recurse -Force -ErrorAction SilentlyContinue
    }
    Remove-Item -Path $temp -Recurse -Force

    $metaPath = Join-Path $proj.Path 'checkpoint-metadata.json'
    if (-not (Test-Path $metaPath)) { Write-Host 'Fichier metadata introuvable après extraction.'; exit 1 }
    $meta = Get-Content $metaPath -Raw | ConvertFrom-Json

    Write-Host 'Restoration Git : branche' $meta.branch 'commit' $meta.commit
    git fetch --all --prune
    git checkout $meta.branch
    git reset --hard $meta.commit

    Write-Host 'Ouverture de VS Code...'
    Start-Process code -ArgumentList '.'

    Write-Host 'Reprise terminée. Si vous utilisez une extension de gestion de session, restaurez la session nommée:' $meta.session_name
  } catch {
    Write-Host 'Erreur:' $_.Exception.Message
    exit 1
  }
}"
