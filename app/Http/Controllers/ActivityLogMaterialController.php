<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
// Ganti dengan model MaterialHenkaten Anda
use App\Models\MaterialHenkaten;
use App\Models\Station;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; // Import untuk redirect

class ActivityLogMaterialController extends Controller
{
    /**
     * Menampilkan daftar activity log material.
     */
    public function index(Request $request): View
    {
        $created_date = $request->input('created_date');

        // Eager load 'station' karena view Anda menggunakan $log->station->station_name
        $query = MaterialHenkaten::with('station')
                                 ->latest();

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->paginate(10);

        // Mengarah ke view yang Anda kirimkan sebelumnya
        return view('activity-log.material', [
            'logs' => $logs,
            'created_date' => $created_date
        ]);
    }

    /**
     * Menampilkan form untuk mengedit log material.
     */
   public function edit(MaterialHenkaten $log): View
    {
        // Ganti 'Material' dengan nama model Anda jika beda
        // use App\Models\Material;

        // 1. Eager load relasi
        $log->load('station', 'material'); // Asumsi relasinya 'station' dan 'material'

        // 2. Ambil data Line Area
        $lineAreas = Station::pluck('line_area')->unique()->filter()->toArray();

        // 3. Ambil daftar station untuk line_area yang sedang dipilih
        $stations = [];
        if ($log->station) {
            $stations = Station::where('line_area', $log->station->line_area)
                               ->get(['id', 'station_name']);
        }

        // 4. BARU: Ambil daftar material untuk station yang sedang dipilih
        $materials = [];
        if ($log->station_id) {
            $materials = Material::where('station_id', $log->station_id) // Ganti 'Material'
                                 ->get(['id', 'material_name']);
        }

        // 5. Arahkan ke file form terpadu Anda
        return view('materials.create_henkaten', [
            'log'       => $log,
            'lineAreas' => $lineAreas,
            'stations'  => $stations,
            'materials' => $materials // DIUBAH: Kirim data material
        ]);
    }

  public function update(Request $request, MaterialHenkaten $log): RedirectResponse
    {
        // 1. Validasi data (disesuaikan dengan form BARU Anda)
        $validatedData = $request->validate([
            'station_id'         => 'required|exists:stations,id',
            'material_id'        => 'required|exists:materials,id', // DIUBAH
            'effective_date'     => 'required|date',
            'end_date'           => 'required|date|after_or_equal:effective_date', // Dulu nullable, form Anda 'required'
            'time_start'         => 'required',
            'time_end'           => 'required',
            'description_before' => 'required|string|max:255', // DIUBAH
            'description_after'  => 'required|string|max:255', // DIUBAH
            'keterangan'         => 'required|string',
            'lampiran'           => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
            'shift'              => 'required|string',
            'grup'               => 'required|string', // Pastikan Anda mengirim ini
        ]);

        // 2. Handle file upload
        if ($request->hasFile('lampiran')) {
            if ($log->lampiran) {
                Storage::delete('public/' . $log->lampiran);
            }
            $path = $request->file('lampiran')->store('lampiran/material', 'public');
            $validatedData['lampiran'] = $path;
        }

        // 3. Update data log
        $log->update($validatedData);

        return redirect()->route('activity.log.material')
                         ->with('success', 'Data log Material berhasil diperbarui.');
    }
    /**
     * Menghapus log material dari database.
     */
    public function destroy(MaterialHenkaten $log): RedirectResponse
    {
        // Hapus lampiran dari storage jika ada
        if ($log->lampiran) {
            Storage::delete('public/' . $log->lampiran);
        }

        // Hapus data dari database
        $log->delete();

        return redirect()->route('activity.log.material')
                         ->with('success', 'Data log Material berhasil dihapus.');
    }

    /**
     * Memperbarui log material di database.
     * (Anda juga akan butuh method update nanti)
     */
    // public function update(Request $request, MaterialHenkaten $log)
    // {
    //     // ... (Logika validasi & update untuk Material) ...
    // }
}
