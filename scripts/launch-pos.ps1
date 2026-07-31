# TEMPUCO POS — silent thermal receipt printing
# Opens Chrome with --kiosk-printing so receipts print to the Windows
# default printer with no print dialog / preview.
#
# Usage:
#   .\launch-pos.ps1
#   .\launch-pos.ps1 -Panel canteen
#   .\launch-pos.ps1 -PrinterName "POS-80"
#   .\launch-pos.ps1 -BaseUrl "http://tempuco.test"
#
# Requirements:
#   1. Thermal printer installed in Windows (e.g. POS-80 / XP-80C)
#   2. Google Chrome or Microsoft Edge
#   3. Always open POS with this script (normal Chrome still shows the dialog)

param(
    [ValidateSet('grocery', 'canteen')]
    [string] $Panel = 'grocery',

    # Production TEMPUCO site.
    [string] $BaseUrl = 'https://tempuco-dicnsh.com',

    [string] $PrinterName = 'POS-80',

    [switch] $ListPrinters,

    [switch] $SkipSetDefaultPrinter
)

$ErrorActionPreference = 'Stop'

function Get-PosBaseUrl {
    param([string] $Configured)

    if ($Configured -ne '') {
        return $Configured.TrimEnd('/')
    }

    return 'https://tempuco-dicnsh.com'
}

function Find-Browser {
    $paths = @(
        "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
        "$env:LocalAppData\Google\Chrome\Application\chrome.exe",
        "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
        "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
        "$env:LocalAppData\Microsoft\Edge\Application\msedge.exe"
    )

    foreach ($path in $paths) {
        if (Test-Path -LiteralPath $path) {
            return $path
        }
    }

    return $null
}

function Set-DefaultThermalPrinter {
    param([string] $Name)

    try {
        $printer = Get-CimInstance -ClassName Win32_Printer -Filter "Name='$Name'" -ErrorAction Stop
        $null = Invoke-CimMethod -InputObject $printer -MethodName SetDefaultPrinter
        Write-Host "Default printer set to: $Name" -ForegroundColor Green
        return $true
    } catch {
        Write-Host "Warning: printer '$Name' was not found." -ForegroundColor Yellow
        Write-Host "Set your thermal printer as the Windows default printer, or pass -PrinterName `"Exact Printer Name`"." -ForegroundColor Yellow
        Write-Host "Run: .\launch-pos.ps1 -ListPrinters" -ForegroundColor Yellow
        return $false
    }
}

if ($ListPrinters) {
    Write-Host "Installed printers:" -ForegroundColor Cyan
    Get-CimInstance -ClassName Win32_Printer |
        Sort-Object Name |
        ForEach-Object {
            $mark = if ($_.Default) { ' (DEFAULT)' } else { '' }
            Write-Host ("  - {0}{1}" -f $_.Name, $mark)
        }
    exit 0
}

$browser = Find-Browser
if (-not $browser) {
    Write-Host "Chrome or Edge was not found. Install Google Chrome, then run this again." -ForegroundColor Red
    exit 1
}

if (-not $SkipSetDefaultPrinter) {
    Set-DefaultThermalPrinter -Name $PrinterName | Out-Null
}

$base = Get-PosBaseUrl -Configured $BaseUrl
$path = if ($Panel -eq 'canteen') { '/pos/canteen' } else { '/pos' }
$posUrl = "$base$path"

# Dedicated profile so --kiosk-printing is always applied (existing Chrome
# windows ignore new flags unless a separate user-data-dir is used).
$profileDir = Join-Path $env:LOCALAPPDATA "TEMPUCO\chrome-pos-kiosk"

Write-Host ""
Write-Host "Opening POS with silent printing..." -ForegroundColor Cyan
Write-Host "  URL      : $posUrl"
Write-Host "  Browser  : $browser"
Write-Host "  Profile  : $profileDir"
Write-Host "  Printer  : $PrinterName (Windows default)"
Write-Host ""
Write-Host "Receipts will print with no dialog while you stay in this browser window." -ForegroundColor Green
Write-Host "Do not use a normal Chrome window for POS if you want silent print." -ForegroundColor DarkGray
Write-Host ""

$args = @(
    "--user-data-dir=$profileDir",
    '--kiosk-printing',
    '--disable-print-preview',
    "--app=$posUrl"
)

Start-Process -FilePath $browser -ArgumentList $args
