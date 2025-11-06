<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten;
use App\Models\Station; // <-- 1. ASUMSI: Tambahkan model Station Anda
use Illuminate\Support\Facades\Session; // <-- 2. Tambahkan ini
use Illuminate\Support\Facades\Storage; // <-- 3. Tambahkan ini
use Illuminate\Support\Facades\DB;       // <-- Untuk transaksi

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
        $validated = $request->validate([
            'shift'              => 'required|string',
            'grup'               => 'required|string', 
            'man_power_id'       => 'required|integer|exists:man_power,id', 
            'man_power_id_after' => 'required|integer|exists:man_power,id|different:man_power_id', 
            'station_id'         => 'required|integer|exists:stations,id',
            'keterangan'         => 'required|string',
            'line_area'          => 'required|string',
            'effective_date'     => 'required|date',
            'end_date'           => 'required|date|after_or_equal:effective_date',
            'lampiran'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'time_start'         => 'required|date_format:H:i',
            'time_end'           => 'required|date_format:H:i|after_or_equal:time_start',
        ], [
            'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.'
        ]);

        $newLampiranPath = null;

        try {
            DB::beginTransaction();

            // --- Bagian 1: Revert (Batalkan) Henkaten Lama ---
            $mpAsliLama = ManPower::find($log->man_power_id);
            if ($mpAsliLama) {
                $mpAsliLama->status = 'standby'; 
                $mpAsliLama->station_id = null;
                $mpAsliLama->save();
            }
            $mpAfterLama = ManPower::find($log->man_power_id_after);
            if ($mpAfterLama) {
                $mpAfterLama->status = 'standby';
                $mpAfterLama->station_id = null;
                $mpAfterLama->save();
            }

            // --- Bagian 2: Jalankan Henkaten Baru ---

            $manPowerAsli_Baru = ManPower::find($validated['man_power_id']);
            $manPowerAfter_Baru = ManPower::find($validated['man_power_id_after']);

            if (!$manPowerAsli_Baru || !$manPowerAfter_Baru) {
                throw new \Exception('Data Man Power (dari form) tidak ditemukan.');
            }

            $dataToUpdate = $validated;
            $dataToUpdate['nama'] = $manPowerAsli_Baru->nama; 
            $dataToUpdate['nama_after'] = $manPowerAfter_Baru->nama;
            
            // Set Status kembali ke 'pending' (pastikan 'status' ada di $fillable)
            $dataToUpdate['status'] = 'pending';

            if ($request->hasFile('lampiran')) {
                if ($log->lampiran && Storage::disk('public')->exists($log->lampiran)) {
                    Storage::disk('public')->delete($log->lampiran);
                }
                $newLampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
                $dataToUpdate['lampiran'] = $newLampiranPath;
            }

            $log->update($dataToUpdate);

            // Update Man Power 'Asli' yang BARU
            $manPowerAsli_Baru->status = 'henkaten';
            $manPowerAsli_Baru->station_id = null; 
            $manPowerAsli_Baru->save();

            // Update Man Power 'After' yang BARU
            $manPowerAfter_Baru->status = 'aktif';
            $manPowerAfter_Baru->station_id = $validated['station_id'];
            $manPowerAfter_Baru->save();

            DB::commit();

            // =======================================================
            // PERBAIKAN: Redirect KEMBALI ke halaman edit
            // =======================================================
            return redirect()->back()
                ->with('success', 'Data Henkaten berhasil diperbarui dan dikirim kembali untuk approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($newLampiranPath && Storage::disk('public')->exists($newLampiranPath)) {
                Storage::disk('public')->delete($newLampiranPath);
            }
            
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