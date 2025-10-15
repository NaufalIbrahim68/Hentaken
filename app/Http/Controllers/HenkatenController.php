<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use Illuminate\Http\Request;
use App\Models\ManPowerHenkaten;
use App\Models\Station;

class HenkatenController extends Controller
{
    // ===================================================================
    // BAGIAN 1: FUNGSI UNTUK FORM PEMBUATAN HENKATEN (HALAMAN PERTAMA)
    // ===================================================================

    /**
     * Menampilkan form untuk membuat data Henkaten baru.
     * (Fungsi 'form' Anda, diganti nama menjadi 'create' agar sesuai standar Laravel)
     */
    public function create()
    {
        $stations = Station::all();
        return view('manpower.create_henkaten', compact('stations'));
    }

    /**
     * Menyimpan data baru dari form create_henkaten.
     * (Fungsi 'store' Anda, sudah bagus)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift'              => 'required|string',
            'nama'               => 'required|string',
            'nama_after'         => 'required|string',
            'man_power_id'       => 'required|integer|exists:man_powers,id',
            'man_power_id_after' => 'required|integer|exists:man_powers,id',
            'station_id'         => 'required|integer|exists:stations,id',
            'keterangan'         => 'nullable|string',
            'line_area'          => 'required|string',
            'effective_date'     => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:effective_date',
            'lampiran'           => 'nullable|image|mimes:jpeg,png|max:2048',
            'time_start'         => 'nullable|date_format:H:i',
            'time_end'           => 'nullable|date_format:H:i|after_or_equal:time_start',
        ]);

        // Hapus 'serial_number' dari validasi karena akan diisi di halaman terpisah
        // 'serial_number_start' => 'nullable|string|max:255',
        // 'serial_number_end'   => 'nullable|string|max:255',

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $namaFile = time() . '_' . $request->file('lampiran')->getClientOriginalName();
            $tujuan = public_path('storage/lampiran_henkaten');
            if (!file_exists($tujuan)) {
                mkdir($tujuan, 0777, true);
            }
            $request->file('lampiran')->move($tujuan, $namaFile);
            $lampiranPath = 'lampiran_henkaten/' . $namaFile;
        }

        // Gabungkan path lampiran ke data yang akan disimpan
        $dataToCreate = $validated;
        $dataToCreate['lampiran'] = $lampiranPath;

        // Simpan data ke database
        ManPowerHenkaten::create($dataToCreate);

        // Update status man power asli
        $manPowerAsli = ManPower::find($request->man_power_id);
        if ($manPowerAsli) {
            $manPowerAsli->status = 'henkaten';
            $manPowerAsli->save();
        }

        // Redirect kembali ke form dengan pesan sukses
        return redirect()->route('henkaten.create')
            ->with('success', 'Data Henkaten berhasil dibuat. Selanjutnya isi Serial Number di halaman Man Power Start.');
    }

    // ===================================================================
    // BAGIAN 2: FUNGSI UNTUK HALAMAN START/MANPOWER (HALAMAN KEDUA)
    // ===================================================================

    /**
     * BARU: Menampilkan halaman untuk mengisi Serial Number.
     */
    public function showStartPage()
    {
        // Ambil data henkaten yang BELUM diisi serial number-nya.
        // Gunakan `with('station')` untuk mengambil relasi agar lebih efisien (Eager Loading).
        $pendingHenkatens = ManPowerHenkaten::with('station')
                                            ->whereNull('serial_number_start')
                                            ->latest()
                                            ->get();

        // Kirim data ke view baru
        return view('manpower.create_henkaten_start', ['henkatens' => $pendingHenkatens]);
    }

    /**
     * BARU: Mengupdate Serial Number dari halaman start_henkaten.
     * (Fungsi 'updateAll' Anda, diganti nama agar lebih deskriptif)
     */
    public function updateStartData(Request $request)
    {
        $updates = $request->input('updates', []);

        foreach ($updates as $id => $data) {
            // Cek jika input tidak kosong untuk menghindari update data kosong
            if (!empty($data['serial_number_start']) && !empty($data['serial_number_end'])) {
                $henkaten = ManPowerHenkaten::find($id);

                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end'   => $data['serial_number_end'],
                    ]);
                }
            }
        }

        return redirect()->route('henkaten.start.page')
            ->with('success', 'Data Serial Number berhasil diperbarui!');
    }
}
