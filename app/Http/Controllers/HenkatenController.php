<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten;
use App\Models\Station; // Tambahkan import ini

class HenkatenController extends Controller
{
    public function form()
    {
        $stations = Station::all(); 
        
        // PERBAIKAN: Kirim $stations ke view menggunakan compact()
        return view('manpower.create_henkaten', compact('stations'));
    }

  public function store(Request $request)
{
    $validated = $request->validate([
        'shift'                 => 'required|string',
        'nama'                  => 'required|string',
        'nama_after'            => 'required|string',
        'man_power_id'          => 'required|integer', 
        'man_power_id_after'    => 'required|integer', 
        'station_id'            => 'required|integer|exists:stations,id', 
        'keterangan'            => 'nullable|string',
        'line_area'             => 'required|string',
        'effective_date'        => 'nullable|date',
        'end_date'              => 'nullable|date|after_or_equal:effective_date',
        'lampiran'              => 'nullable|image|mimes:jpeg,png|max:2048',
        'serial_number_start'   => 'nullable|string|max:255',
        'serial_number_end'     => 'nullable|string|max:255',
        'time_start'            => 'nullable|date_format:H:i',
        'time_end'              => 'nullable|date_format:H:i|after_or_equal:time_start',
    ]);

    // Simpan lampiran langsung ke public/storage agar bisa dibaca IIS
    if ($request->hasFile('lampiran')) {
        $namaFile = time() . '_' . $request->file('lampiran')->getClientOriginalName();
        $tujuan = public_path('storage/lampiran_henkaten');

        if (!file_exists($tujuan)) {
            mkdir($tujuan, 0777, true);
        }

        $request->file('lampiran')->move($tujuan, $namaFile);

        $validated['lampiran'] = 'lampiran_henkaten/' . $namaFile;
    }

    // Simpan data ke database
    ManPowerHenkaten::create([
        'man_power_id'          => $request->man_power_id,
        'man_power_id_after'    => $request->man_power_id_after,
        'station_id'            => $request->station_id, 
        'shift'                 => $request->shift,
        'nama'                  => $request->nama,
        'nama_after'            => $request->nama_after,
        'line_area'             => $request->line_area,
        'effective_date'        => $request->effective_date,
        'end_date'              => $request->end_date,
        'keterangan'            => $request->keterangan,
        'lampiran'              => $validated['lampiran'] ?? null,
        'serial_number_start'   => $request->serial_number_start,
        'serial_number_end'     => $request->serial_number_end,
        'time_start'            => $request->time_start,
        'time_end'              => $request->time_end,
    ]);

    // Update status man power asli
    $manPowerAsli = ManPower::find($request->man_power_id);
    if ($manPowerAsli) {
        $manPowerAsli->status = 'henkaten';
        $manPowerAsli->save();
    }

    return redirect()->route('henkaten.form')
        ->with('success', 'Data Henkaten berhasil disimpan.');
}

}