<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('manpower')->name('manpower.')->group(function () {
    
    // INDEX - Daftar Master Man Power
    Route::get('/', [ManPowerController::class, 'index'])->name('index');
    
    // MASTER MAN POWER - Full CRUD (bisa edit)
    Route::get('/master/{id}/edit', [ManPowerController::class, 'editMaster'])->name('master.edit');
    Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');
    Route::delete('/master/{id}', [ManPowerController::class, 'destroyMaster'])->name('master.destroy');

    // HENKATEN - Create & Delete ONLY (TIDAK BISA EDIT)
    Route::get('/{id}/henkaten/create', [ManPowerController::class, 'createHenkaten'])->name('henkaten.create');
    Route::post('/{id}/henkaten/store', [ManPowerController::class, 'storeHenkaten'])->name('henkaten.store');
    Route::delete('/henkaten/{id}', [ManPowerController::class, 'destroy'])->name('henkaten.destroy');
});

require __DIR__.'/auth.php';