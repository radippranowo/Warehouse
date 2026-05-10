@echo off
echo ========================================
echo   API ENDPOINTS TESTING
echo ========================================
echo.

REM Check if Laragon is running
echo [1/5] Checking Laragon status...
tasklist /FI "IMAGENAME eq nginx.exe" 2>NUL | find /I /N "nginx.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Laragon is running
) else (
    echo [ERROR] Laragon is not running!
    echo Please start Laragon first.
    pause
    exit /b 1
)
echo.

REM Clear cache
echo [2/5] Clearing cache...
cd c:\laragon\www\warehouse
php artisan cache:clear >nul 2>&1
echo [OK] Cache cleared
echo.

REM Test API endpoints
echo [3/5] Testing API endpoints...
echo.

echo Testing: GET /api/v1/barang/search?q=test
curl -s -w "\nStatus: %%{http_code}\nTime: %%{time_total}s\n" "http://localhost/api/v1/barang/search?q=test&limit=10" | head -n 5
echo.
echo ---

echo Testing: GET /api/v1/barang/masters
curl -s -w "\nStatus: %%{http_code}\nTime: %%{time_total}s\n" "http://localhost/api/v1/barang/masters" | head -n 5
echo.
echo ---

echo Testing: GET /api/v1/stok/summary?gudang_id=1
curl -s -w "\nStatus: %%{http_code}\nTime: %%{time_total}s\n" "http://localhost/api/v1/stok/summary?gudang_id=1" | head -n 5
echo.
echo ---

echo Testing: GET /api/v1/dashboard/stats
curl -s -w "\nStatus: %%{http_code}\nTime: %%{time_total}s\n" "http://localhost/api/v1/dashboard/stats" | head -n 5
echo.

echo [4/5] Checking routes...
php artisan route:list --path=api --columns=Method,URI,Name | findstr /C:"api/v1"
echo.

echo [5/5] Opening demo page...
start http://localhost/comparison.html
echo.

echo ========================================
echo   TESTING COMPLETE!
echo ========================================
echo.
echo Next steps:
echo 1. Check comparison.html for visual demo
echo 2. Check api-demo.html for interactive testing
echo 3. Review test results above
echo.
pause
