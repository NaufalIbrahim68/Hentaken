<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\HenkatenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Rute Publik (Bisa diakses tanpa login) ---
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Redirect agar /henkaten/manpower/select tetap jalan
Route::redirect('/henkaten/manpower/select', '/henkaten/form');

// ==== HENKATEN (PUBLIC) ====
Route::get('/henkaten/form', [HenkatenController::class, 'form'])->name('henkaten.form');
Route::post('/manpower/henkaten/store', [HenkatenController::class, 'store'])
    ->name('manpower.henkaten.store');

// ==== MASTER DATA (PUBLIC) ====
// Man Power
Route::prefix('manpower')->name('manpower.')->group(function () {
    Route::get('/', [ManPowerController::class, 'index'])->name('index');

    // Master Data
    Route::get('/master/create', [ManPowerController::class, 'createMaster'])->name('master.create');
    Route::post('/master', [ManPowerController::class, 'storeMaster'])->name('master.store');
    Route::get('/master/{id}/edit', [ManPowerController::class, 'editMaster'])->name('master.edit');
    Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');
    Route::delete('/master/{id}', [ManPowerController::class, 'destroyMaster'])->name('master.destroy');

    // Henkaten khusus Man Power ID
    Route::get('/{id}/henkaten/create', [ManPowerController::class, 'createHenkaten'])->name('henkaten.create');
    Route::post('/{id}/henkaten/store', [ManPowerController::class, 'storeHenkaten'])->name('henkaten.store_by_id');
    Route::delete('/henkaten/{id}', [ManPowerController::class, 'destroy'])->name('henkaten.destroy');
});

// Material
Route::resource('materials', MaterialController::class);

// --- (Opsional) Hilangkan auth karena website ini tidak pakai login ---
// Kalau memang 100% tidak ada login, baris berikut tidak usah dipakai:
// require __DIR__.'/auth.php';
