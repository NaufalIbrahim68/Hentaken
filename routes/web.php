<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\HenkatenController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ActivityLogMethodController;
use App\Http\Controllers\ActivityLogMaterialController;
use App\Http\Controllers\ActivityLogMachineController;
use App\Http\Controllers\MasterConfirmController;
use App\Http\Controllers\HenkatenApprovalController;
use App\Http\Controllers\ManPowerStationController;
use App\Http\Controllers\ManagementUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ======================================================================
// RUTE PUBLIK (TIDAK PERLU LOGIN)
// ======================================================================
require __DIR__.'/auth.php';


// ======================================================================
// RUTE YANG DILINDUNGI (HARUS LOGIN)
// ======================================================================

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

        // Rute untuk tombol "Change" dari master manpower
        Route::get('/manpower/create-change/{id_manpower}', [HenkatenController::class, 'createChange'])
             ->name('manpower.createChange');

        // Rute store change (dipindahkan/ditambahkan dari luar group)
        Route::post('/manpower/store-change', [HenkatenController::class, 'storeChange'])->name('manpower.storeChange');

        Route::post('/manpower/store', [HenkatenController::class, 'store'])->name('store');
        Route::get('/manpower/start', [HenkatenController::class, 'showStartPage'])->name('manpower.start.page');
        Route::patch('/manpower/start/update', [HenkatenController::class, 'updateStartData'])->name('manpower.start.update');
        
        // Rute search ManPower
        Route::get('/manpower/search', [ManPowerController::class, 'search'])->name('manpower.search');
        
        Route::get('/search-replacement', [ManPowerController::class, 'searchAvailableReplacement'])
        ->name('searchReplacement');
        
        Route::get('/check-after', [HenkatenController::class, 'checkAfter'])->name('checkAfter');


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


        // MACHINE HENKATEN
        Route::get('/machine/create', [HenkatenController::class, 'createMachineHenkaten'])->name('machine.create');
        Route::post('/machine/store', [HenkatenController::class, 'storeMachineHenkaten'])->name('machine.store');
        Route::get('/machine/start', [HenkatenController::class, 'showMachineStartPage'])->name('machine.start.page');
        Route::patch('/machine/start/update', [HenkatenController::class, 'updateMachineStartData'])->name('machine.start.update');
    });

    // ======================================================================
    // API / AJAX ROUTES
    // ======================================================================
    Route::get('/get-stations-by-line', [HenkatenController::class, 'getStationsByLine'])
        ->name('henkaten.stations.by_line');
    Route::get('/get-materials-by-station', [HenkatenController::class, 'getMaterialsByStation'])
        ->name('henkaten.materials.by_station');
        Route::get('/get-methods-by-station', [HenkatenController::class, 'getMethodsByStation'])
        ->name('henkaten.methods.by_station'); 
    Route::get('/henkaten/get-manpower', [HenkatenController::class, 'getManPower'])->name('henkaten.getManPower');

    // ======================================================================
    // MASTER DATA
    // ======================================================================
    Route::prefix('manpower')->name('manpower.master.')->group(function () {
        // CREATE / STORE MASTER
        Route::get('/create-master', [ManPowerController::class, 'create'])->name('create');
        Route::post('/store-master', [ManPowerController::class, 'storeMaster'])->name('store');
    Route::delete('/destroy-master/{id}', [ManPowerController::class, 'destroyMaster'])->name('destroy');

        // 🔹 AJAX dropdown line → station
        Route::get('/stations/by_line', [ManPowerController::class, 'getStationsByLine'])->name('stations.by_line');
        Route::put('/manpower/stations/{id}', [ManPowerController::class, 'updateStation'])->name('stations.update');


        // 🔹 AJAX tambah station
        Route::post('/stations', [ManPowerController::class, 'storeStation'])->name('stations.store');

        // 🔹 AJAX hapus station
        Route::delete('/stations/{id}', [ManPowerController::class, 'destroyStation'])->name('stations.destroy');
    });

    // ======================================================================
    // RESOURCE CONTROLLERS
    // ======================================================================
Route::resource('manpower', ManPowerController::class)->except([
    'show',
    'update'
]);

// 2. Daftarkan 'update' secara manual (menggunakan updateMaster)
Route::put('/manpower/{manpower}', [ManPowerController::class, 'updateMaster'])
    ->name('manpower.update'); 
Route::delete('/manpower-master/{id}', [ManPowerController::class, 'destroyMaster'])->name('manpower.destroyMaster');


    // [BARU] Rute AJAX untuk form Material
Route::get('/get-stations-by-line-area', [MaterialController::class, 'getStationsByLineArea'])
        ->name('get.stations.by.line.area');


    Route::resource('materials', MaterialController::class);
    Route::resource('machines', MachineController::class);
    Route::resource('methods', MethodController::class);

   


    // ======================================================================
// ACTIVITY LOG - (MAN POWER - CRUD Lengkap)
// ======================================================================
Route::prefix('activity-log/manpower')
    ->name('activity.log.manpower') 
    ->controller(ActivityLogController::class) 
    ->group(function () {
    Route::get('/', 'manpower')->name(''); 
    Route::get('/{log}/edit', 'edit')->name('.edit');
    Route::put('/{log}', 'update')->name('.update');
    Route::delete('/{log}', 'destroy')->name('.destroy');
    Route::get('/pdf', 'downloadPDF')->name('.pdf');
});

// ======================================================================
// ACTIVITY LOG - (Method - CRUD Lengkap)
// ======================================================================
Route::prefix('activity-log/method')
    ->name('activity.log.method') 
    ->controller(ActivityLogMethodController::class) 
    ->group(function () {
        Route::get('/', 'index')->name(''); 
        Route::get('/{log}/edit', 'edit')->name('.edit'); 
        Route::put('/{log}', 'update')->name('.update'); 
        Route::delete('/{log}', 'destroy')->name('.destroy'); 
        // FIX: Hapus named arguments action: dan name:
        Route::get('/pdf', 'downloadPDF')->name('.pdf');
    });

    // ======================================================================
// ACTIVITY LOG - (MACHINE - CRUD Lengkap)
// ======================================================================
Route::prefix('activity-log/machine')
    ->name('activity.log.machine') 
    ->controller(ActivityLogMachineController::class) 
    ->group(function () {
    Route::get('/', 'index')->name(''); 
    Route::get('/{log}/edit', 'edit')->name('.edit'); 
    Route::put('/{log}', 'update')->name('.update'); 
    Route::delete('/{log}', 'destroy')->name('.destroy');
    // FIX: Normalisasi URI dan method name agar konsisten dengan yang lain
    Route::get('/pdf', 'downloadPDF')->name('.pdf'); 
});

    // ======================================================================
// ACTIVITY LOG - (MATERIAL - CRUD Lengkap)
// ======================================================================
Route::prefix('activity-log/material')
    ->name('activity.log.material') 
    ->controller(ActivityLogMaterialController::class) 
    ->group(function () {
        Route::get('/', 'index')->name(''); 
        Route::get('/{log}/edit', 'edit')->name('.edit'); 
        Route::put('/{log}', 'update')->name('.update'); 
        Route::delete('/{log}', 'destroy')->name('.destroy'); 
        // FIX: Hapus named arguments action: dan name:
        Route::get('/pdf', 'downloadPDF')->name('.pdf');
    });

    // ======================================================================
    // MASTER DATA ACTIVITY LOG
    // ======================================================================
    Route::prefix('master-log')->name('master.log.')->group(function () {
        Route::get('/manpower', [\App\Http\Controllers\MasterDataLogController::class, 'manpower'])->name('manpower');
        Route::get('/method', [\App\Http\Controllers\MasterDataLogController::class, 'method'])->name('method');
        Route::get('/machine', [\App\Http\Controllers\MasterDataLogController::class, 'machine'])->name('machine');
        Route::get('/material', [\App\Http\Controllers\MasterDataLogController::class, 'material'])->name('material');
        Route::delete('/{id}', [\App\Http\Controllers\MasterDataLogController::class, 'destroy'])->name('destroy');
    });

    

// ======================================================================
// Konfirmasi Approval Section Head
// ======================================================================

Route::middleware(['auth', 'role:Sect Head Produksi|Sect Head PPIC|Sect Head QC'])->group(function () {
    // --- Rute Master Data ---
    Route::get('/konfirmasi/master', [MasterConfirmController::class, 'index'])->name('konfirmasi.master');
    Route::post('/konfirmasi/master/{type}/{id}/approve', [MasterConfirmController::class, 'approve'])->name('konfirmasi.master.approve');
    Route::post('/konfirmasi/master/{type}/{id}/revisi', [MasterConfirmController::class, 'revisi'])->name('konfirmasi.master.revisi');
    Route::get('/master/confirmation', [MasterConfirmController::class, 'index'])->name('master.confirmation');
    Route::get('/api/master-detail/{type}/{id}', [MasterConfirmController::class, 'show']);
    // Halaman list Matrix Man Power
Route::get('/approval/omm', [ManPowerStationController::class, 'matrixApprovalIndex'])->name('approval.omm.index');

// Rute untuk Aksi Approval
Route::post('/approval/omm/{id}/approve', [ManPowerStationController::class, 'approveOmm'])
    ->name('approval.omm.approve');
Route::post('/approval/omm/{id}/revisi', [ManPowerStationController::class, 'reviseOmm'])
    ->name('approval.omm.revisi');
    
// Rute API untuk Modal Detail (sesuai yang digunakan di Alpine.js)
Route::get('/api/omm-detail/{id}', [ManPowerStationController::class, 'showOmmDetail']);

    // ======================================================================
    // HENKATEN APPROVAL - SECTION HEAD
    // ======================================================================

    // Halaman approval
    Route::get('/henkaten/approval', [HenkatenApprovalController::class, 'index'])
        ->name('henkaten.approval.index');

    // Approve Henkaten
    Route::post('/henkaten/approval/{type}/{id}/approve', [HenkatenApprovalController::class, 'approveHenkaten'])
        ->name('henkaten.approval.approve');

    // Revisi Henkaten
    Route::post('/henkaten/approval/{type}/{id}/revisi', [HenkatenApprovalController::class, 'revisiHenkaten'])
        ->name('henkaten.approval.revisi');


    // Rute API untuk mengambil data detail henkaten (untuk modal)
    Route::get('/api/henkaten-detail/{type}/{id}', [HenkatenController::class, 'getHenkatenDetail']);


    });

    // ======================================================================
    // USER MANAGEMENT (Admin Only)
    // ======================================================================
    Route::middleware(['checkrole:Admin'])->group(function () {
        Route::resource('users', ManagementUserController::class);
    });

});