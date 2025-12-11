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
        
        // ✅ TAMBAHKAN VALIDASI UNTUK 3 LAMPIRAN
        'lampiran'              => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf,application/zip,application/x-rar-compressed|max:2048',
        'lampiran_2'            => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf,application/zip,application/x-rar-compressed|max:2048',
        'lampiran_3'            => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf,application/zip,application/x-rar-compressed|max:2048',
        
        'serial_number_start'   => 'required|string|max:255',
        'serial_number_end'     => 'required|string|max:255',
    ], [
        'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.',
        'serial_number_start.required' => 'Serial Number Start wajib diisi saat edit.',
        'serial_number_end.required'   => 'Serial Number End wajib diisi saat edit.',
    ]);

    try {
        DB::beginTransaction();

        $manPowerAsli_Baru = ManPower::findOrFail($validated['man_power_id']);
        $manPowerAfter_Baru = ManPower::findOrFail($validated['man_power_id_after']);

        // Logika revert (sama seperti sebelumnya)
        if ($log->man_power_id != $validated['man_power_id']) {
            $mpAsliLama = ManPower::find($log->man_power_id);
            if ($mpAsliLama) {
                $mpAsliLama->status = 'Actived';
                $mpAsliLama->save();
            }
        }

        if ($log->man_power_id_after != $validated['man_power_id_after']) {
            $mpAfterLama = ManPower::find($log->man_power_id_after);
            if ($mpAfterLama) {
                // Jangan kosongkan station_id; cukup aktifkan kembali
                $mpAfterLama->status = 'Actived';
                $mpAfterLama->save();
            }
        }

        $status = $this->determineHenkatenStatus(
            $validated['effective_date'],
            $validated['end_date'],
            $validated['time_start'],
            $validated['time_end']
        );

        $dataToUpdate = $validated;
        $dataToUpdate['nama'] = $manPowerAsli_Baru->nama;
        $dataToUpdate['nama_after'] = $manPowerAfter_Baru->nama;
        $dataToUpdate['status'] = $status;

        // ✅ HANDLE 3 LAMPIRAN
        // Lampiran 1
        if ($request->hasFile('lampiran')) {
            if ($log->lampiran && Storage::disk('public')->exists($log->lampiran)) {
                Storage::disk('public')->delete($log->lampiran);
            }
            $dataToUpdate['lampiran'] = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
        }

        // Lampiran 2
        if ($request->hasFile('lampiran_2')) {
            if ($log->lampiran_2 && Storage::disk('public')->exists($log->lampiran_2)) {
                Storage::disk('public')->delete($log->lampiran_2);
            }
            $dataToUpdate['lampiran_2'] = $request->file('lampiran_2')->store('henkaten_man_power_lampiran', 'public');
        }

        // Lampiran 3
        if ($request->hasFile('lampiran_3')) {
            if ($log->lampiran_3 && Storage::disk('public')->exists($log->lampiran_3)) {
                Storage::disk('public')->delete($log->lampiran_3);
            }
            $dataToUpdate['lampiran_3'] = $request->file('lampiran_3')->store('henkaten_man_power_lampiran', 'public');
        }

        $log->update($dataToUpdate);

        // Update status Man Power
        if ($status === 'Henkaten') {
            $manPowerAsli_Baru->status = 'Henkaten';
            $manPowerAsli_Baru->save();

            $manPowerAfter_Baru->status = 'Actived';
            $manPowerAfter_Baru->station_id = $validated['station_id'];
            $manPowerAfter_Baru->save();
        } else {
            $manPowerAsli_Baru->status = 'Actived';
            $manPowerAsli_Baru->save();

            // Jangan hapus station_id operator pengganti saat Henkaten selesai
            $manPowerAfter_Baru->status = 'Actived';
            $manPowerAfter_Baru->save();
        }

        DB::commit();

        return redirect()->back()
            ->with('success', 'Data Henkaten berhasil diperbarui.');
    } catch (\Exception $e) {
        DB::rollBack();

        // Hapus file yang sudah diupload jika terjadi error
        if (isset($dataToUpdate['lampiran']) && Storage::disk('public')->exists($dataToUpdate['lampiran'])) {
            Storage::disk('public')->delete($dataToUpdate['lampiran']);
        }
        if (isset($dataToUpdate['lampiran_2']) && Storage::disk('public')->exists($dataToUpdate['lampiran_2'])) {
            Storage::disk('public')->delete($dataToUpdate['lampiran_2']);
        }
        if (isset($dataToUpdate['lampiran_3']) && Storage::disk('public')->exists($dataToUpdate['lampiran_3'])) {
            Storage::disk('public')->delete($dataToUpdate['lampiran_3']);
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
        $mpBefore = ManPower::find($log->man_power_id);
        if ($mpBefore) {
            $mpBefore->update(['status' => 'Actived']);
        }

        $mpAfter = ManPower::find($log->man_power_id_after);
        if ($mpAfter) {
            // Jangan kosongkan station_id saat menghapus log henkaten
            $mpAfter->update(['status' => 'Actived']);
        }

        // ✅ HAPUS SEMUA LAMPIRAN
        if ($log->lampiran && Storage::disk('public')->exists($log->lampiran)) {
            Storage::disk('public')->delete($log->lampiran);
        }
        if ($log->lampiran_2 && Storage::disk('public')->exists($log->lampiran_2)) {
            Storage::disk('public')->delete($log->lampiran_2);
        }
        if ($log->lampiran_3 && Storage::disk('public')->exists($log->lampiran_3)) {
            Storage::disk('public')->delete($log->lampiran_3);
        }

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

public function downloadPDF(Request $request)
{
    // Ambil input filter
    $created_date = $request->input('created_date');
    $line_area = $request->input('line_area');

    // MENGGUNAKAN MODEL: MethodHenkaten (Sesuai dengan kode Anda)
    $query = ManPowerHenkaten::with('station'); // Pastikan 'station' adalah relasi yang benar

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
        'filterDate' => $created_date,
        'filterLine' => $line_area,
    ];

    $pdf = Pdf::loadView('pdf.activity-log-manpower', $data) 
              ->setPaper('a4', 'landscape');
    
    return $pdf->download('Laporan_Henkaten_ManPower.pdf');
}


private function determineHenkatenStatus($request)
{
    return $request->status ?? 'Approved';
}

}