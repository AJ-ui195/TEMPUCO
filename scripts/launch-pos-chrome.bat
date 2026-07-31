@echo off
REM TEMPUCO Grocery POS — silent thermal printing (no print dialog)
REM Double-click this file on the cashier PC. Do not open POS in normal Chrome.

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0launch-pos.ps1" -Panel grocery %*
if errorlevel 1 pause
