<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Diperlukan untuk mengambil waktu saat ini

class ManPowerController extends Controller
{
    /**
     * PERUBAHAN: Menampilkan daftar dari tabel 'man_power'
     */
  public function index()
{
    // Mengambil data dari tabel master 'man_power'
    $man_powers = DB::table('man_power')->get();

   

    // Mengirim data ke view
    return view('manpower.index', ['man_powers' => $man_powers]);
}
    /**
     * BARU: Membuat entri henkaten dari man_power yang dipilih
     * lalu redirect ke halaman edit henkaten tersebut.
     */
    public function createHenkaten($id)
    {
        // 1. Cari data master man power yang akan diubah
        $man_power = DB::table('man_power')->find($id);

        if (!$man_power) {
            return redirect()->route('manpower.index')->with('error', 'Man Power tidak ditemukan.');
        }

        // 2. Buat entri baru di tabel man_power_henkaten
        $newHenkatenId = DB::table('man_power_henkaten')->insertGetId([
            'man_power_id' => $man_power->id, // atau field ID yang sesuai
            'station_id'   => $man_power->station_id,
            'shift'        => $man_power->shift,
            'nama'         => $man_power->nama,
            'line_area'    => $man_power->line_area,
            'keterangan'   => 'Menunggu update...', // Keterangan default
            'created_at'   => Carbon::now(),
            // Kolom _after dikosongkan karena akan diisi di form edit
            'man_power_id_after' => null,
            'station_id_after'   => null,
            'effective_date'     => null,
            'end_date'           => null,
        ]);

        // 3. Redirect ke halaman edit untuk henkaten yang baru dibuat
        return redirect()->route('manpower.edit', $newHenkatenId)->with('success', 'Henkaten berhasil dibuat. Silakan isi data pengganti.');
    }


    /**
     * Menampilkan form untuk mengedit data HENKATEN.
     * (Method ini tetap sama, karena halaman edit tetap untuk data henkaten)
     */
    public function edit($id)
    {
        $henkaten = DB::table('man_power_henkaten')->find($id);
        
        if (!$henkaten) {
            return redirect()->route('manpower.index')->with('error', 'Data Henkaten tidak ditemukan.');
        }

        return view('manpower.edit', ['henkaten' => $henkaten]);
    }

    /**
     * Mengupdate data HENKATEN.
     * (Method ini tetap sama)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'man_power_id_after' => 'nullable|string|max:255',
            'station_id_after' => 'nullable|string|max:255',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'keterangan' => 'nullable|string',
        ]);

        DB::table('man_power_henkaten')->where('id', $id)->update([
            'man_power_id_after' => $request->man_power_id_after,
            'station_id_after'   => $request->station_id_after,
            'effective_date'     => $request->effective_date,
            'end_date'           => $request->end_date,
            'keterangan'         => $request->keterangan,
            'update_at'          => Carbon::now(),
        ]);

        // Redirect ke daftar HENKATEN setelah update, bukan daftar man power
        // Anda mungkin perlu membuat route baru untuk melihat daftar henkaten
        // Untuk sementara, kita redirect kembali ke halaman index man power
        return redirect()->route('manpower.index')->with('success', 'Data Henkaten berhasil diperbarui.');
    }

    /**
     * Menghapus data HENKATEN.
     * (Method ini tetap sama)
     */
    public function destroy($id)
    {
        DB::table('man_power_henkaten')->where('id', $id)->delete();
        return redirect()->route('manpower.index')->with('success', 'Data Henkaten berhasil dihapus.');
    }

      public function editMaster($id)
    {
        $man_power = DB::table('man_power')->find($id);

        if (!$man_power) {
            return redirect()->route('manpower.index')->with('error', 'Data Man Power tidak ditemukan.');
        }
        
        // Anda perlu membuat view baru: 'manpower.edit_master'
        return view('manpower.edit_master', ['man_power' => $man_power]);
    }

    /**
     * BARU: Menghapus data MASTER man_power dari database.
     */
    public function destroyMaster($id)
    {
        // Cari dulu datanya
        $man_power = DB::table('man_power')->find($id);

        if (!$man_power) {
            return redirect()->route('manpower.index')->with('error', 'Data Man Power gagal dihapus karena tidak ditemukan.');
        }

        // Hapus data
        DB::table('man_power')->where('id', $id)->delete();

        return redirect()->route('manpower.index')->with('success', 'Data Man Power berhasil dihapus.');
    }

}

