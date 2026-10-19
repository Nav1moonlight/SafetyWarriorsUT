@echo off
title Impact Warrior - Disable Auto-Start
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0scripts\remove_autostart.ps1"
echo.
pause
