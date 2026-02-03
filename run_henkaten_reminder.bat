@echo off
REM ================================================
REM  Batch file untuk menjalankan Email Reminder
REM  Henkaten Pending > 7 Hari
REM ================================================

REM Set lokasi project Laravel
cd /d "c:\xampp\htdocs\henkaten"

REM Tampilkan waktu eksekusi
echo ================================================
echo  Henkaten Reminder Email - %date% %time%
echo ================================================
echo.

REM Jalankan command reminder
echo [INFO] Menjalankan php artisan henkaten:reminder...
echo.

php artisan henkaten:reminder

echo.
echo ================================================
echo  Selesai!
echo ================================================

REM Pause agar bisa melihat output (hapus baris ini jika dijalankan via Task Scheduler)
pause
