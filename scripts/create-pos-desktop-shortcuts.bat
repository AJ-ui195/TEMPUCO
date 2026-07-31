@echo off
REM Create Desktop shortcuts + set POS-80 as default printer (production)
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-pos-printer.ps1"
echo.
pause
