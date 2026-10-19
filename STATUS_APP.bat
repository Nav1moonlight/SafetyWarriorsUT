@echo off
title Impact Warrior - Status Monitor
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0scripts\status_runner.ps1"
echo.
pause
