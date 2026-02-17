@echo off
cd /d "%~dp0"

:loop
echo Starting stack...
php stack start

echo Running worker...
php stack run:worker

echo Crashed or stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak

goto loop
