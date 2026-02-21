@echo off
echo Starting Reclamation Email Notification Service...
echo This will check for pending reclamations every 5 minutes.
echo Press Ctrl+C to stop.
echo.

:loop
echo [%date% %time%] Checking for pending reclamations...
php bin/console app:check-pending-reclamations
echo.
echo Waiting 5 minutes before next check...
timeout /t 300 /nobreak
goto loop
