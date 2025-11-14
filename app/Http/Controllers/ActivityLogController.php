<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten;
use App\Models\Station;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Barryvdh\DomPDF\Facade\Pdf;
class ActivityLogController extends Controller
{

    /**
     * Menampilkan halaman activity log Man Power dengan filter.
     */
    public function manpower(Request $request)
    {
        // Ambil input filter
        $created_date = $request->input('created_date');
        $line_area = $request->input('line_area'); // <-- 2. TAMBAHAN: Ambil filter line_area

        $query = \App\Models\ManPowerHenkaten::with('station');

        // Terapkan filter tanggal
        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        // <-- 3. TAMBAHAN: Terapkan filter line_area
        if ($line_area) {
            $query->where('line_area', $line_area);
        }

        $logs = $query->latest('created_at')
            ->paginate(10)
            ->appends($request->query());

        // <-- 4. TAMBAHAN: Ambil data unik line_area untuk dropdown
        $lineAreas = Station::distinct()->whereNotNull('line_area')->pluck('line_area');

        return view('manpower.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date,
            'lineAreas' => $lineAreas,     // <-- 5. TAMBAHAN: Kirim ke view
            'line_area' => $line_area,         // <-- 6. TAMBAHAN: Kirim ke view
        ]);
    }

    /**
     * Menampilkan form edit untuk log henkaten.
     */
  public function edit(ManPowerHenkaten $log)
{
    // Ambil semua station untuk dropdown
    $station = Station::all();

    // Ambil semua line area
    $lineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();

    // Ambil shift dan grup (jika dibutuhkan)
    $currentShift = Session::get('shift');
    $currentGroup = Session::get('grup');

    return view('manpower.create_henkaten', compact(
        'log',
        'station',
        'lineAreas',
        'currentShift',
        'currentGroup'
    ));
}

    /**
     * Update data log henkaten yang ada.
     */
    public function update(Request $request, ManPowerHenkaten $log)
    {
        // =========================================================
        // 1. VALIDASI DATA
        // =========================================================
        $validated = $request->validate([
            'shift'                 => 'required|string',
            'grup'                  => 'required|string',
            'man_power_id'          => 'required|integer|exists:man_power,id',
            'man_power_id_after'    => 'required|integer|exists:man_power,id|different:man_power_id',
            'station_id'            => 'required|integer|exists:stations,id',
            'keterangan'            => 'required|string',
            'line_area'             => 'required|string',
            'effective_date'        => 'required|date',
            'end_date'              => 'required|date|after_or_equal:effective_date',
            'time_start'            => 'required|date_format:H:i',
            'time_end'              => 'required|date_format:H:i|after_or_equal:time_start',
            'lampiran'              => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'serial_number_start'   => 'required|string|max:255',
            'serial_number_end'     => 'required|string|max:255',
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

            $manPowerAsli_Baru = ManPower::findOrFail($validated['man_power_id']);
            $manPowerAfter_Baru = ManPower::findOrFail($validated['man_power_id_after']);

            // =========================================================
            // 3. LOGIKA REVERT (JIKA MAN POWER BERUBAH)
            // =========================================================

            // 3A. Cek Man Power ASLI.
            if ($log->man_power_id != $validated['man_power_id']) {
                $mpAsliLama = ManPower::find($log->man_power_id);
                if ($mpAsliLama) {
                    $mpAsliLama->status = 'standby';
                    $mpAsliLama->save();
                }
            }

            // 3B. Cek Man Power PENGGANTI.
            if ($log->man_power_id_after != $validated['man_power_id_after']) {
                $mpAfterLama = ManPower::find($log->man_power_id_after);
                if ($mpAfterLama) {
                    $mpAfterLama->status = 'standby';
                    $mpAfterLama->station_id = null;
                    $mpAfterLama->save();
                }
            }

            // =========================================================
            // 4. SIAPKAN DATA & UPDATE LOG HENKATEN
            // =========================================================
            $dataToUpdate = $validated;
            $dataToUpdate['nama'] = $manPowerAsli_Baru->nama;
            $dataToUpdate['nama_after'] = $manPowerAfter_Baru->nama;
            $dataToUpdate['status'] = 'pending';

            if ($request->hasFile('lampiran')) {
                if ($log->lampiran && Storage::disk('public')->exists($log->lampiran)) {
                    Storage::disk('public')->delete($log->lampiran);
                }
                $newLampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
                $dataToUpdate['lampiran'] = $newLampiranPath;
            }

            $log->update($dataToUpdate);

            // =========================================================
            // 5. UPDATE STATUS MAN POWER (YANG BARU)
            // =========================================================
            $manPowerAsli_Baru->status = 'henkaten';
            $manPowerAsli_Baru->save();

            $manPowerAfter_Baru->status = 'aktif';
            $manPowerAfter_Baru->station_id = $validated['station_id'];
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

            if ($newLampiranPath && Storage::disk('public')->exists($newLampiranPath)) {
                Storage::disk('public')->delete($newLampiranPath);
            }

            if ($e instanceof ModelNotFoundException) {
                return back()->withErrors(['error' => 'Data Man Power (dari form) tidak ditemukan.'])->withInput();
            }

            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Hapus log henkaten dan kembalikan status man power.
     */
    public function destroy(ManPowerHenkaten $log)
    {
        // <-- 7. TAMBAHAN: Gunakan Transaksi untuk keamanan data
        DB::beginTransaction();

        try {
            // <-- 8. TAMBAHAN: Logika Revert Status Man Power
            // Kembalikan Man Power SEBELUM (yang diganti) ke 'standby'
            $mpBefore = ManPower::find($log->man_power_id);
            if ($mpBefore) {
                // Asumsi 'standby' adalah status default. Sesuaikan jika perlu.
                $mpBefore->update(['status' => 'standby']);
            }

            // Kembalikan Man Power SESUDAH (pengganti) ke 'standby' dan hapus station
            $mpAfter = ManPower::find($log->man_power_id_after);
            if ($mpAfter) {
                $mpAfter->update(['status' => 'standby', 'station_id' => null]);
            }

            // Hapus file lampiran dari storage (Kode Anda sudah benar)
            if ($log->lampiran) {
                Storage::disk('public')->delete($log->lampiran);
            }

            // Hapus log henkaten
            $log->delete();

            // Jika semua berhasil, commit
            DB::commit();

            return redirect()->route('activity.log.manpower')
                ->with('success', 'Log Henkaten Man Power berhasil dihapus dan status Man Power telah dikembalikan.');
        } catch (\Exception $e) {
            // Jika terjadi error, rollback
            DB::rollBack();

            return redirect()->route('activity.log.manpower')
                ->with('error', 'Gagal menghapus log: ' . $e->getMessage());
        }
    }

    /**
     * <-- 9. TAMBAHAN: Method baru untuk download PDF
     */
    public function downloadPDF(Request $request)
    {
        // Ambil input filter (LOGIKA SAMA DENGAN METHOD MANPOWER)
        $created_date = $request->input('created_date');
        $line_area = $request->input('line_area');

        $query = \App\Models\ManPowerHenkaten::with('station');

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        if ($line_area) {
            $query->where('line_area', $line_area);
        }

        // Ambil SEMUA data (gunakan get(), BUKAN paginate())
        $logs = $query->latest('created_at')->get();

        // Data untuk dikirim ke view PDF
        $data = [
            'logs' => $logs,
            'filterDate' => $created_date,
            'filterLine' => $line_area,
        ];

        $pdf = Pdf::loadView('pdf.activity-log-manpower', $data) 
                    ->setPaper('a4', 'landscape');
        return $pdf->download('Laporan_Henkaten_ManPower.pdf');
    }
}