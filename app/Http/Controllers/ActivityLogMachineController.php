<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MachineHenkaten; // Ganti jika nama model Anda berbeda
use App\Models\Line;
use App\Models\Machine;
use App\Models\Station;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf; 


class ActivityLogMachineController extends Controller
{
   
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
    // Load relasi
    $log->load('station');

    // Dropdown Line Area
    $lineAreas = Station::select('line_area')
                        ->distinct()
                        ->orderBy('line_area', 'asc')
                        ->pluck('line_area');

    // Cek apakah user adalah Leader FA
    $isLeaderFA = auth()->user()->role === 'Leader FA';

    // Jika Leader FA → pakai kategori hardcode
    if ($isLeaderFA) {

    $machinesToDisplay = collect([
        (object)['id' => 1, 'machines_category' => 'PROGRAM'],
        (object)['id' => 2, 'machines_category' => 'Machine & JIG'],
        (object)['id' => 3, 'machines_category' => 'Equipement'],
        (object)['id' => 4, 'machines_category' => 'Kamera'],
    ]);

} else {

        // Untuk role lain: ambil dari DB (yang punya id_machines)
        $machinesToDisplay = Machine::orderBy('machines_category')->get();
    }

    return view('machines.create_henkaten', [
        'log'               => $log,
        'lineAreas'         => $lineAreas,
        'machinesToDisplay' => $machinesToDisplay,
        'isLeaderFA'        => $isLeaderFA
    ]);
}


 public function update(Request $request, MachineHenkaten $log)
{
    $isLeaderFA = auth()->user()->role === 'Leader FA';

    // VALIDASI DINAMIS
    $validatedData = $request->validate([
        'station_id'     => 'required|exists:stations,id',

        // Jika Leader FA → category wajib salah satu dari hardcode
       'category' => $isLeaderFA
    ? 'required|string|in:PROGRAM,Machine & JIG,Equipement,Kamera'
    : 'required|string|max:255',

        'effective_date' => 'required|date',
        'end_date'       => 'nullable|date|after_or_equal:effective_date',
        'time_start'     => 'required',
        'time_end'       => 'required',
        'before_value'   => 'required|string|max:255',
        'after_value'    => 'required|string|max:255',
        'keterangan'     => 'required|string',
        'shift'          => 'required|string',

        'serial_number_start' => 'required|string|max:255',
        'serial_number_end'   => 'required|string|max:255',

        'lampiran'       => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
        'lampiran_2'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
        'lampiran_3'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
    ]);

    // === HANDLE LAMPIRAN 1 ===
    if ($request->hasFile('lampiran')) {
        if ($log->lampiran) {
            Storage::disk('public')->delete($log->lampiran);
        }

        $validatedData['lampiran'] =
            $request->file('lampiran')->store('lampiran/machine', 'public');
    }

    // === HANDLE LAMPIRAN 2 ===
    if ($request->hasFile('lampiran_2')) {
        if ($log->lampiran_2) {
            Storage::disk('public')->delete($log->lampiran_2);
        }

        $validatedData['lampiran_2'] =
            $request->file('lampiran_2')->store('lampiran/machine', 'public');
    }

    // === HANDLE LAMPIRAN 3 ===
    if ($request->hasFile('lampiran_3')) {
        if ($log->lampiran_3) {
            Storage::disk('public')->delete($log->lampiran_3);
        }

        $validatedData['lampiran_3'] =
            $request->file('lampiran_3')->store('lampiran/machine', 'public');
    }

    // setiap edit → kembali Pending
    $validatedData['status'] = 'Pending';
    $validatedData['note']   = null;

    // UPDATE
    $log->update($validatedData);

    return redirect()
            ->route('activity.log.machine')
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

            return redirect()->route('activity.log.machine')
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

public function downloadPDF(Request $request)
{
    // Ambil input filter
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area');

    // MENGGUNAKAN MODEL: MethodHenkaten (Sesuai dengan kode Anda)
    $query = MachineHenkaten::with('station'); // Pastikan 'station' adalah relasi yang benar

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

    $pdf = Pdf::loadView('pdf.activity-log-machine', $data) 
              ->setPaper('a4', 'landscape');
    
    return $pdf->download('Laporan_Henkaten_Machine.pdf');
}

private function determineHenkatenStatus($request)
{
    return $request->status ?? 'Pending';
}

}
