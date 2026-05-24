Set-StrictMode -Version Latest
Set-Location "$PSScriptRoot\.."
$source = 'assets/logo.png'
$dest = "tools/backup_logo_$(Get-Date -Format yyyyMMdd-HHmmss).zip"
Compress-Archive -LiteralPath $source -DestinationPath $dest -Force
$h = Get-FileHash -Algorithm SHA256 -Path $dest
$h.Hash | Out-File -FilePath "tools/backup_logo_latest.hash" -Encoding utf8
Write-Output $dest
Write-Output $h.Hash
