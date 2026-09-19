param(
    [string]$ProjectRoot = (Split-Path $PSScriptRoot -Parent),
    [string]$BackupRoot = (Join-Path $env:LOCALAPPDATA 'MizukiShop/backups'),
    [string]$PhpPath = 'C:/xampp/php/php.exe',
    [switch]$Force
)
$ErrorActionPreference = 'Stop'
$ProjectRoot = (Resolve-Path -LiteralPath $ProjectRoot).Path
if (!(Test-Path -LiteralPath (Join-Path $ProjectRoot 'artisan'))) { throw 'Laravel project not found' }
New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null
$BackupRoot = (Resolve-Path -LiteralPath $BackupRoot).Path
$statePath = Join-Path $BackupRoot 'status.json'
$lockPath = Join-Path $BackupRoot 'runner.lock'
$lock = $null
try { $lock = [IO.File]::Open($lockPath, 'OpenOrCreate', 'ReadWrite', 'None') }
catch [IO.IOException] { exit 0 }
$exitCode = 1
try {
    $today = [TimeZoneInfo]::ConvertTimeBySystemTimeZoneId([DateTime]::UtcNow, 'SE Asia Standard Time').ToString('yyyy-MM-dd')
    $previous = if (Test-Path -LiteralPath $statePath) { Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json } else { $null }
    if (!$Force -and $previous.lastSuccessDay -eq $today -and
        (Test-Path -LiteralPath (Join-Path $previous.lastBackupPath 'manifest.json'))) { exit 0 }
    $state = [ordered]@{
        lastAttemptAt = [DateTime]::UtcNow.ToString('o')
        lastSuccessDay = $previous.lastSuccessDay
        lastSuccessAt = $previous.lastSuccessAt
        lastBackupPath = $previous.lastBackupPath
        lastExitCode = 1
    }
    Push-Location $ProjectRoot
    try {
        $output = @(& $PhpPath artisan store:backup "--output=$BackupRoot" --no-ansi 2>&1)
        $exitCode = $LASTEXITCODE
    } finally { Pop-Location }
    $output | Set-Content -LiteralPath (Join-Path $BackupRoot 'last-run.log') -Encoding UTF8
    if ($exitCode -eq 0) {
        $created = $output | ForEach-Object { if ([string]$_ -match '^Backup created: (.+)$') { $Matches[1] } } | Select-Object -Last 1
        if (!$created -or !(Test-Path -LiteralPath (Join-Path $created 'manifest.json'))) { throw 'Backup completion manifest missing' }
        $state.lastSuccessDay = $today
        $state.lastSuccessAt = [DateTime]::UtcNow.ToString('o')
        $state.lastBackupPath = $created
    }
    $state.lastExitCode = $exitCode
    $state | ConvertTo-Json | Set-Content -LiteralPath $statePath -Encoding UTF8
} catch {
    # No mail/DB credentials or SQL are printed into the scheduler log.
    'Backup runner failed. Check PHP, MySQL and destination availability.' |
        Set-Content -LiteralPath (Join-Path $BackupRoot 'last-run.log') -Encoding UTF8
    if ($state) {
        $state.lastExitCode = 1
        $state | ConvertTo-Json | Set-Content -LiteralPath $statePath -Encoding UTF8
    }
    $exitCode = 1
} finally { if ($lock) { $lock.Dispose() } }
exit $exitCode
