<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // <-- 1. PASTIKAN ADA INI
use App\Models\ManPowerHenkaten; 

class ActivityLogController extends Controller
{
    // ... method Anda yang lain ...

    /**
     * Menampilkan log untuk Man Power Henkaten.
     */
    public function manpower(Request $request)
{
    $created_date = $request->input('created_date');

    $query = \App\Models\ManPowerHenkaten::with('station');

    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }

    $logs = $query->latest('created_at')
                  ->paginate(10)
                  ->appends($request->query());

    return view('manpower.activity-log', [
        'logs' => $logs,
        'created_date' => $created_date,
    ]);
}
    
}