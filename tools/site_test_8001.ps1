$worktree = 'D:\site_required\tools\worktrees\restore-20260523-eb2890b2'
Set-Location $worktree
$base = 'http://127.0.0.1:8001'
$files = Get-ChildItem -Path . -Recurse -Include *.php,*.html -File | Where-Object { $_.FullName -notlike '*\\.git*' } | Sort-Object FullName
Write-Output "TOTAL:$($files.Count)"
$i = 0
foreach ($f in $files) {
    $i++
    $rel = $f.FullName.Substring((Get-Location).Path.Length).TrimStart('\')
    $urlpath = '/' + ($rel -replace '\\','/')
    if ($urlpath -match '/index\\.html$') { $urlpath = $urlpath -replace '/index\\.html$','/' }
    $url = $base + $urlpath
    Write-Output "START:$i/$($files.Count) $url"
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
        $sw.Stop()
        $status = $r.StatusCode
        $len = if ($r.RawContentLength -ne $null) { $r.RawContentLength } else { ($r.Content | Out-String).Length }
        Write-Output "OK:$url Status=$status TimeMs=$($sw.ElapsedMilliseconds) Len=$len"
    } catch {
        $sw.Stop()
        $err = $_.Exception
        $msg = ($err.Message) -replace "`r|`n",' '
        $st = 'ERR'
        if ($err.Response -ne $null) { $st = $err.Response.StatusCode.value__ }
        Write-Output "ERR:$url Status=$st Msg=$msg TimeMs=$($sw.ElapsedMilliseconds)"
    }
}
Write-Output "CHECK_ASSETS"
try {
    $h = Invoke-WebRequest -Uri ($base + '/assets/logo.avif') -Method Head -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
    Write-Output "ASSET: /assets/logo.avif HEAD OK Status=$($h.StatusCode)"
} catch {
    Write-Output "ASSET: /assets/logo.avif NOT_FOUND_OR_ERR"
}
Write-Output "DONE"
