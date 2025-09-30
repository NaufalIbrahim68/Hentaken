<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Station;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ================= FOKUS PADA MAN POWER =================
        // 1. Eager load relasi 'station' untuk efisiensi query
        $manPower = ManPower::with('station')->get();
        
        // 2. Kelompokkan data manPower berdasarkan station_id untuk kemudahan di view
        $groupedManPower = $manPower->groupBy('station_id');

        // 3. Siapkan data spesifik untuk pergantian shift langsung di controller
        // Stasiun 4
        $station4Workers = $groupedManPower->get(4, collect()); // Gunakan collect() kosong sebagai default
        $shiftAWorker4 = $station4Workers->where('shift', 'Shift A')->first();
        $shiftBWorker4 = $station4Workers->where('shift', 'Shift B')->first();
        
        // Stasiun 7
        $station7Workers = $groupedManPower->get(7, collect());
        $shiftAWorker7 = $station7Workers->where('shift', 'Shift A')->first();
        $shiftBWorker7 = $station7Workers->where('shift', 'Shift B')->first();
        // =========================================================

        $methods   = Method::with('station')->paginate(5); 
        $materials = Material::all();
        $stations  = Station::all();
        
        // JOIN machines dengan stations
        $machines = DB::table('machines')
            ->join('stations', 'machines.station_id', '=', 'stations.id')
            ->select(
                'machines.*',
                'stations.station_name'
            )
            ->get();

        // Contoh current & new part (sementara ambil dari materials)
        $currentPart = $materials->first();
        $newPart     = $materials->skip(1)->first();
        $currentShift = 'Shift A';

        // Mapping station dengan status (sementara default NORMAL)
        $stationStatuses = $stations->map(function ($station) {
            return [
                'id'    => $station->id,
                'name'  => $station->station_name,
                'status'=> 'NORMAL', // default
            ];
        });

        return view('dashboard.index', compact(
            'manPower',         // Tetap dikirim jika masih digunakan di tempat lain
            'groupedManPower',  // Data yang sudah dikelompokkan
            'shiftAWorker4',    // Data siap pakai untuk shift change
            'shiftBWorker4',
            'shiftAWorker7',
            'shiftBWorker7',
            'methods', 
            'machines', 
            'materials', 
            'stations', 
            'stationStatuses',
            'currentPart', 
            'newPart', 
            'currentShift'
        ));
    }
}