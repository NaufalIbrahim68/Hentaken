<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; 
use Illuminate\Support\Facades\Auth; 

// 1. IMPORT SEMUA MODEL (HENKATEN & MASTER)
use App\Models\MachineHenkaten;
use App\Models\MaterialHenkaten;
use App\Models\ManPowerHenkaten; // ✅ DITAMBAHKAN
use App\Models\ManPower; 
use App\Models\Machine;
use App\Models\Material;
use App\Models\Method;
use App\Models\MethodHenkaten;
use App\Models\ManPowerManyStation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

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
                $manpowers = ManPowerHenkaten::where('status', 'Pending'); // ✅ DITAMBAHKAN
                $methods   = MethodHenkaten::where('status', 'Pending');
                $materials = MaterialHenkaten::where('status', 'Pending');
                $machines  = MachineHenkaten::where('status', 'Pending');

                switch ($role) {
                    case 'Sect Head QC':
                        $manpowers->whereRaw("LOWER(line_area) LIKE 'incoming%'"); // ✅ DITAMBAHKAN
                        $methods->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                        $materials->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                        $machines->whereRaw("LOWER(line_area) LIKE 'incoming%'");
                        break;

                    case 'Sect Head PPIC':
                        $manpowers->where('line_area', 'Delivery'); // ✅ DITAMBAHKAN
                        $methods->where('line_area', 'Delivery');
                        $materials->where('line_area', 'Delivery');
                        $machines->where('line_area', 'Delivery');
                        break;

                    case 'Sect Head Produksi':
                        $allowedLineAreas = [
                            'FA L1','FA L2','FA L3','FA L5','FA L6',
                            'SMT L1','SMT L2'
                        ];
                        $manpowers->whereIn('line_area', $allowedLineAreas); // ✅ DITAMBAHKAN
                        $methods->whereIn('line_area', $allowedLineAreas);
                        $materials->whereIn('line_area', $allowedLineAreas);
                        $machines->whereIn('line_area', $allowedLineAreas);
                        break;
                }

                // ✅ DITAMBAHKAN manpowers->count()
                $totalHenkaten = $manpowers->count() + $methods->count() 
                               + $materials->count() + $machines->count();

                // --- MASTER DATA PENDING ---
                $pendingMasterManPower = ManPower::where('status', 'Pending')->count();
                $pendingMasterMachine  = Machine::where('status', 'Pending')->count();
                $pendingMasterMaterial = Material::where('status', 'Pending')->count();
                $pendingMasterMethod   = Method::where('status', 'Pending')->count();

                $totalMasterData = $pendingMasterManPower + $pendingMasterMachine 
                                 + $pendingMasterMaterial + $pendingMasterMethod;

                // --- MATRIX MAN POWER PENDING ---
                $pendingMatrixManPower = ManPowerManyStation::where('status', 'Pending')->count();

                // --- KIRIM KE VIEW ---
                $view->with('pendingHenkatenCount', $totalHenkaten);
                $view->with('pendingMasterDataCount', $totalMasterData);
                $view->with('pendingMatrixManPowerCount', $pendingMatrixManPower);

            } else {
                $view->with('pendingHenkatenCount', 0);
                $view->with('pendingMasterDataCount', 0);
                $view->with('pendingMatrixManPowerCount', 0);
            }
        });
    }
}