<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; 
use Illuminate\Support\Facades\Auth; 

// 1. IMPORT SEMUA MODEL (HENKATEN & MASTER)
use App\Models\ManPowerHenkaten;
use App\Models\MachineHenkaten;
use App\Models\MethodsHenkaten;
use App\Models\MaterialHenkaten;
// --- IMPORT MASTER DATA (ASUMSI NAMA MODELNYA INI) ---
use App\Models\ManPower; 
use App\Models\Machine;
use App\Models\Material;
use App\Models\Method;
use App\Models\MethodHenkaten;
use App\Models\Methods; // (atau Methods_m?) Sesuaikan dengan nama model Anda

class AppServiceProvider extends ServiceProvider
{
    // ... (method register) ...

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            
            if (Auth::check()) {
                
                // --- A. Hitung Total Henkaten Pending ---
                $pendingManpower = ManPowerHenkaten::where('status', 'Pending')->count();
                $pendingMachine  = MachineHenkaten::where('status', 'Pending')->count();
                $pendingMethod   = MethodHenkaten::where('status', 'Pending')->count();
                $pendingMaterial = MaterialHenkaten::where('status', 'Pending')->count();
                
                $totalHenkaten = $pendingManpower + $pendingMachine + $pendingMethod + $pendingMaterial;

                // --- B. Hitung Total Master Data Pending ---
                // (INI BAGIAN YANG DIPERBAIKI)
                // Sesuaikan 'status_approval' & 'Pending' jika nama kolom/value Anda berbeda
                $pendingMasterManPower = ManPower::where('status', 'Pending')->count();
                $pendingMasterMachine  = Machine::where('status', 'Pending')->count();
                $pendingMasterMaterial = Material::where('status', 'Pending')->count();
                $pendingMasterMethod   = Method::where('status', 'Pending')->count(); // Sesuaikan nama model ini

                $totalMasterData = $pendingMasterManPower + $pendingMasterMachine + $pendingMasterMaterial + $pendingMasterMethod;

                // --- C. Kirim data ke View ---
                $view->with('pendingHenkatenCount', $totalHenkaten);
                $view->with('pendingMasterDataCount', $totalMasterData);

            } else {
                $view->with('pendingHenkatenCount', 0);
                $view->with('pendingMasterDataCount', 0);
            }
        });
    }
}