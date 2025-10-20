<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten; // <-- 1. PASTIKAN IMPORT INI

class ActivityLogController extends Controller
{
    // ... method Anda yang lain ...

    /**
     * Menampilkan log untuk Man Power Henkaten.
     */
    public function manpower()
    {
        // 2. Ambil data, sertakan relasi 'station', urutkan terbaru
        $logs = ManPowerHenkaten::with('station')
                                ->latest('updated_at') // Tampilkan yang terbaru di atas
                                ->paginate(10); // Gunakan paginate untuk data banyak

        // 3. Kirim data ke view
        // Perhatikan: nama view 'manpower.activity-log' sesuai
        // dengan lokasi file: /resources/views/manpower/activity-log.blade.php
        return view('manpower.activity-log', [
            'logs' => $logs
        ]);
    }
    
    // ... method Anda yang lain ...
}