@echo off
title Impact Warrior - Stop Server
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0scripts\stop_runner.ps1"
pause
