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

        $user = Auth::user();

        if ($user) {

            $role = $user->role;

            // --- HENKATEN PENDING ---
          
            $methods   = MethodHenkaten::where('status', 'Pending');
            $materials = MaterialHenkaten::where('status', 'Pending');
            $machines  = MachineHenkaten::where('status', 'Pending');

            switch ($role) {
                case 'Sect Head QC':
                  
                    $methods->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                    $materials->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                    $machines->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                    break;

                case 'Sect Head PPIC':
                  
                    $methods->where('line_area', 'Delivery');
                    $materials->where('line_area', 'Delivery');
                    $machines->where('line_area', 'Delivery');
                    break;

                case 'Sect Head Produksi':
                    $allowedLineAreas = [
                        'FA L1','FA L2','FA L3','FA L5','FA L6',
                        'SMT L1','SMT L2'
                    ];
                   
                    $methods->whereIn('line_area', $allowedLineAreas);
                    $materials->whereIn('line_area', $allowedLineAreas);
                    $machines->whereIn('line_area', $allowedLineAreas);
                    break;
            }

            $totalHenkaten =  $methods->count() + $materials->count() + $machines->count();

            // --- MASTER DATA PENDING ---
            $pendingMasterManPower = ManPower::where('status', 'Pending')->count();
            $pendingMasterMachine  = Machine::where('status', 'Pending')->count();
            $pendingMasterMaterial = Material::where('status', 'Pending')->count();
            $pendingMasterMethod   = Method::where('status', 'Pending')->count();

            $totalMasterData = $pendingMasterManPower + $pendingMasterMachine + $pendingMasterMaterial + $pendingMasterMethod;

            // --- KIRIM KE VIEW ---
            $view->with('pendingHenkatenCount', $totalHenkaten);
            $view->with('pendingMasterDataCount', $totalMasterData);

        } else {
            $view->with('pendingHenkatenCount', 0);
            $view->with('pendingMasterDataCount', 0);
        }
    });
}

}