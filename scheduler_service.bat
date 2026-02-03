@echo off
REM ================================================
REM  Laravel Scheduler Service untuk NSSM
REM  File ini akan dijalankan terus-menerus oleh NSSM
REM ================================================

cd /d "c:\xampp\htdocs\henkaten"

REM Jalankan schedule:work (loop setiap menit)
php artisan schedule:work
