@echo off
title Impact Warrior - Start Server
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0scripts\app_runner.ps1" -OpenBrowser
