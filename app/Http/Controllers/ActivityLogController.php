<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten;
use App\Models\Station; // <-- 1. ASUMSI: Tambahkan model Station Anda
use Illuminate\Support\Facades\Session; // <-- 2. Tambahkan ini
use Illuminate\Support\Facades\Storage; // <-- 3. Tambahkan ini

class ActivityLogController extends Controller
{
    /**
     * Menampilkan log untuk Man Power Henkaten.
     */
    public function manpower(Request $request)
    {
        $created_date = $request->input('created_date');

        $query = \App\Models\ManPowerHenkaten::with('station');

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->latest('created_at')
            ->paginate(10)
            ->appends($request->query());

        return view('manpower.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date,
        ]);
    }

    // ==========================================================
    // BARU: Method untuk EDIT
    // ==========================================================
    /**
     * Menampilkan form untuk mengedit log.
     */
   public function edit(ManPowerHenkaten $log) // Gunakan Route Model Binding
    {
        // Mendapatkan Line Area unik dari database
        $lineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();

        // Mengambil shift dan grup dari session
        $currentShift = Session::get('shift');
        $currentGroup = Session::get('grup');

        // Pastikan Anda juga mengirimkan data $log yang akan diedit ke view
        // Blade akan menggunakan $log ini untuk mengisi form
        return view('manpower.create_henkaten', compact('log', 'lineAreas', 'currentShift', 'currentGroup'));
    }

    public function update(Request $request, ManPowerHenkaten $log)
    {
        // Validasi data yang masuk
        $validatedData = $request->validate([
            'line_area' => 'required|string|max:255',
            'station_id' => 'required|exists:line_stations,id',
            'effective_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:effective_date',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'man_power_id' => 'required|exists:man_powers,id', // Karyawan sebelum
            'man_power_id_after' => 'required|exists:man_powers,id|different:man_power_id', // Karyawan sesudah, harus berbeda
            'keterangan' => 'required|string',
            'lampiran' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Lampiran tidak wajib di update
            'shift' => 'required|string', // Pastikan shift dan grup juga divalidasi
            'grup' => 'required|string',
        ], [
            'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.',
            // Tambahkan pesan error kustom lainnya jika diperlukan
        ]);

        // Tangani upload lampiran jika ada file baru
        if ($request->hasFile('lampiran')) {
            // Hapus lampiran lama jika ada
            if ($log->lampiran) {
                // Storage::delete('public/' . $log->lampiran); // Pastikan ini aman
            }
            $lampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
            $validatedData['lampiran'] = $lampiranPath;
        }

        // Update data log
        $log->update($validatedData);

return redirect('/henkaten/approval')->with('success', 'Data Henkaten Man Power berhasil diperbarui!');   
 }

    // ==========================================================
    // BARU: Method untuk DESTROY (Hapus)
    // ==========================================================
    /**
     * Menghapus log dari database.
     */
    public function destroy(ManPowerHenkaten $log)
    {
        // Hapus file lampiran dari storage
        if ($log->lampiran) {
            Storage::disk('public')->delete($log->lampiran);
        }

        $log->delete();

        return redirect()->route('activity.log.manpower')
            ->with('success', 'Log Henkaten Man Power berhasil dihapus.');
    }
}