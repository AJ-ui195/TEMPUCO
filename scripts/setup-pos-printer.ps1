# One-shot setup: POS-80 default printer + desktop shortcuts for production POS
$ErrorActionPreference = 'Stop'

$printerName = 'POS-80'
$scripts = 'c:\laragon\www\TEMPUCO\scripts'
$desktop = [Environment]::GetFolderPath('Desktop')

Write-Host "Setting default printer to $printerName ..." -ForegroundColor Cyan
$printer = Get-CimInstance -ClassName Win32_Printer -Filter "Name='$printerName'"
if (-not $printer) {
    Write-Host "ERROR: Printer '$printerName' was not found." -ForegroundColor Red
    Get-CimInstance Win32_Printer | Sort-Object Name | ForEach-Object {
        $mark = if ($_.Default) { ' (DEFAULT)' } else { '' }
        Write-Host ("  - {0}{1}" -f $_.Name, $mark)
    }
    exit 1
}

Invoke-CimMethod -InputObject $printer -MethodName SetDefaultPrinter | Out-Null
Write-Host "Default printer is now: $printerName" -ForegroundColor Green

$ws = New-Object -ComObject WScript.Shell
$iconCandidates = @(
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "$env:LocalAppData\Google\Chrome\Application\chrome.exe"
)
$icon = $iconCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1

$shortcuts = @(
    @{
        Name = 'TEMPUCO Grocery POS.lnk'
        Target = 'launch-pos-chrome.bat'
        Description = 'TEMPUCO Grocery POS (https://tempuco-dicnsh.com/pos) - silent print to POS-80'
    },
    @{
        Name = 'TEMPUCO Canteen POS.lnk'
        Target = 'launch-pos-canteen-chrome.bat'
        Description = 'TEMPUCO Canteen POS (https://tempuco-dicnsh.com/pos/canteen) - silent print to POS-80'
    }
)

foreach ($item in $shortcuts) {
    $path = Join-Path $desktop $item.Name
    $shortcut = $ws.CreateShortcut($path)
    $shortcut.TargetPath = Join-Path $scripts $item.Target
    $shortcut.WorkingDirectory = $scripts
    $shortcut.Description = $item.Description
    if ($icon) {
        $shortcut.IconLocation = $icon
    }
    $shortcut.Save()
    Write-Host "Created: $path" -ForegroundColor Green
}

Write-Host ""
Write-Host "Setup complete." -ForegroundColor Green
Write-Host "Open POS with the desktop shortcuts so receipts print directly to POS-80."
Write-Host "  Grocery : https://tempuco-dicnsh.com/pos"
Write-Host "  Canteen : https://tempuco-dicnsh.com/pos/canteen"
