<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HenkatenController extends Controller
{
    // ==============================================================
    // BAGIAN 1: FORM PEMBUATAN HENKATEN BARU
    // ==============================================================

    /**
     * Menampilkan form untuk membuat data Henkaten baru.
     */
    public function create()
    {
        $stations = Station::all();
        $lineAreas = Station::whereNotNull('line_area')
                        ->orderBy('line_area', 'asc')
                        ->pluck('line_area')
                        ->unique();

                        $stations = [];
    if ($oldLineArea = old('line_area')) {
        // Jika ada error validasi, isi $stations berdasarkan line_area yang lama
        $stations = Station::where('line_area', $oldLineArea)->get();
    }
        return view('manpower.create_henkaten', compact('stations','lineAreas'));
    }

    /**
     * Menyimpan data baru dari form create_henkaten.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift'              => 'required|string',
            'nama'               => 'required|string',
            'nama_after'         => 'required|string',
            'man_power_id'       => 'required|integer|exists:man_power,id',
            'man_power_id_after' => 'required|integer|exists:man_power,id',
            'station_id'         => 'required|integer|exists:stations,id',
            'keterangan'         => 'nullable|string',
            'line_area'          => 'required|string',
            'effective_date'     => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:effective_date',
            'lampiran'           => 'nullable|image|mimes:jpeg,png|max:2048',
            'time_start'         => 'nullable|date_format:H:i',
            'time_end'           => 'nullable|date_format:H:i|after_or_equal:time_start',
        ]);

        try {
            DB::beginTransaction();

            // ===========================
            // Upload file jika ada lampiran
            // ===========================
            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_henkaten', 'public');
            }

            // Gabungkan data yang akan disimpan
            $dataToCreate = $validated;
            $dataToCreate['lampiran'] = $lampiranPath;

            // Simpan ke database
            $henkaten = ManPowerHenkaten::create($dataToCreate);

            // ===========================
            // Update status Man Power lama
            // ===========================
            $manPowerAsli = ManPower::find($request->man_power_id);
            if ($manPowerAsli) {
                $manPowerAsli->status = 'henkaten';
                $manPowerAsli->save();
            }

            // ===========================
            // Opsional: update status pengganti jadi aktif
            // ===========================
            $manPowerAfter = ManPower::find($request->man_power_id_after);
            if ($manPowerAfter) {
                $manPowerAfter->status = 'aktif';
                $manPowerAfter->save();
            }

            DB::commit();

            return redirect()->route('henkaten.create')
                ->with('success', 'Data Henkaten berhasil dibuat. Selanjutnya isi Serial Number di halaman Man Power Start.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()]);
        }
    }

    // ==============================================================
    // BAGIAN 2: HALAMAN START / MENGISI SERIAL NUMBER
    // ==============================================================

    /**
     * Menampilkan halaman untuk mengisi Serial Number.
     */
    public function showStartPage()
    {
        $pendingHenkatens = ManPowerHenkaten::with('station')
            ->whereNull('serial_number_start') // Logika ini sudah benar
            ->latest()
            ->get();

        return view('manpower.create_henkaten_start', [
            'henkatens' => $pendingHenkatens,
        ]);
    }

    /**
     * Mengupdate Serial Number dari halaman start_henkaten.
     */
    public function updateStartData(Request $request)
    {
        // DITAMBAHKAN: Validasi input 'updates'
        $validator = Validator::make($request->all(), [
            'updates'=> 'required|array',
            'updates.*.serial_number_start' => 'nullable|string|max:255', // Sesuaikan max length
            'updates.*.serial_number_end'=> 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updates = $request->input('updates', []);

        // DITAMBAHKAN: Gunakan DB::transaction untuk keamanan data
        try {
            DB::transaction(function () use ($updates) {
                foreach ($updates as $id => $data) {
                    // Pastikan ID ada di database sebelum update
                    $henkaten = ManPowerHenkaten::find($id);

                    // Hanya update jika data henkaten ditemukan dan ada input SN
                    if ($henkaten && (!empty($data['serial_number_start']) || !empty($data['serial_number_end']))) {
                        $henkaten->update([
                            'serial_number_start' => $data['serial_number_start'] ?? $henkaten->serial_number_start,
                            'serial_number_end'=> $data['serial_number_end'] ?? $henkaten->serial_number_end,
                        ]);
                    }
                }
            });

            return redirect()->route('henkaten.start.page')
                ->with('success', 'Data Serial Number berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui serial number: ' . $e->getMessage()]);
        }
    }
    public function searchManPower(Request $request)
{
    $query = $request->get('q', '');

    $results = ManPower::where('nama', 'like', "%{$query}%")
        ->select('id', 'nama')
        ->orderBy('nama', 'asc')
        ->limit(10)
        ->get();

    return response()->json($results);
}

public function getStationsByLine(Request $request)
{
    // 1. Validasi input
    $request->validate([
        'line_area' => 'required|string',
    ]);

    // 2. Ambil data dari database
    $stations = Station::where('line_area', $request->line_area)
                       ->orderBy('station_name', 'asc')
                       ->get(['id', 'station_name']); // Hanya ambil kolom yang perlu

    // 3. Kembalikan sebagai JSON
    return response()->json($stations);
}

}
