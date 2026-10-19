@echo off
title Impact Warrior - Enable Auto-Start
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0scripts\setup_autostart.ps1"
echo.
pause
