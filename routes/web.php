<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\HenkatenController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MasterConfirmController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ======================================================================
// RUTE PUBLIK (TIDAK PERLU LOGIN)
// ======================================================================

// Rute untuk menampilkan halaman login, memproses login, logout,
// register (jika ada), dll.
// File ini HARUS berada DI LUAR middleware 'auth'
// agar pengguna bisa mengakses halaman login.
require __DIR__.'/auth.php';


// ======================================================================
// RUTE YANG DILINDUNGI (HARUS LOGIN)
// ======================================================================

// Semua rute di dalam grup ini mewajibkan pengguna untuk login.
// Jika belum login, mereka akan otomatis diarahkan ke halaman login.
Route::middleware(['auth'])->group(function () {

    // ======================================================================
    // DASHBOARD
    // ======================================================================
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

     // Hanya Admin
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('dashboard.admin');

    // Leader SMT
    Route::get('/dashboard/leader-smt', [DashboardController::class, 'leaderSmt'])
        ->middleware('role:Leader SMT')
        ->name('dashboard.leader_smt');

    // Sect Head Produksi
    Route::get('/dashboard/secthead-produksi', [DashboardController::class, 'sectheadProduksi'])
        ->middleware('role:Sect Head Produksi')
        ->name('dashboard.secthead_produksi');

    // Sect Head PPIC
    Route::get('/dashboard/secthead-ppic', [DashboardController::class, 'sectheadPpic'])
        ->middleware('role:Sect Head PPIC')
        ->name('dashboard.secthead_ppic');

    // Sect Head QC
    Route::get('/dashboard/secthead-qc', [DashboardController::class, 'sectheadQc'])
        ->middleware('role:Sect Head QC')
        ->name('dashboard.secthead_qc');

    // Aksi tambahan dashboard
    Route::post('/dashboard/set-grup', [DashboardController::class, 'setGrup'])->name('dashboard.setGrup');
    Route::get('/dashboard/reset-grup', [DashboardController::class, 'resetGrup'])->name('dashboard.resetGrup');
    Route::post('/dashboard/set-line', [DashboardController::class, 'setLine'])->name('dashboard.setLine');

    Route::get('/session/line', function() {
    return response()->json(['line' => session('active_line')]);
});


    
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


        // ==================================================
        // BARU: MACHINE HENKATEN
        // ==================================================
        Route::get('/machine/create', [HenkatenController::class, 'createMachineHenkaten'])->name('machine.create');
        Route::post('/machine/store', [HenkatenController::class, 'storeMachineHenkaten'])->name('machine.store');
        Route::get('/machine/start', [HenkatenController::class, 'showMachineStartPage'])->name('machine.start.page');
        Route::patch('/machine/start/update', [HenkatenController::class, 'updateMachineStartData'])->name('machine.start.update');
    });

    // ======================================================================
    // API / AJAX ROUTES
    // ======================================================================
    Route::get('/manpower/search', [HenkatenController::class, 'searchManPower'])->name('manpower.search');
    Route::get('/get-stations-by-line', [HenkatenController::class, 'getStationsByLine'])
        ->name('henkaten.stations.by_line');
        Route::get('/get-materials-by-station', [HenkatenController::class, 'getMaterialsByStation'])
        ->name('henkaten.materials.by_station');
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
        Route::get('/machine', [HenkatenController::class, 'showMachineActivityLog'])->name('machine');
        Route::get('/material', [HenkatenController::class, 'showMaterialActivityLog'])->name('material');
        Route::get('/method', [HenkatenController::class, 'showMethodActivityLog'])->name('method'); 
    });

    
 // ======================================================================
    // Konfirmasi Approval Section Head
    // ======================================================================
    Route::middleware(['auth', 'role:secthead'])->group(function () {
    Route::get('/konfirmasi/master', [MasterConfirmController::class, 'index'])->name('konfirmasi.master');
    Route::post('/konfirmasi/master/{type}/{id}/approve', [MasterConfirmController::class, 'approve'])->name('konfirmasi.master.approve');
    Route::post('/konfirmasi/master/{type}/{id}/revisi', [MasterConfirmController::class, 'revisi'])->name('konfirmasi.master.revisi');
        Route::get('/master/confirmation', [ManPowerController::class, 'confirmation'])->name('master.confirmation');
Route::get('/henkaten/approval', [HenkatenController::class, 'approval'])->name('henkaten.approval');
Route::get('/api/master-detail/{type}/{id}', [MasterConfirmController::class, 'show']);

});




    // (Rute Profile, jika Anda menggunakannya, juga harus ada di dalam sini)
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

}); // <-- INI ADALAH AKHIR DARI GROUP MIDDLEWARE 'AUTH'

