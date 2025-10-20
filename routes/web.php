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
    
    // --- Method Henkaten (DISESUAIKAN) ---
    Route::get('/method/create', [HenkatenController::class, 'createMethodHenkaten'])->name('method.create');
    Route::post('/method/store', [HenkatenController::class, 'storeMethodHenkaten'])->name('method.store');
    
    // HALAMAN START (jika berlaku umum)
    Route::get('/start', [HenkatenController::class, 'showStartPage'])->name('start.page');
    Route::patch('/update-start', [HenkatenController::class, 'updateStartData'])->name('start.update');
});

// =========================================================================
// ==== API & AJAX ROUTES ==================================================
// =========================================================================

// API untuk autocomplete pencarian
Route::get('/manpower/search', [HenkatenController::class, 'searchManPower'])->name('manpower.search');
Route::get('/method/search', [HenkatenController::class, 'searchMethod'])->name('method.search'); // BARU: Rute untuk autocomplete method

// Mengambil station berdasarkan line area
Route::get('/get-stations-by-line', [HenkatenController::class, 'getStationsByLine'])
     ->name('stations.by_line');

// =========================================================================
// ==== MASTER DATA ========================================================
// =========================================================================

// Master Data Man Power
Route::resource('manpower', ManPowerController::class)->except(['show']);

// Master Data Material
Route::resource('materials', MaterialController::class);

// Master Data Machine
Route::resource('machines', MachineController::class);

// Master Data Method
Route::resource('methods', MethodController::class);


// =========================================================================
// ==== ACTIVITY LOG =======================================================
// =========================================================================

Route::prefix('activity-log')->name('activity.log.')->group(function () {
    Route::get('/manpower', [ActivityLogController::class, 'manpower'])->name('manpower');
    Route::get('/machine', [ActivityLogController::class, 'machine'])->name('machine');
    Route::get('/material', [ActivityLogController::class, 'material'])->name('material');
    Route::get('/method', [ActivityLogController::class, 'method'])->name('method');
});

// Rute-rute lama yang mungkin sudah tidak relevan bisa dihapus atau dikomentari
// Route::get('/henkaten/method/create', [HenkatenController::class, 'createMethodHenkaten'])->name('method.henkaten.create');
// Route::post('/henkaten/method/store', [HenkatenController::class, 'storeMethodHenkaten'])->name('method.henkaten.store');

// Catatan: Pastikan Anda memiliki method 'searchMethod' di dalam HenkatenController
// yang fungsinya mirip dengan 'searchManPower' tetapi untuk mencari model Method.
