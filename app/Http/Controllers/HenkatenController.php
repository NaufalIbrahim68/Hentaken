<?php

namespace App\Http\Controllers;

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
            'shift'             => 'required|string',
            'nama'              => 'required|string',   // nama sebelum (nama_before)
            'nama_after'        => 'required|string',   // nama sesudah
            'keterangan'        => 'nullable|string',
            'line_area'         => 'required|string',
            'station_id_after'  => 'nullable|exists:stations,id', // Ubah validasi
            'effective_date'    => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:effective_date',
            'lampiran'          => 'nullable|image|mimes:jpeg,png|max:2048', // Tambahkan validasi lampiran
        ]);

        // Handle file upload jika ada
        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran')->store('lampiran_henkaten', 'public');
        }

        ManPowerHenkaten::create($validated);

        return redirect()->route('henkaten.form')
            ->with('success', 'Data Henkaten berhasil disimpan.');
    }
}