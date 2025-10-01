<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// kalau dashboard tetap mau pakai auth middleware (harus login dulu)
// Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');



});
// Grup route untuk Man Power dan proses Henkaten
Route::prefix('manpower')->name('manpower.')->group(function () {
    
    // Rute INDEX: Menampilkan daftar master dari tabel 'man_power'
    Route::get('/', [ManPowerController::class, 'index'])->name('index');
    
    // --- PENYESUAIAN DI SINI ---
    // BARU: Rute untuk mengelola data MASTER Man Power
    Route::get('/master/{id}/edit', [ManPowerController::class, 'editMaster'])->name('master.edit');
    Route::delete('/master/{id}', [ManPowerController::class, 'destroyMaster'])->name('master.destroy');
    // Anda juga perlu route untuk update data master
    // Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');


    // --- RUTE ANDA UNTUK PROSES HENKATEN (TETAP SAMA) ---
    // Rute AKSI: Membuat entri henkaten baru dari man_power yang dipilih
    Route::post('/{id}/create-henkaten', [ManPowerController::class, 'createHenkaten'])->name('create-henkaten');

    // Rute EDIT: Menampilkan form edit untuk data HENKATEN
    Route::get('/{id}/edit', [ManPowerController::class, 'edit'])->name('edit');
    
    // Rute UPDATE: Memperbarui data HENKATEN yang ada
    Route::put('/{id}', [ManPowerController::class, 'update'])->name('update');
    
    // Rute DESTROY: Menghapus data HENKATEN
    Route::delete('/{id}', [ManPowerController::class, 'destroy'])->name('destroy');

    Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');
});
require __DIR__.'/auth.php';
