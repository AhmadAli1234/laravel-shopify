@echo off
powershell -NoProfile -Command "if (-not (Get-Process -Name redis-server -ErrorAction SilentlyContinue)) { Start-Process -FilePath 'D:\laragon\bin\redis\redis-x64-5.0.14.1\redis-server.exe' -WindowStyle Hidden }"
