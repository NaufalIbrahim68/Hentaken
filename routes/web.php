<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\HenkatenController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ActivityLogController;
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
Route::prefix('henkaten')->name('henkaten.')->group(function () {
    
    // --- Man Power Henkaten ---
    Route::get('/manpower/create', [HenkatenController::class, 'create'])->name('create');
    Route::post('/manpower/store', [HenkatenController::class, 'store'])->name('store');
    Route::get('/manpower/start', [HenkatenController::class, 'showStartPage'])->name('manpower.start.page');
    Route::patch('/manpower/start/update', [HenkatenController::class, 'updateStartData'])->name('manpower.start.update');
    
    // --- Method Henkaten ---
    Route::get('/method/create', [HenkatenController::class, 'createMethodHenkaten'])->name('method.create');
    Route::post('/method/store', [HenkatenController::class, 'storeMethodHenkaten'])->name('method.store');
    
    // BARU: Rute untuk menampilkan halaman Start Henkaten Method
    Route::get('/method/start', [HenkatenController::class, 'showMethodStartPage'])->name('method.start.page');
    
    // DISESUAIKAN: Rute untuk memproses update dari halaman Start Henkaten Method
    Route::patch('/method/start/update', [HenkatenController::class, 'updateMethodStartData'])->name('method.start.update');

});

// =========================================================================
// ==== API & AJAX ROUTES ==================================================
// =========================================================================

// API untuk autocomplete pencarian
Route::get('/manpower/search', [HenkatenController::class, 'searchManPower'])->name('manpower.search');
Route::get('/method/search', [HenkatenController::class, 'searchMethod'])->name('method.search');

// Mengambil station berdasarkan line area
Route::get('/get-stations-by-line', [HenkatenController::class, 'getStationsByLine'])
     ->name('stations.by_line');

// =========================================================================
// ==== MASTER DATA (Tidak ada perubahan) ==================================
// =========================================================================
Route::resource('manpower', ManPowerController::class)->except(['show']);
Route::resource('materials', MaterialController::class);
Route::resource('machines', MachineController::class);
Route::resource('methods', MethodController::class);

// =========================================================================
// ==== ACTIVITY LOG (Tidak ada perubahan) =================================
// =========================================================================
Route::prefix('activity-log')->name('activity.log.')->group(function () {
    Route::get('/manpower', [ActivityLogController::class, 'manpower'])->name('manpower');
    Route::get('/machine', [ActivityLogController::class, 'machine'])->name('machine');
    Route::get('/material', [ActivityLogController::class, 'material'])->name('material');
    Route::get('/method', [ActivityLogController::class, 'method'])->name('method');
});

Route::get('/activity-log/method', [HenkatenController::class, 'showMethodActivityLog'])
     ->name('activity.log.method');