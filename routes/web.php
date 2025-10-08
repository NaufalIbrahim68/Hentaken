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

// --- Rute Publik (Bisa diakses tanpa login) ---
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Redirect agar /henkaten/manpower/select tetap jalan
Route::redirect('/henkaten/manpower/select', '/henkaten/form');

// ==== HENKATEN (PUBLIC) ====
// Form tampil
Route::get('/henkaten/form', [HenkatenController::class, 'form'])->name('henkaten.form');
// Proses simpan
Route::post('/henkaten/store', [HenkatenController::class, 'store'])->name('henkaten.store');

// ==== MASTER DATA (PUBLIC) ====
Route::prefix('manpower')->name('manpower.')->group(function () {
    Route::get('/', [ManPowerController::class, 'index'])->name('index');

    // Master Data
    Route::get('/master/create', [ManPowerController::class, 'createMaster'])->name('master.create');
    Route::post('/master', [ManPowerController::class, 'storeMaster'])->name('master.store');
    Route::get('/master/{id}/edit', [ManPowerController::class, 'editMaster'])->name('master.edit');
    Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');
    Route::delete('/master/{id}', [ManPowerController::class, 'destroyMaster'])->name('master.destroy');

    // Henkaten routes
    Route::get('/{id}/henkaten/create', [ManPowerController::class, 'createHenkaten'])->name('henkaten.create');
    
    Route::post('/henkaten/store', [ManPowerController::class, 'storeHenkaten'])->name('henkaten.store'); // ADD THIS LINE
    Route::delete('/henkaten/{id}', [ManPowerController::class, 'destroy'])->name('henkaten.destroy');
});

// Material
Route::resource('materials', MaterialController::class);

// --- Machine Routes ---
Route::resource('machines', MachineController::class);

Route::resource('methods', MethodController::class);

Route::get('/manpower/search', function (Request $request) {
    $q = $request->query('q');
    $results = ManPower::where('nama', 'like', "%$q%")
                        ->orderBy('nama')
                        ->limit(10)
                        ->get();
    return response()->json($results);
});