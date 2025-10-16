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
    public function index()
    {
        // 1. Tentukan Shift Secara Dinamis berdasarkan waktu saat ini
        $now = Carbon::now();
        // Kode BARU (Dengan asumsi Shift B adalah shift siang 07:00-19:00)
$currentShift = ($now->hour >= 7 && $now->hour < 19) ? 'Shift B' : 'Shift A';

        // ================= SUMBER DATA HENKATEN =================
        // Ambil data Henkaten Man Power yang sedang aktif sebagai satu-satunya sumber data
        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where('effective_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                      ->orWhereNull('end_date');
            })
            ->latest('effective_date')
            ->get();
        
        // ================= PENGGUNAAN DATA HENKATEN UNTUK SEMUA SEKSI =================
        // Gunakan data Man Power Henkaten untuk section lainnya (UNTUK SEMENTARA)
        $methodHenkatens   = $activeManPowerHenkatens;
        $machineHenkatens  = $activeManPowerHenkatens;
        $materialHenkatens = $activeManPowerHenkatens;

        // ================= PENGAMBILAN DATA REGULER =================
        $manPower = ManPower::with('station')->get();
        $groupedManPower = $manPower->groupBy('station_id');
        
        $methods = Method::with('station')->paginate(5);
        
        // Gunakan Eloquent untuk konsistensi, bukan DB::table()
        $machines = Machine::with('station')->get();
        
        $materials = Material::all();
        $stations  = Station::all();
            
        // Mapping status material (bisa dikembangkan jika perlu)
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
            // Kirim semua variabel Henkaten yang sumber datanya sama
            'activeManPowerHenkatens', // Untuk Man Power
            'methodHenkatens',         // Untuk Method
            'machineHenkatens',        // Untuk Machine
            'materialHenkatens'        // Untuk Material
        ));
    }
}