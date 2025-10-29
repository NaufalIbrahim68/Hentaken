<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeScheduler;

class ActiveSchedulerController extends Controller
{
    /**
     * Menyimpan pilihan Grup & Shift dari user ke dalam session.
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date',
            'waktu_mulai' => 'required',
            'waktu_berakhir' => 'required',
            'shift' => 'required',
            'grup' => 'required',
        ]);

        // 2. Ambil semua data dari form
        $input = $request->only([
            'grup', 
            'shift', 
            'tanggal_mulai', 
            'tanggal_berakhir', 
            'waktu_mulai', 
            'waktu_berakhir'
        ]);

        // 3. Cari atau Buat (FirstOrCreate) scheduler
        // Ini memastikan kita tidak membuat record duplikat
        $scheduler = TimeScheduler::firstOrCreate($input);

        // 4. SIMPAN KE SESSION
        session([
            'active_scheduler_id' => $scheduler->id,
            'active_grup' => $scheduler->grup,
            'active_shift' => $scheduler->shift,
        ]);

        // 5. Kembalikan user ke dashboard
        return redirect()->route('dashboard.index'); 
    }
}