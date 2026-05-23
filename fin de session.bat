@echo off
powershell -NoProfile -ExecutionPolicy Bypass -Command "& {
  Set-StrictMode -Version Latest
  try {
    $proj = Get-Location
    if (-not (Test-Path (Join-Path $proj '.git'))) {
      Write-Host 'Aucun dépôt Git trouvé dans ce dossier. Abandon.'; exit 1
    }

    $checkpointTimestamp = Get-Date -Format 'yyyy-MM-dd+HH:mm'
    $fileTimestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
    $checkpointName = 'on repart+' + $checkpointTimestamp + '+ok'

    $status = git status --porcelain 2>$null
    if ($status -ne '') {
      Write-Host 'Modifications non validées détectées -> commit checkpoint.'
      git add -A
      git commit -m "checkpoint: $checkpointName" || Write-Host 'Commit failed or nothing to commit.'
    } else {
      Write-Host 'Aucun changement non validé.'
    }

    $commitShort = git rev-parse --short HEAD 2>$null
    $branch = git rev-parse --abbrev-ref HEAD 2>$null

    $meta = @{
      checkpoint_name = $checkpointName
      branch = $branch
      commit = $commitShort
      session_name = $checkpointName
      timestamp = (Get-Date).ToString('o')
    }
    $meta | ConvertTo-Json -Depth 5 | Out-File -FilePath checkpoint-metadata.json -Encoding UTF8
    $checkpointName | Out-File -FilePath REPRISE_COMMAND.txt -Encoding UTF8

    $zipPath = Join-Path (Split-Path -Parent $proj.Path) ('fin-de-session-' + $fileTimestamp + '.zip')
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
    Write-Host "Création de l'archive : $zipPath"
    Compress-Archive -Path * -DestinationPath $zipPath -Force
    Write-Host 'Archive créée.'

    # Fermer Visual Studio Code (process nommé Code ou Code - Insiders)
    $p = Get-Process -Name 'Code' -ErrorAction SilentlyContinue
    if ($p) { Write-Host 'Fermeture de VS Code...'; $p | Stop-Process -Force }
    $p2 = Get-Process -Name 'Code - Insiders' -ErrorAction SilentlyContinue
    if ($p2) { $p2 | Stop-Process -Force }

    Write-Host 'Fin de session terminée.'
  } catch {
    Write-Host 'Erreur:' $_.Exception.Message
    exit 1
  }
}"
