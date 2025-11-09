<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MethodHenkaten;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActivityLogMethodController extends Controller
{
    // Asumsi Anda sudah punya fungsi index
    public function index(Request $request)
    {
        $created_date = $request->input('created_date');

        $query = MethodHenkaten::query() // <-- GANTI INI jika model Anda berbeda
                    ->with('station') // Eager load relasi station
                    ->orderBy('created_at', 'desc');

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->paginate(10); // Misalnya paginate 10

        return view('activity-log.method', compact('logs', 'created_date')); // Sesuaikan path view jika perlu
    }

    // ==========================================================
    // TAMBAHKAN FUNGSI-FUNGSI BARU DI BAWAH INI
    // ==========================================================

    /**
     * Menampilkan form untuk mengedit log.
     */
    public function edit(MethodHenkaten $log) // <-- GANTI INI jika model Anda berbeda
    {
        // Pastikan Anda membuat view 'activity-log.method-edit'
        return view('activity-log.method-edit', compact('log'));
    }

    /**
     * Memperbarui log di database.
     */
    public function update(Request $request, MethodHenkaten $log) // <-- GANTI INI jika model Anda berbeda
    {
        // Validasi data
        $request->validate([
            'keterangan_after' => 'nullable|string|max:255',
            'line_area' => 'nullable|string|max:100',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx|max:2048',
            // Tambahkan validasi lain jika perlu
        ]);

        // Ambil semua data kecuali lampiran
        $data = $request->except('lampiran');

        // Handle file upload (lampiran)
        if ($request->hasFile('lampiran')) {
            // Hapus lampiran lama jika ada
            if ($log->lampiran) {
                Storage::delete('public/' . $log->lampiran);
            }

            // Simpan lampiran baru
            $path = $request->file('lampiran')->store('lampiran/method', 'public');
            $data['lampiran'] = $path;
        }

        // Update data log
        $log->update($data);

        return redirect()->route('activity.log.method')
                         ->with('success', 'Data log berhasil diperbarui.');
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
}
