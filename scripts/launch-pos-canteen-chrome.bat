@echo off
REM TEMPUCO Canteen POS — silent thermal printing (no print dialog)
REM Double-click this file on the canteen cashier PC.

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0launch-pos.ps1" -Panel canteen %*
if errorlevel 1 pause
