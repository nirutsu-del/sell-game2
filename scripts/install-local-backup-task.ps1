param([string]$TaskName = 'MizukiShop-DailyBackup')
$ErrorActionPreference = 'Stop'
$runner = Join-Path $PSScriptRoot 'run-local-backup.ps1'
if (!(Test-Path -LiteralPath $runner)) { throw 'Backup runner not found' }
$account = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$action = New-ScheduledTaskAction -Execute "$env:SystemRoot/System32/WindowsPowerShell/v1.0/powershell.exe" `
    -Argument "-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$runner`""
# Hourly opportunity, one completed backup per Thai calendar day. Retry next hour if MySQL is off.
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Hours 1)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 20)
$principal = New-ScheduledTaskPrincipal -UserId $account -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings `
    -Principal $principal -Description 'Back up Mizuki Shop once daily when the user is logged on and MySQL is available. Retry hourly. Local private destination; no automatic deletion.' -Force | Out-Null
Write-Output "Registered $TaskName for $account."
