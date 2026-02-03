@echo off
REM ================================================
REM  Batch file untuk menjalankan Laravel Scheduler
REM  (Semua scheduled tasks termasuk email reminder)
REM ================================================

REM Set lokasi project Laravel
cd /d "c:\xampp\htdocs\henkaten"

REM Tampilkan waktu eksekusi
echo ================================================
echo  Laravel Scheduler - %date% %time%
echo ================================================
echo.

REM Jalankan scheduler
echo [INFO] Menjalankan php artisan schedule:run...
echo.

php artisan schedule:run

echo.
echo ================================================
echo  Selesai!
echo ================================================

REM Pause agar bisa melihat output (hapus baris ini jika dijalankan via Task Scheduler)
pause
