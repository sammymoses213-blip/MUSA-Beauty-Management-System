@echo off
REM Start both MySQL and PHP server under PM2
REM This ensures the full system is running

echo Starting MySQL...
start "" "C:\xampp\mysql\bin\mysqld" --standalone --datadir="C:\xampp\mysql\data"
timeout /t 3 /nobreak

echo Starting MUSA Beauty Management System under PM2...
cd /d "%~dp0"
npx pm2 start "C:/xampp/php/php.exe" --name musa -- -d xdebug.mode=off -S 0.0.0.0:8000 -t .
npx pm2 save

echo.
echo ============================================
echo MUSA Beauty Management System is starting...
echo ============================================
echo.
echo Access the application at:
echo   http://localhost:8000
echo.
echo Check status:
echo   npx pm2 ls
echo   npx pm2 logs musa
echo.
echo Stop the server:
echo   npx pm2 stop musa
echo.
pause
