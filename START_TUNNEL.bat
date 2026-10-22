@echo off
title Impact Warrior - Cloudflare Public Tunnel (HTTPS)
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0scripts\run_tunnel.ps1"
pause
