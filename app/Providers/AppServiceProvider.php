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

                $normalizedRole = strtolower(str_replace([' ', '_'], '', $role));
                
                $lineAreaFilter = null;
                $allowedAreas = null;

                if ($normalizedRole === 'sectheadqc') {
                    $lineAreaFilter = 'Incoming';
                } elseif ($normalizedRole === 'sectheadppic') {
                    $lineAreaFilter = 'Delivery';
                } elseif ($normalizedRole === 'sectheadproduksi') {
                    $allowedAreas = ['FA L1','FA L2','FA L3','FA L5','FA L6','SMT L1','SMT L2'];
                }

                // --- HENKATEN PENDING ---
                $hManpowers = ManPowerHenkaten::where('status', 'Pending');
                $hMethods   = MethodHenkaten::where('status', 'Pending');
                $hMaterials = MaterialHenkaten::where('status', 'Pending');
                $hMachines  = MachineHenkaten::where('status', 'Pending');

                if ($lineAreaFilter) {
                    $hManpowers->where('line_area', $lineAreaFilter);
                    $hMethods->where('line_area', $lineAreaFilter);
                    $hMaterials->where('line_area', $lineAreaFilter);
                    $hMachines->where('line_area', $lineAreaFilter);
                } elseif ($allowedAreas) {
                    $hManpowers->whereIn('line_area', $allowedAreas);
                    $hMethods->whereIn('line_area', $allowedAreas);
                    $hMaterials->whereIn('line_area', $allowedAreas);
                    $hMachines->whereIn('line_area', $allowedAreas);
                }

                $totalHenkaten = $hManpowers->count() + $hMethods->count() 
                               + $hMaterials->count() + $hMachines->count();

                // --- MASTER DATA PENDING ---
                $mManpowers = ManPower::where('status', 'Pending');
                $mMachines  = Machine::where('status', 'Pending');
                $mMaterials = Material::where('status', 'Pending');
                $mMethods   = Method::where('status', 'Pending');

                // --- MATRIX MAN POWER PENDING ---
                $mMatrix = ManPowerManyStation::where('status', 'Pending');

                if ($lineAreaFilter) {
                    $mManpowers->where(function($q) use ($lineAreaFilter) {
                        $q->whereHas('station', fn($sq) => $sq->where('line_area', $lineAreaFilter))
                          ->orWhere(fn($subq) => $subq->whereNull('station_id')->where('line_area', $lineAreaFilter));
                    });
                    $mMachines->whereHas('station', fn($sq) => $sq->where('line_area', $lineAreaFilter));
                    $mMaterials->whereHas('station', fn($sq) => $sq->where('line_area', $lineAreaFilter));
                    $mMethods->whereHas('station', fn($sq) => $sq->where('line_area', $lineAreaFilter));
                    $mMatrix->whereHas('station', fn($sq) => $sq->where('line_area', $lineAreaFilter));
                } elseif ($allowedAreas) {
                    $mManpowers->where(function($q) use ($allowedAreas) {
                        $q->whereHas('station', fn($sq) => $sq->whereIn('line_area', $allowedAreas))
                          ->orWhere(fn($subq) => $subq->whereNull('station_id')->whereIn('line_area', $allowedAreas));
                    });
                    $mMachines->whereHas('station', fn($sq) => $sq->whereIn('line_area', $allowedAreas));
                    $mMaterials->whereHas('station', fn($sq) => $sq->whereIn('line_area', $allowedAreas));
                    $mMethods->whereHas('station', fn($sq) => $sq->whereIn('line_area', $allowedAreas));
                    $mMatrix->whereHas('station', fn($sq) => $sq->whereIn('line_area', $allowedAreas));
                }

                $totalMasterData = $mManpowers->count() + $mMachines->count() 
                                 + $mMaterials->count() + $mMethods->count();
                $pendingMatrixManPower = $mMatrix->count();

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