@echo off
echo ========================================
echo   API Performance Test - Quick Start
echo ========================================
echo.

echo [1/4] Checking Laragon services...
curl -s http://localhost > nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Laragon tidak running!
    echo.
    echo Solusi:
    echo 1. Buka Laragon
    echo 2. Klik "Start All"
    echo 3. Tunggu Apache hijau
    echo 4. Run script ini lagi
    echo.
    pause
    exit /b 1
)
echo [OK] Laragon running!
echo.

echo [2/4] Clearing cache...
cd /d C:\laragon\www\warehouse
php artisan cache:clear > nul 2>&1
echo [OK] Cache cleared!
echo.

echo [3/4] Checking API routes...
php artisan route:list --path=api | findstr "api/v1" > nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] API routes tidak ditemukan!
    echo Running route:clear...
    php artisan route:clear
)
echo [OK] API routes ready!
echo.

echo [4/4] Opening comparison page...
start http://localhost/comparison.html
echo [OK] Comparison page opened in browser!
echo.

echo ========================================
echo   Comparison Page Features:
echo ========================================
echo.
echo Side-by-Side Comparison:
echo - LEFT: Without API (Old Method)
echo - RIGHT: With API (New Method)
echo.
echo Auto-Test:
echo - Otomatis test kedua method
echo - Lihat perbedaan response time
echo - Lihat improvement metrics
echo.
echo Expected Results:
echo - Old Method: 1500-2000ms (SLOW)
echo - New Method: 50-100ms (FAST)
echo - Improvement: 10-20x faster!
echo.

echo ========================================
echo   Test Instructions:
echo ========================================
echo.
echo 1. Lihat auto-test search (otomatis jalan)
echo 2. Klik tombol "Test Dashboard API"
echo 3. Klik tombol "Test Masters API"
echo 4. Klik tombol "Test Stok API"
echo 5. Lihat Comparison Table di bawah
echo.
echo Expected Results:
echo - Badge HIJAU = Fast (^<100ms) ✓
echo - Badge KUNING = Acceptable (100-200ms)
echo - Badge MERAH = Slow (^>200ms) ✗
echo.
echo ========================================
echo   Manual API Test Commands:
echo ========================================
echo.
echo Test Search:
echo curl http://localhost/api/v1/barang/search?q=test
echo.
echo Test Dashboard:
echo curl http://localhost/api/v1/dashboard/stats
echo.
echo Test Masters:
echo curl http://localhost/api/v1/barang/masters
echo.
echo ========================================
echo.
pause
