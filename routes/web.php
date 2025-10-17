<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\HenkatenController;
use App\Http\Controllers\MethodController;
use App\Models\ManPower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Rute Publik ---
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// =========================================================================
// ==== HENKATEN WORKFLOW ==================================================
// =========================================================================
Route::prefix('henkaten')->group(function () {
    // HALAMAN 1: Form untuk membuat data Henkaten baru
    Route::get('/create', [HenkatenController::class, 'create'])->name('henkaten.create');
    Route::post('/store', [HenkatenController::class, 'store'])->name('henkaten.store');

    // HALAMAN 2: Halaman untuk mengisi Serial Number (Start/Manpower Page)
    // View file: resources/views/manpower/create_henkaten_start.blade.php
    Route::get('/start', [HenkatenController::class, 'showStartPage'])->name('henkaten.start.page');
    Route::patch('/update-start', [HenkatenController::class, 'updateStartData'])->name('henkaten.start.update');
});

// API untuk autocomplete pencarian nama Man Power
Route::get('/manpower/search', [HenkatenController::class, 'searchManPower'])->name('manpower.search');


// =========================================================================
// ==== MASTER DATA ========================================================
// =========================================================================

// Master Data Man Power
Route::prefix('manpower')->name('manpower.')->group(function () {
    Route::get('/', [ManPowerController::class, 'index'])->name('index');

    // CRUD Master Data
    Route::get('/master/create', [ManPowerController::class, 'createMaster'])->name('master.create');
    Route::post('/master', [ManPowerController::class, 'storeMaster'])->name('master.store');
    Route::get('/master/{id}/edit', [ManPowerController::class, 'editMaster'])->name('master.edit');
    Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');
    Route::delete('/master/{id}', [ManPowerController::class, 'destroyMaster'])->name('master.destroy');

    // Rute Henkaten lama di dalam ManPower (bisa dievaluasi jika masih perlu)
    Route::get('/{id}/henkaten/create', [ManPowerController::class, 'createHenkaten'])->name('henkaten.create');
    Route::post('/henkaten/store', [ManPowerController::class, 'storeHenkaten'])->name('henkaten.store');
    Route::delete('/henkaten/{id}', [ManPowerController::class, 'destroy'])->name('henkaten.destroy');
});

Route::get('/get-stations-by-line', [HenkatenController::class, 'getStationsByLine'])
     ->name('stations.by_line');

// Master Data Material
Route::resource('materials', MaterialController::class);

// Master Data Machine
Route::resource('machines', MachineController::class);

// Master Data Method
Route::resource('methods', MethodController::class);

