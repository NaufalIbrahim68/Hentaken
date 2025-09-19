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
        $manPower   = ManPower::all();
    $methods = Method::with('station')->paginate(10); 
        $materials  = Material::all();
        $stations   = Station::all();
        
        // JOIN machines dengan stations
        $machines = DB::table('machines')
            ->join('stations', 'machines.station_id', '=', 'stations.id')
            ->select(
                'machines.*',
                'stations.station_name'
            )
            ->get();

        // contoh current & new part
        $currentPart = $materials->first();
        $newPart     = $materials->skip(1)->first();
        $currentShift = 'Shift A';

        return view('dashboard.index', compact(
            'manPower', 
            'methods', 
            'machines', 
            'materials', 
            'stations', 
            'currentPart', 
            'newPart', 
            'currentShift'
        ));
    }
}
