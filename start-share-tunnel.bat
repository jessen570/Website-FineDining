@echo off
title Maison Aster ERP - Server & Public Tunnel
echo ========================================================
echo  Maison Aster Fine Dining ERP - Server & Public Tunnel
echo ========================================================
echo.
echo Menjalankan Laravel Server pada port 8000...
start "Laravel Server (Maison Aster)" cmd /c "cd /d \"%~dp0backend\" && php artisan serve --host=0.0.0.0 --port=8000"
timeout /t 3 /nobreak >nul

:: Cek apakah MySQL berjalan di port 3306, jika tidak jalankan mysqld
netstat -ano | findstr :3306 >nul
if %errorlevel% neq 0 (
    echo [1/3] Menjalankan database MySQL...
    if exist "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe" (
        start "MySQL Server" /b "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe" --defaults-file="C:\laragon\bin\mysql\mysql-8.0.30-winx64\my.ini"
    )
    timeout /t 2 /nobreak >nul
) else (
    echo [1/3] MySQL sudah aktif di port 3306.
)

:: Cek apakah Laravel berjalan di port 8000
netstat -ano | findstr :8000 >nul
if %errorlevel% neq 0 (
    echo [2/3] Menjalankan Laravel Backend Server (port 8000)...
    start "Laravel Server (Maison Aster)" cmd /c "cd /d \"%~dp0backend\" && php artisan serve --host=0.0.0.0 --port=8000"
    timeout /t 3 /nobreak >nul
) else (
    echo [2/3] Laravel Server sudah aktif di port 8000.
)

echo.
echo Menghubungkan Public Tunnel (https://maison-aster-erp.loca.lt)...
echo Password Tunnel (IP): 114.122.23.179
echo [3/3] Membuka Cloudflare Tunnel publik (Tanpa password / IP)...
echo ========================================================
echo Tautan publik trycloudflare.com akan muncul di bawah ini.
echo Tautan dapat langsung dibagikan dan dibuka teman Anda!
echo ========================================================
echo.
npx localtunnel --port 8000 --subdomain maison-aster-erp
"%~dp0cloudflared.exe" tunnel --url http://127.0.0.1:8000
pause

