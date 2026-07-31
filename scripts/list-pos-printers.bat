@echo off
REM List Windows printers so you can set the correct -PrinterName in launch-pos.ps1
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0launch-pos.ps1" -ListPrinters
echo.
pause
