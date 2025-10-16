<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Station;
// Impor model ManPowerHenkaten
use App\Models\ManPowerHenkaten; 
use Carbon\Carbon;

class DashboardController extends Controller
{
   // DashboardController.php

public function index()
{
    // 1. Tentukan Shift Secara Dinamis berdasarkan waktu saat ini
    $now = Carbon::now();
    $currentShift = ($now->hour >= 7 && $now->hour < 19) ? 'Shift B' : 'Shift A';

    // ================= SUMBER DATA HENKATEN (SUDAH DISESUAIKAN) =================
    // Ambil Henkaten yang belum berakhir, tanpa peduli kapan mulainya
    $activeManPowerHenkatens = ManPowerHenkaten::with('station')
        ->where(function ($query) use ($now) {
            $query->where('end_date', '>=', $now)
                  ->orWhereNull('end_date');
        })
        ->latest('effective_date')
        ->get();
       
    
    // ================= PENGGUNAAN DATA HENKATEN UNTUK SEMUA SEKSI =================
    $methodHenkatens   = $activeManPowerHenkatens;
    $machineHenkatens  = $activeManPowerHenkatens;
    $materialHenkatens = $activeManPowerHenkatens;

    // ================= PENGAMBILAN DATA REGULER =================
    $manPower = ManPower::with('station')->get();
    $groupedManPower = $manPower->groupBy('station_id');
    
    $methods = Method::with('station')->paginate(5);
    
    $machines = Machine::with('station')->get();
    
    $materials = Material::all();
    $stations  = Station::all();
        
    $stationStatuses = $stations->map(function ($station) {
        return [
            'id'     => $station->id,
            'name'   => $station->station_name,
            'status' => 'NORMAL', // default
        ];
    });

    // Kirim semua variabel yang relevan ke view
    return view('dashboard.index', compact(
        'groupedManPower',
        'currentShift',
        'methods',
        'machines',
        'materials',
        'stations',
        'stationStatuses',
        'activeManPowerHenkatens',
        'methodHenkatens',
        'machineHenkatens',
        'materialHenkatens'
    ));
}
}