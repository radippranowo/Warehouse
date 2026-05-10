@echo off
cd /d "c:\laragon\www\warehouse"
echo Clearing Laravel cache...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo.
echo Cache cleared successfully!
pause
