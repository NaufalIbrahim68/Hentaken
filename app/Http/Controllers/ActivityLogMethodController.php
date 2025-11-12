<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;
use App\Models\MethodHenkaten;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; 


class ActivityLogMethodController extends Controller
{
   

public function index(Request $request)
{
    // Ambil input filter
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area'); // <-- Tambahan

    // Asumsi model Anda bernama MethodHenkaten
    $query = \App\Models\MethodHenkaten::with('station'); 

    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }

    // <-- TAMBAHAN: Filter berdasarkan line_area di tabel station
    if ($line_area) {
        $query->whereHas('station', function ($q) use ($line_area) {
            $q->where('line_area', $line_area);
        });
    }

    $logs = $query->latest('created_at')
        ->paginate(10)
        ->appends($request->query());

    // <-- TAMBAHAN: Ambil data unik line_area untuk dropdown
    $lineAreas = Station::distinct()->whereNotNull('line_area')->pluck('line_area');

    return view('methods.activity-log', [ // Pastikan nama view-nya benar
        'logs' => $logs,
        'created_date' => $created_date,
        'lineAreas' => $lineAreas,     // <-- Kirim ke view
        'line_area' => $line_area,         // <-- Kirim ke view
    ]);
}

    // ==========================================================
    // TAMBAHKAN FUNGSI-FUNGSI BARU DI BAWAH INI
    // ==========================================================

    /**
     * Menampilkan form untuk mengedit log.
     */
 public function edit(MethodHenkaten $log)
    {
        // 1. Ambil DAFTAR NAMA line_area (sebagai string) dari tabel stations
        //    Query ini mengambil nilai unik (distinct) dari kolom 'line_area'
        $lineAreas = Station::select('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area');

        // 2. Ambil DAFTAR STATIONS (sebagai objek) yang sesuai dengan
        //    line_area yang sedang diedit ($log)
        //    Ini dibutuhkan agar dropdown "Station" terisi saat halaman dimuat
        $stations = Station::where('line_area', $log->station->line_area)
                           ->orderBy('station_name', 'asc') // Asumsi nama kolomnya 'station_name'
                           ->get();

        // 3. Kirim semua data yang dibutuhkan ke view
        return view('methods.create_henkaten', compact(
            'log',        // Data log yang akan diedit
            'lineAreas',  // Daftar untuk dropdown "Line Area"
            'stations'    // Daftar untuk dropdown "Station" (yang di-handle Alpine)
        ));
    }
    /**
     * Memperbarui log di database.
     */
   public function update(Request $request, MethodHenkaten $log) // <-- Menggunakan route-model binding Anda
    {
        // Validasi data
        $request->validate([
            'keterangan_after' => 'nullable|string|max:255',
            'line_area' => 'nullable|string|max:100',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
             'serial_number_start' => 'required|string|max:255',
             'serial_number_end'   => 'required|string|max:255', 

            
        ]);

        // Ambil semua data kecuali lampiran
        $data = $request->except('lampiran');

        // -----------------------------------------------------------
        // PERUBAHAN UTAMA DI SINI
        // -----------------------------------------------------------
        // 1. Set status kembali ke 'Pending' untuk diajukan ulang ke Section Head.
        $data['status'] = 'Pending';
        
        // 2. [Opsional] Hapus 'note' revisi sebelumnya (best practice).
        $data['note'] = null;
        // -----------------------------------------------------------

        // Handle file upload (lampiran)
        if ($request->hasFile('lampiran')) {
            // Hapus lampiran lama jika ada
            if ($log->lampiran) {
                Storage::delete('public/' . $log->lampiran);
            }

            // Simpan lampiran baru (path dari kode Anda)
            $path = $request->file('lampiran')->store('lampiran/method', 'public');
            $data['lampiran'] = $path;
        }

        // Update data log dengan $data yang sudah berisi status 'Pending'
        $log->update($data);

        // Ubah pesan sukses agar lebih jelas
        return redirect()->route('activity.log.method')
                         ->with('success', 'Data berhasil diupdate dan diajukan kembali untuk approval.');
    }

    /**
     * Menghapus log dari database.
     */
    public function destroy(MethodHenkaten $log) // <-- GANTI INI jika model Anda berbeda
    {
        // Hapus lampiran dari storage jika ada
        if ($log->lampiran) {
            Storage::delete('public/' . $log->lampiran);
        }

        // Hapus data dari database
        $log->delete();

        return redirect()->route('activity.log.method')
                         ->with('success', 'Data log berhasil dihapus.');
    }

  public function downloadPDF(Request $request)
{
    // Ambil input filter (LOGIKA SAMA DENGAN METHOD MANPOWER)
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area');

    // UBAH: Menggunakan model MethodHenkaten
    // Pastikan nama model Anda sudah benar (misal: \App\Models\MethodHenkaten)
    $query = \App\Models\MethodHenkaten::with('station');

    // Filter Tanggal (Sama persis)
    if ($created_date) {
        $query->whereDate('created_at', $created_date);
    }

    // Filter Line Area (Sama persis)
    // Ini mengasumsikan 'methods_henkaten' punya kolom 'line_area'
    if ($line_area) {
        $query->where('line_area', $line_area);
    }

    // Ambil SEMUA data (Sama persis)
    $logs = $query->latest('created_at')->get();

    // Data untuk dikirim ke view PDF (Sama persis)
    $data = [
        'logs' => $logs,
        'filterDate' => $created_date,
        'filterLine' => $line_area,
    ];

    // UBAH: Menggunakan view PDF 'method'
    $pdf = Pdf::loadView('pdf.activity-log-method', $data) 
                ->setPaper('a4', 'landscape');
    
    // UBAH: Nama file download
    return $pdf->download('Laporan_Henkaten_Method.pdf');
}
}
