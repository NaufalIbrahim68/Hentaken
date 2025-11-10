<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MachineHenkaten; // Ganti jika nama model Anda berbeda
use App\Models\Line;
use App\Models\Station;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ActivityLogMachineController extends Controller
{
    /**
     * Menampilkan daftar activity log machine.
     * Ini menggantikan showMachineActivityLog() dari HenkatenController.
     */
    public function index(Request $request): View
    {
        $created_date = $request->input('created_date');

        $query = MachineHenkaten::with('station')
                                ->latest();

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->paginate(10);

        return view('machines.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date
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

        // 3. Update data log menggunakan data yang sudah divalidasi
        // Ini jauh lebih aman daripada $request->except()
        $log->update($validatedData);

        return redirect()->route('activity.log.machine')
                         ->with('success', 'Data log Machine berhasil diperbarui.');
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
}
