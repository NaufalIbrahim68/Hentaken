<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MachineHenkaten; // Ganti jika nama model Anda berbeda
use App\Models\Line;
use App\Models\Station;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf; 


class ActivityLogMachineController extends Controller
{
    /**
     * Menampilkan daftar activity log machine.
     * Ini menggantikan showMachineActivityLog() dari HenkatenController.
     */
  public function index(Request $request): View
    {
        // 2. Ambil semua filter dari request
        $created_date = $request->input('created_date');
        $filterLine = $request->input('line_area'); // <-- BARU

        // 3. Ambil data untuk dropdown Line Area (BARU)
        $lineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();

        // 4. Eager load 'station'
        $query = MachineHenkaten::with('station')
                                   ->latest();

        // 5. Terapkan filter tanggal
        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }
        
        // 6. Terapkan filter Line Area (BARU)
        if ($filterLine) {
            // Asumsi tabel machine_henkaten punya kolom 'line_area'
            $query->where('line_area', $filterLine); 
        }

        $logs = $query->paginate(10);

        // 7. Kirim semua data (termasuk filter) ke view
        return view('machines.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date,
            'lineAreas' => $lineAreas,     // <-- BARU
            'filterLine' => $filterLine      // <-- BARU
        ]);
    }

    /**
     * Menampilkan form untuk mengedit log machine.
     */
public function edit(MachineHenkaten $log): View
    {
        // 1. Eager load relasi 'station' (Ini sudah bagus)
        $log->load('station');

        // 2. Ambil data untuk dropdown Line Area
        //    (Versi ini lebih efisien: 'distinct' & 'order' di DB)
        $lineAreas = Station::select('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area');

        // 3. Query $stations SUDAH TIDAK DIPERLUKAN LAGI!
        //    Alpine.js di Blade akan mengambilnya sendiri.

        // 4. Kirim HANYA data $log dan $lineAreas
        return view('machines.create_henkaten', [ // Sesuaikan path view jika perlu
            'log'       => $log,
            'lineAreas' => $lineAreas,
        ]);
    }

  public function update(Request $request, MachineHenkaten $log)
    {
        // 1. Validasi data (disesuaikan agar cocok dengan form Anda)
        $validatedData = $request->validate([
            // Field dari form
            'station_id'     => 'required|exists:stations,id',
            'category'       => 'required|string|in:Program,Machine & Jig,Equipment,Camera',
            'effective_date' => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:effective_date', // Dibuat nullable
            'time_start'     => 'required',
            'time_end'       => 'required',
            'before_value'   => 'required|string|max:255',
            'after_value'    => 'required|string|max:255', // Nama disesuaikan
            'keterangan'     => 'required|string',         // Dibuat required
            'lampiran'       => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
            'shift'          => 'required|string',
            'serial_number_start' => 'required|string|max:255', // <-- Set ke required
            'serial_number_end'   => 'required|string|max:255', // <-- Set ke required
        ]);

        // 2. Handle file upload (lampiran) secara terpisah
        if ($request->hasFile('lampiran')) {
            // Hapus lampiran lama jika ada
            if ($log->lampiran) {
                Storage::delete('public/' . $log->lampiran);
            }
            // Simpan lampiran baru dan tambahkan path-nya ke data yang divalidasi
            $path = $request->file('lampiran')->store('lampiran/machine', 'public');
            $validatedData['lampiran'] = $path;
        }

        // 3. -----------------------------------------------------------
        //    PERUBAHAN UTAMA DI SINI
        //    Set status kembali ke 'Pending' dan hapus note revisi lama.
        // -----------------------------------------------------------
        $validatedData['status'] = 'Pending';
        $validatedData['note']   = null;

        // 4. Update data log menggunakan data yang sudah divalidasi
        //    ($validatedData sekarang sudah berisi 'status' dan 'note' baru)
        $log->update($validatedData);

        // 5. Ubah pesan sukses
        return redirect()->route('activity.log.machine')
                         ->with('success', 'Data berhasil diupdate dan diajukan kembali untuk approval.');
    }

    /**
     * Menghapus log machine dari database.
     */
    public function destroy(MachineHenkaten $log)
    {
        // Hapus lampiran dari storage jika ada
        if ($log->lampiran) {
            Storage::delete('public/' . $log->lampiran);
        }

        // Hapus data dari database
        $log->delete();

        return redirect()->route('machines.activity-log')
                         ->with('success', 'Data log Machine berhasil dihapus.');
    }

    public function exportPDF(Request $request)
{
    // Ambil input filter (LOGIKA SAMA DENGAN METHOD MANPOWER)
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area');

    // UBAH: Menggunakan model MethodHenkaten
    // Pastikan nama model Anda sudah benar (misal: \App\Models\MethodHenkaten)
    $query = \App\Models\MachineHenkaten::with('station');

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
    $pdf = Pdf::loadView('pdf.activity-log-machine', $data) 
                ->setPaper('a4', 'landscape');
    
    // UBAH: Nama file download
    return $pdf->download('Laporan_Henkaten_machine.pdf');
}
}
