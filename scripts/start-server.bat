@echo off
REM Start the app using pm2 (requires pm2 installed in the project)
cd /d "%~dp0\.."
npx pm2 start C:/xampp/php/php.exe --name musa -- -d xdebug.mode=off -S 0.0.0.0:8000 -t .
npx pm2 save
echo Server started under pm2 as 'musa'.
pause
