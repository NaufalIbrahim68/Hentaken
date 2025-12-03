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
        $created_date = $request->input('created_date');
        $line_area = $request->input('line_area');

        $query = \App\Models\ManPowerHenkaten::with('station');

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        if ($line_area) {
            $query->where('line_area', $line_area);
        }

        $logs = $query->latest('created_at')
            ->paginate(10)
            ->appends($request->query());

        $lineAreas = Station::distinct()->whereNotNull('line_area')->pluck('line_area');

        return view('manpower.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date,
            'lineAreas' => $lineAreas,
            'line_area' => $line_area,
        ]);
    }

    /**
     * Menampilkan form edit untuk log henkaten.
     */
    public function edit(ManPowerHenkaten $log)
    {
        $stations = Station::select('id', 'station_name')->get();
        $lineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();
        $currentShift = Session::get('shift');
        $currentGroup = Session::get('grup');

        return view('manpower.create_henkaten', compact(
            'log',
            'stations',
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
            'lampiran'              => 'nullable|file|mimetypes:image/jpeg,image/png,application/zip,application/x-rar-compressed|max:2048',
            'serial_number_start'   => 'required|string|max:255',
            'serial_number_end'     => 'required|string|max:255',
        ], [
            'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.',
            'serial_number_start.required' => 'Serial Number Start wajib diisi saat edit.',
            'serial_number_end.required'   => 'Serial Number End wajib diisi saat edit.',
        ]);

        $newLampiranPath = null;

        try {
            DB::beginTransaction();

            $manPowerAsli_Baru = ManPower::findOrFail($validated['man_power_id']);
            $manPowerAfter_Baru = ManPower::findOrFail($validated['man_power_id_after']);

            // =========================================================
            // LOGIKA REVERT (JIKA MAN POWER BERUBAH)
            // =========================================================

            // Cek Man Power ASLI - Kembalikan ke Actived jika berubah
            if ($log->man_power_id != $validated['man_power_id']) {
                $mpAsliLama = ManPower::find($log->man_power_id);
                if ($mpAsliLama) {
                    // FIXED: Ubah dari 'standby' menjadi 'Actived'
                    $mpAsliLama->status = 'Actived';
                    $mpAsliLama->save();
                }
            }

            // Cek Man Power PENGGANTI - Kembalikan ke Actived dan hapus station jika berubah
            if ($log->man_power_id_after != $validated['man_power_id_after']) {
                $mpAfterLama = ManPower::find($log->man_power_id_after);
                if ($mpAfterLama) {
                    // FIXED: Ubah dari 'standby' menjadi 'Actived'
                    $mpAfterLama->status = 'Actived';
                    $mpAfterLama->station_id = null;
                    $mpAfterLama->save();
                }
            }

            // =========================================================
            // SIAPKAN DATA & UPDATE LOG HENKATEN
            // =========================================================
            
            // Tentukan status berdasarkan tanggal dan waktu
            $status = $this->determineHenkatenStatus(
                $validated['effective_date'],
                $validated['end_date'],
                $validated['time_start'],
                $validated['time_end']
            );

            $dataToUpdate = $validated;
            $dataToUpdate['nama'] = $manPowerAsli_Baru->nama;
            $dataToUpdate['nama_after'] = $manPowerAfter_Baru->nama;
            // FIXED: Gunakan status dinamis, bukan hardcoded 'pending'
            $dataToUpdate['status'] = $status;

            if ($request->hasFile('lampiran')) {
                if ($log->lampiran && Storage::disk('public')->exists($log->lampiran)) {
                    Storage::disk('public')->delete($log->lampiran);
                }
                $newLampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
                $dataToUpdate['lampiran'] = $newLampiranPath;
            }

            $log->update($dataToUpdate);

            // =========================================================
            // UPDATE STATUS MAN POWER (YANG BARU)
            // =========================================================
            
            // FIXED: Status sesuai dengan kondisi Henkaten
            if ($status === 'Henkaten') {
                // Jika sedang dalam periode Henkaten
                $manPowerAsli_Baru->status = 'Henkaten';
                $manPowerAsli_Baru->save();

                $manPowerAfter_Baru->status = 'Actived';
                $manPowerAfter_Baru->station_id = $validated['station_id'];
                $manPowerAfter_Baru->save();
            } else {
                // Jika belum atau sudah lewat periode Henkaten (Pending)
                // Man Power asli tetap Actived, pengganti juga Actived tapi belum assign station
                $manPowerAsli_Baru->status = 'Actived';
                $manPowerAsli_Baru->save();

                $manPowerAfter_Baru->status = 'Actived';
                $manPowerAfter_Baru->station_id = null; // Belum assign
                $manPowerAfter_Baru->save();
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Data Henkaten berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($newLampiranPath && Storage::disk('public')->exists($newLampiranPath)) {
                Storage::disk('public')->delete($newLampiranPath);
            }

            if ($e instanceof ModelNotFoundException) {
                return back()->withErrors(['error' => 'Data Man Power tidak ditemukan.'])->withInput();
            }

            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Hapus log henkaten dan kembalikan status man power.
     */
    public function destroy(ManPowerHenkaten $log)
    {
        DB::beginTransaction();

        try {
            // Kembalikan Man Power SEBELUM (yang diganti) ke 'Actived'
            $mpBefore = ManPower::find($log->man_power_id);
            if ($mpBefore) {
                // FIXED: Ubah dari 'standby' menjadi 'Actived'
                $mpBefore->update(['status' => 'Actived']);
            }

            // Kembalikan Man Power SESUDAH (pengganti) ke 'Actived' dan hapus station
            $mpAfter = ManPower::find($log->man_power_id_after);
            if ($mpAfter) {
                // FIXED: Ubah dari 'standby' menjadi 'Actived'
                $mpAfter->update(['status' => 'Actived', 'station_id' => null]);
            }

            // Hapus file lampiran dari storage
            if ($log->lampiran) {
                Storage::disk('public')->delete($log->lampiran);
            }

            // Hapus log henkaten
            $log->delete();

            DB::commit();

            return redirect()->route('activity.log.manpower')
                ->with('success', 'Log Henkaten Man Power berhasil dihapus dan status Man Power telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('activity.log.manpower')
                ->with('error', 'Gagal menghapus log: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF activity log
     */
    public function downloadPDF(Request $request)
    {
        $created_date = $request->input('created_date');
        $line_area = $request->input('line_area');

        $query = \App\Models\ManPowerHenkaten::with('station');

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        if ($line_area) {
            $query->where('line_area', $line_area);
        }

        $logs = $query->latest('created_at')->get();

        $data = [
            'logs' => $logs,
            'filterDate' => $created_date,
            'filterLine' => $line_area,
        ];

        $pdf = Pdf::loadView('pdf.activity-log-manpower', $data) 
                    ->setPaper('a4', 'landscape');
        return $pdf->download('Laporan_Henkaten_ManPower.pdf');
    }

    /**
     * Tentukan status Henkaten berdasarkan tanggal dan waktu
     */
    private function determineHenkatenStatus($effectiveDate, $endDate, $timeStart, $timeEnd)
    {
        $now = now();
        $currentDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        $effectiveDateOnly = date('Y-m-d', strtotime($effectiveDate));
        $endDateOnly = date('Y-m-d', strtotime($endDate));

        // Cek apakah saat ini berada dalam periode tanggal dan waktu Henkaten
        if ($currentDate >= $effectiveDateOnly && $currentDate <= $endDateOnly) {
            // Jika tanggal cocok, cek waktu
            if ($currentDate == $effectiveDateOnly && $currentDate == $endDateOnly) {
                // Jika tanggal efektif dan berakhir sama, cek range waktu
                if ($currentTime >= $timeStart && $currentTime <= $timeEnd) {
                    return 'Henkaten';
                }
            } elseif ($currentDate == $effectiveDateOnly) {
                // Hari pertama Henkaten
                if ($currentTime >= $timeStart) {
                    return 'Henkaten';
                }
            } elseif ($currentDate == $endDateOnly) {
                // Hari terakhir Henkaten
                if ($currentTime <= $timeEnd) {
                    return 'Henkaten';
                }
            } else {
                // Hari di antara effective_date dan end_date
                return 'Henkaten';
            }
        }

        // Jika belum masuk periode Henkaten atau sudah lewat
        return 'Pending';
    }
}