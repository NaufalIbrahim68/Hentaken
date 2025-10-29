<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\HenkatenController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ======================================================================
// DASHBOARD
// ======================================================================
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// ======================================================================
// HENKATEN WORKFLOW
// ======================================================================
Route::prefix('henkaten')->name('henkaten.')->group(function () {

    // MAN POWER HENKATEN
    Route::get('/manpower/create', [HenkatenController::class, 'create'])->name('create');
    Route::post('/manpower/store', [HenkatenController::class, 'store'])->name('store');
    Route::get('/manpower/start', [HenkatenController::class, 'showStartPage'])->name('manpower.start.page');
    Route::patch('/manpower/start/update', [HenkatenController::class, 'updateStartData'])->name('manpower.start.update');
    Route::get('/manpower/search', [ManPowerController::class, 'search'])->name('manpower.search');


    // METHOD HENKATEN
    Route::get('/method/create', [HenkatenController::class, 'createMethodHenkaten'])->name('method.create');
    Route::post('/method/store', [HenkatenController::class, 'storeMethodHenkaten'])->name('method.store');
    Route::get('/method/start', [HenkatenController::class, 'showMethodStartPage'])->name('method.start.page');
    Route::patch('/method/start/update', [HenkatenController::class, 'updateMethodStartData'])->name('method.start.update');

    // MATERIAL HENKATEN 
    Route::get('/material/create', [HenkatenController::class, 'createMaterialHenkaten'])->name('material.create');
    Route::post('/material/store', [HenkatenController::class, 'storeMaterialHenkaten'])->name('material.store');
    Route::get('/material/start', [HenkatenController::class, 'showMaterialStartPage'])->name('material.start.page');
    Route::patch('/material/start/update', [HenkatenController::class, 'updateMaterialStartData'])->name('material.start.update');
    Route::get('/material/search', [MaterialController::class, 'search'])->name('material.search');
});

// ======================================================================
// API / AJAX ROUTES
// ======================================================================
Route::get('/manpower/search', [HenkatenController::class, 'searchManPower'])->name('manpower.search');
Route::get('/get-stations-by-line', [HenkatenController::class, 'getStationsByLine'])
    ->name('henkaten.stations.by_line');

Route::get('/henkaten/get-manpower', [HenkatenController::class, 'getManPower'])->name('henkaten.getManPower');

// ======================================================================
// MASTER DATA
// ======================================================================
Route::prefix('manpower')->name('manpower.master.')->group(function () {
    // CREATE / STORE MASTER
    Route::get('/create-master', [ManPowerController::class, 'create'])->name('create');
    Route::post('/store-master', [ManPowerController::class, 'storeMaster'])->name('store');

    // 🔹 AJAX dropdown line → station
    Route::get('/stations/by_line', [ManPowerController::class, 'getStationsByLine'])->name('stations.by_line');
    Route::put('/manpower/stations/{id}', [ManPowerController::class, 'updateStation'])->name('manpower.master.stations.update');


    // 🔹 AJAX tambah station
    Route::post('/stations', [ManPowerController::class, 'storeStation'])->name('stations.store');

    // 🔹 AJAX hapus station
    Route::delete('/stations/{id}', [ManPowerController::class, 'destroyStation'])->name('stations.destroy');
});

// ======================================================================
// RESOURCE CONTROLLERS
// ======================================================================
Route::resource('manpower', ManPowerController::class)->except(['show']);
Route::delete('/manpower-master/{id}', [ManPowerController::class, 'destroyMaster'])->name('manpower.destroyMaster');

Route::resource('materials', MaterialController::class);
Route::resource('machines', MachineController::class);
Route::resource('methods', MethodController::class);

// ======================================================================
// ACTIVITY LOG
// ======================================================================
Route::prefix('activity-log')->name('activity.log.')->group(function () {
    Route::get('/manpower', [ActivityLogController::class, 'manpower'])->name('manpower');
    Route::get('/machine', [ActivityLogController::class, 'machine'])->name('machine');
    Route::get('/material', [HenkatenController::class, 'showMaterialActivityLog'])->name('material');
    Route::get('/method', [HenkatenController::class, 'showMethodActivityLog'])->name('method'); 
});

// ======================================================================
// TIME SCHEDULER
// ======================================================================
Route::prefix('time-scheduler')->name('scheduler.')->group(function () {
    Route::get('/manpower/create', [ManPowerController::class, 'createManpowerScheduler'])->name('manpower.create');
});
