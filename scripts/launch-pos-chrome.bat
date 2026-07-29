@echo off
REM TEMPUCO POS — silent receipt printing
REM Uses Chrome --kiosk-printing so Print / auto-print goes straight to the
REM Windows default printer (set POS-80 as default) with no print preview dialog.
REM
REM Normal Chrome cannot skip that dialog — always open POS with this launcher.

set "POS_URL=http://127.0.0.1:8000/pos"
set "PRINTER_NAME=POS-80"

REM Prefer Laragon / local host if you use that instead of artisan serve:
REM set "POS_URL=http://localhost/pos"

set "CHROME="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "CHROME=%LocalAppData%\Google\Chrome\Application\chrome.exe"

if not defined CHROME (
    echo Google Chrome was not found. Install Chrome, then run this again.
    pause
    exit /b 1
)

REM Set POS-80 as the Windows default printer when it exists.
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { $p = Get-Printer -Name '%PRINTER_NAME%' -ErrorAction Stop; (Get-CimInstance -ClassName Win32_Printer -Filter \"Name='%PRINTER_NAME%'\").SetDefaultPrinter() | Out-Null; Write-Host 'Default printer: %PRINTER_NAME%' } catch { Write-Host 'Warning: printer %PRINTER_NAME% not found. Set your receipt printer as Windows default.' }"

start "" "%CHROME%" --kiosk-printing --new-window "%POS_URL%"
