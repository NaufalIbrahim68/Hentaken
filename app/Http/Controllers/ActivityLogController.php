<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten;
use App\Models\Station; // <-- 1. ASUMSI: Tambahkan model Station Anda
use Illuminate\Support\Facades\Session; // <-- 2. Tambahkan ini
use Illuminate\Support\Facades\Storage; // <-- 3. Tambahkan ini
use Illuminate\Support\Facades\DB;       // <-- Untuk transaksi
use Illuminate\Database\Eloquent\ModelNotFoundException; // Penting untuk error handling

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

    // Mengambil shift dan grup dari session (hanya sebagai cadangan)
    $currentShift = Session::get('shift');
    $currentGroup = Session::get('grup');

    // PENTING: Kirim variabel $log ke view
    return view('manpower.create_henkaten', compact(
        'log', // <-- Data historis dikirim ke view
        'lineAreas', 
        'currentShift', 
        'currentGroup'
    ));
}
 public function update(Request $request, ManPowerHenkaten $log)
{
    // =========================================================
    // 1. VALIDASI DATA
    // =========================================================
    $validated = $request->validate([
        'shift'               => 'required|string',
        'grup'                => 'required|string', 
        'man_power_id'        => 'required|integer|exists:man_power,id', 
        'man_power_id_after'  => 'required|integer|exists:man_power,id|different:man_power_id', 
        'station_id'          => 'required|integer|exists:stations,id',
        'keterangan'          => 'required|string',
        'line_area'           => 'required|string',
        'effective_date'      => 'required|date',
        'end_date'            => 'required|date|after_or_equal:effective_date',
        'time_start'          => 'required|date_format:H:i',
        'time_end'            => 'required|date_format:H:i|after_or_equal:time_start',
        
        // Lampiran: Opsional (nullable) saat update
        'lampiran'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
        
        // Serial Number: Wajib (required) saat update
        'serial_number_start' => 'required|string|max:255',
        'serial_number_end'   => 'required|string|max:255',
    ], [
        // Custom error messages
        'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.',
        'serial_number_start.required' => 'Serial Number Start wajib diisi saat edit.',
        'serial_number_end.required'   => 'Serial Number End wajib diisi saat edit.',
    ]);

    $newLampiranPath = null;

    try {
        // =========================================================
        // 2. MULAI TRANSAKSI DATABASE
        // =========================================================
        DB::beginTransaction();

        // Ambil data man power BARU (dari form) menggunakan findOrFail
        // Ini akan otomatis error jika ID tidak ditemukan
        $manPowerAsli_Baru = ManPower::findOrFail($validated['man_power_id']);
        $manPowerAfter_Baru = ManPower::findOrFail($validated['man_power_id_after']);

        
        // =========================================================
        // 3. LOGIKA REVERT (JIKA MAN POWER BERUBAH)
        // =========================================================

        // 3A. Cek Man Power ASLI. 
        // Apakah man power 'asli' di form (baru) BERBEDA dengan yang di log (lama)?
        if ($log->man_power_id != $validated['man_power_id']) {
            // Ya, berbeda. Kembalikan status man power LAMA (yang ada di $log)
            $mpAsliLama = ManPower::find($log->man_power_id);
            if ($mpAsliLama) {
                $mpAsliLama->status = 'standby'; // Atau status default Anda
                // $mpAsliLama->station_id = null; // Opsional: sesuaikan dgn business logic
                $mpAsliLama->save();
            }
        }

        // 3B. Cek Man Power PENGGANTI.
        // Apakah man power 'pengganti' di form (baru) BERBEDA dengan yang di log (lama)?
        if ($log->man_power_id_after != $validated['man_power_id_after']) {
            // Ya, berbeda. Kembalikan status man power LAMA (yang ada di $log)
            $mpAfterLama = ManPower::find($log->man_power_id_after);
            if ($mpAfterLama) {
                $mpAfterLama->status = 'standby';
                $mpAfterLama->station_id = null; // Pengganti lama wajib dikosongkan station-nya
                $mpAfterLama->save();
            }
        }

        // =========================================================
        // 4. SIAPKAN DATA & UPDATE LOG HENKATEN
        // =========================================================

        // Siapkan data untuk di-update ke $log (tabel henkaten)
        $dataToUpdate = $validated;
        $dataToUpdate['nama'] = $manPowerAsli_Baru->nama; 
        $dataToUpdate['nama_after'] = $manPowerAfter_Baru->nama;
        $dataToUpdate['status'] = 'pending'; // Set status kembali ke 'pending' untuk approval ulang

        // Handle upload lampiran baru
        if ($request->hasFile('lampiran')) {
            // Hapus lampiran lama jika ada
            if ($log->lampiran && Storage::disk('public')->exists($log->lampiran)) {
                Storage::disk('public')->delete($log->lampiran);
            }
            // Simpan lampiran baru
            $newLampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
            $dataToUpdate['lampiran'] = $newLampiranPath;
        }

        // Update log henkaten itu sendiri dengan data baru
        $log->update($dataToUpdate);

        // =========================================================
        // 5. UPDATE STATUS MAN POWER (YANG BARU)
        // =========================================================

        // 5A. Update Man Power 'Asli' yang BARU (Sesuai perbaikan)
        $manPowerAsli_Baru->status = 'henkaten';
        // $manPowerAsli_Baru->station_id = null; // <-- DINONAKTIFKAN (INI PERBAIKANNYA)
        $manPowerAsli_Baru->save();

        // 5B. Update Man Power 'After' yang BARU
        $manPowerAfter_Baru->status = 'aktif';
        $manPowerAfter_Baru->station_id = $validated['station_id']; // Tugaskan ke station baru
        $manPowerAfter_Baru->save();

        // =========================================================
        // 6. COMMIT & REDIRECT
        // =========================================================
        DB::commit();

        return redirect()->back()
            ->with('success', 'Data Henkaten berhasil diperbarui dan dikirim kembali untuk approval.');

    } catch (\Exception $e) {
        // =========================================================
        // 7. ERROR HANDLING & ROLLBACK
        // =========================================================
        DB::rollBack();
        
        // Hapus lampiran baru jika transaksi gagal
        if ($newLampiranPath && Storage::disk('public')->exists($newLampiranPath)) {
            Storage::disk('public')->delete($newLampiranPath);
        }
        
        // Handle error jika ID tidak ditemukan oleh findOrFail
        if ($e instanceof ModelNotFoundException) {
            return back()->withErrors(['error' => 'Data Man Power (dari form) tidak ditemukan.'])->withInput();
        }

        // Error umum
        return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
    }
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