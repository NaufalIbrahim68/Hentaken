<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\MaterialController;
use Illuminate\Support\Facades\Route;

// --- Dashboard Routes ---
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// --- Profile Routes ---
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// --- Man Power Routes ---
Route::prefix('manpower')->name('manpower.')->group(function () {
    Route::get('/', [ManPowerController::class, 'index'])->name('index');
    Route::get('/master/{id}/edit', [ManPowerController::class, 'editMaster'])->name('master.edit');
    Route::put('/master/{id}', [ManPowerController::class, 'updateMaster'])->name('master.update');
    Route::delete('/master/{id}', [ManPowerController::class, 'destroyMaster'])->name('master.destroy');
    Route::get('/{id}/henkaten/create', [ManPowerController::class, 'createHenkaten'])->name('henkaten.create');
    Route::post('/{id}/henkaten/store', [ManPowerController::class, 'storeHenkaten'])->name('henkaten.store');
    Route::delete('/henkaten/{id}', [ManPowerController::class, 'destroy'])->name('henkaten.destroy');
});

// --- Material Routes ---
Route::resource('materials', MaterialController::class);

require __DIR__.'/auth.php';
