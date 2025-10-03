<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Station;
use App\Models\ManPowerHenkaten; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class HenkatenController extends Controller
{
    /**
     * Tampilkan form Henkaten (buat baru).
     */
    public function form()
    {
        // ambil daftar stations lalu kirim ke view
        $stations = Station::orderBy('station_code')->get();

        return view('manpower.create_henkaten', compact('stations'));
    }

    /**
     * Simpan data Henkaten.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift' => 'required|in:1,2',
            'line_area' => 'required|string|max:255',
            'nama_before' => 'required|string|max:255',
            'nama_after' => 'required|string|max:255',
            'station_id_after' => 'nullable|exists:stations,id',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'keterangan' => 'nullable|string',
            'lampiran' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $henk = new ManPowerHenkaten();

        // sesuaikan nama kolom model/tabelmu
        $henk->shift = $validated['shift'];
        $henk->line_area = $validated['line_area'];
        $henk->nama_before = $validated['nama_before'];
        $henk->nama_after = $validated['nama_after'];
        $henk->station_id_after = $validated['station_id_after'] ?? null;
        $henk->effective_date = $validated['effective_date'] ?? null;
        $henk->end_date = $validated['end_date'] ?? null;
        $henk->keterangan = $validated['keterangan'] ?? null;

        // Tangani upload lampiran (simpan di storage/app/public/henkaten)
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/henkaten', $filename);
            // simpan path relatif (atau hanya nama file), sesuaikan kebutuhan
            $henk->lampiran = $path; // atau Storage::url($path) jika ingin URL
        }

        $henk->save();

        return redirect()->route('henkaten.form')->with('success', 'Data Henkaten berhasil disimpan.');
    }
}
