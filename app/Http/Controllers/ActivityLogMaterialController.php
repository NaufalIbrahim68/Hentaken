<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
// Ganti dengan model MaterialHenkaten Anda
use App\Models\MaterialHenkaten;
use App\Models\Station;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; 
use Barryvdh\DomPDF\Facade\Pdf; 


class ActivityLogMaterialController extends Controller
{
    /**
     * Menampilkan daftar activity log material.
     */
   public function index(Request $request): View
{
    $created_date = $request->input('created_date');
    $filterLine = $request->input('line_area'); 

    $lineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();

    // ==========================================================
    // PERBAIKAN DI SINI: Tambahkan 'material' ke eager loading
    // ==========================================================
    $query = MaterialHenkaten::with(['station', 'material']) // <-- DIPERBAIKI
                               ->latest();
    // ==========================================================

    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }
    
    if ($filterLine) {
        $query->where('line_area', $filterLine);
    }

    $logs = $query->paginate(10);

    return view('materials.activity-log', [
        'logs' => $logs,
        'created_date' => $created_date,
        'lineAreas' => $lineAreas,
        'filterLine' => $filterLine
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
    // 1. Validasi Data
    $validatedData = $request->validate([
        'station_id'          => 'required|exists:stations,id',
        'material_id'         => 'required|exists:materials,id',
        'effective_date'      => 'required|date',
        'end_date'            => 'required|date|after_or_equal:effective_date',
        'time_start'          => 'required',
        'time_end'            => 'required',
        'description_before'  => 'required|string|max:255',
        'description_after'   => 'required|string|max:255',
        'keterangan'          => 'required|string',
        'shift'               => 'required|string',
        'serial_number_start' => 'required|string|max:255',
        'serial_number_end'   => 'required|string|max:255',

        // TAMBAHAN
        'lampiran'    => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
        'lampiran_2'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
        'lampiran_3'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
    ]);

    // 2. Handle Lampiran 1
    if ($request->hasFile('lampiran')) {
        if ($log->lampiran) {
            Storage::disk('public')->delete($log->lampiran);
        }

        $validatedData['lampiran'] =
            $request->file('lampiran')->store('lampiran/material', 'public');
    }

    // 3. Handle Lampiran 2
    if ($request->hasFile('lampiran_2')) {
        if ($log->lampiran_2) {
            Storage::disk('public')->delete($log->lampiran_2);
        }

        $validatedData['lampiran_2'] =
            $request->file('lampiran_2')->store('lampiran/material', 'public');
    }

    // 4. Handle Lampiran 3
    if ($request->hasFile('lampiran_3')) {
        if ($log->lampiran_3) {
            Storage::disk('public')->delete($log->lampiran_3);
        }

        $validatedData['lampiran_3'] =
            $request->file('lampiran_3')->store('lampiran/material', 'public');
    }

    // 5. Set Status & Clear Note
    $validatedData['status'] = 'Pending';
    $validatedData['note']   = null;

    // 6. Update Database
    $log->update($validatedData);

    // 7. Redirect
    return redirect()->route('activity.log.material')
                     ->with('success', 'Data berhasil diupdate dan diajukan kembali untuk approval.');
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

 public function downloadPDF(Request $request)
{
    // Ambil input filter
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area');

    // MENGGUNAKAN MODEL: MethodHenkaten (Sesuai dengan kode Anda)
    $query = MaterialHenkaten::with('station'); // Pastikan 'station' adalah relasi yang benar

    // Filter Tanggal
    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }

    // Filter Line Area
    if ($line_area) {
        // Kolom 'line_area' diasumsikan ada di tabel methods_henkaten
        $query->where('line_area', $line_area); 
    }

    // Ambil SEMUA data
    $logs = $query->latest('created_at')->get();

    // Data untuk dikirim ke view PDF
    $data = [
        'logs' => $logs,
        // Nama variabel filter di PDF view disamakan dengan template sebelumnya
        'filterDate' => $created_date,
        'filterLine' => $line_area,
    ];

    // UBAH: Menggunakan view PDF 'method' (Asumsi nama view Anda adalah 'pdf.activity-log-method')
    $pdf = Pdf::loadView('pdf.activity-log-material', $data) 
              ->setPaper('a4', 'landscape');
    
    // UBAH: Nama file download disesuaikan dengan Method Henkaten
    return $pdf->download('Laporan_Henkaten_Material.pdf');
}

}
