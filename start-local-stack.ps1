$phpExe = "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
$mariaBin = "C:\Program Files\MariaDB 12.2\bin"
$dataDir = "d:\site clinikauto v3\mariadb-data"
$webRoot = "d:\site clinikauto v3\clinikauto"
$mariaErrLog = Join-Path $dataDir "ClinikAuto.err"

if (-not (Test-Path $phpExe)) {
    Write-Error "PHP introuvable: $phpExe"
    exit 1
}
if (-not (Test-Path "$mariaBin\mariadbd.exe")) {
    Write-Error "MariaDB introuvable: $mariaBin"
    exit 1
}
if (-not (Test-Path $dataDir)) {
    Write-Error "DataDir MariaDB introuvable: $dataDir"
    exit 1
}

# Start MariaDB local instance (3307) only if not already running.
if (-not (Get-Process mariadbd -ErrorAction SilentlyContinue)) {
    $mariaArgs = @(
        "--datadir=$dataDir",
        "--port=3307",
        "--bind-address=127.0.0.1",
        "--log-error=$mariaErrLog"
    )
    # Utiliser cmd /c start /B pour créer un processus vraiment detache du groupe console
    $argString = ($mariaArgs | ForEach-Object { "`"$_`"" }) -join ' '
    cmd /c "start `"`"  `"C:\Program Files\MariaDB 12.2\bin\mariadbd.exe`" $argString"
}

# Start PHP built-in server (8001) only if not already running.
if (-not (Get-Process php -ErrorAction SilentlyContinue | Where-Object { $_.Path -eq $phpExe })) {
    $phpArgs = @(
        "-d", "upload_max_filesize=50M",
        "-d", "post_max_size=50M",
        "-d", "memory_limit=256M",
        "-d", "extension=gd",
        "-S", "127.0.0.1:8001"
    )
    Start-Process -FilePath $phpExe -ArgumentList $phpArgs -WorkingDirectory $webRoot -WindowStyle Hidden | Out-Null
}

# Wait up to 15s for MariaDB to accept TCP connections on 3307
$timeout = 15
$ready = $false
for ($i = 0; $i -lt $timeout; $i++) {
    try {
        $tcp = New-Object System.Net.Sockets.TcpClient
        $tcp.Connect("127.0.0.1", 3307)
        $tcp.Close()
        $ready = $true
        break
    } catch { Start-Sleep -Seconds 1 }
}

if ($ready) {
    Write-Output "Stack locale OK: MariaDB 127.0.0.1:3307 ACTIF + Site http://127.0.0.1:8001/admin.php"
} else {
    Write-Warning "MariaDB n'ecoute pas sur le port 3307 apres $timeout secondes. Verifiez le journal: $mariaErrLog"
    Write-Output "Serveur PHP lance: http://127.0.0.1:8001/admin.php (mode JSON uniquement)"
}
