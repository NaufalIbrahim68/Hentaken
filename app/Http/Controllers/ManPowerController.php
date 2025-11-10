<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Station;
use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\Troubleshooting;
use Illuminate\View\View;

class ManPowerController extends Controller
{
    // ==============================================================
    // INDEX: Tampilkan daftar Man Power
    // ==============================================================
    public function index()
    {
        $man_powers = ManPower::with('station')
            ->orderBy('nama', 'asc')
            ->paginate(5);

        return view('manpower.index', compact('man_powers'));
    }

    // ==============================================================
    // CREATE MASTER MANPOWER
    // ==============================================================
   public function create()
{
    // Ambil daftar 'line_area' yang unik dan tidak null
    // Urutkan berdasarkan abjad (asc) agar rapi di dropdown
    $lineAreas = Station::select('line_area')
                        ->whereNotNull('line_area') // Hindari mengambil nilai NULL
                        ->distinct()
                        ->orderBy('line_area', 'asc') 
                        ->pluck('line_area');

    // $stations = Station::all(); // <-- TIDAK PERLU LAGI
    // Data 'stations' akan di-load secara dinamis via AJAX
    // setelah user memilih 'line_area'.

    // Kirim HANYA 'lineAreas' ke view.
    return view('manpower.create', compact('lineAreas'));
}

    // ==============================================================
    // GET STATIONS BY LINE AREA (AJAX) — dipakai juga untuk modal
    // ==============================================================
    public function getStationsByLine(Request $request)
    {
        $lineArea = $request->input('line_area');

        if (!$lineArea) {
            return response()->json([]);
        }

        $stations = Station::where('line_area', $lineArea)
            ->select('id', 'station_name', 'station_code')
            ->orderBy('station_name', 'asc')
            ->get();

        return response()->json($stations);
    }

    // ==============================================================
    // STORE MASTER MANPOWER
    // ==============================================================
   public function storeMaster(Request $request)
{
    // 1. VALIDASI
    $request->validate([
        'nama' => 'required|string|max:255',
        'station_id' => 'nullable|exists:stations,id',
        'grup' => 'required|string',
        'shift' => 'nullable|string',
        'tanggal_mulai' => 'required|date',
        'waktu_mulai' => 'required|date_format:H:i',
    ]);

    // 2. AMBIL DATA REQUEST
    $data = $request->only(['nama', 'station_id', 'shift', 'grup', 'tanggal_mulai', 'waktu_mulai']);

    // 3. CARI LINE AREA BERDASARKAN STATION_ID
    $lineAreaOfStation = null; 
    if (!empty($data['station_id'])) {
        $station = Station::find($data['station_id']);
        
        if ($station) {
            $lineAreaOfStation = $station->line_area;
        }
    }

    // 4. PROSES SIMPAN DATA
    if (str_contains($data['grup'], 'Troubleshooting')) {
        
        // Simpan ke troubleshooting
        // (Status di tabel troubleshooting tetap 'normal', 
        //  asumsi 'pending' hanya untuk data man power baru)
        $grupTs = substr($data['grup'], 0, 1);
        $troubleshooting = Troubleshooting::create([
            'nama' => $data['nama'],
            'grup' => $grupTs,
            'status' => 'normal', 
        ]);

        // Simpan ke master manpower
        ManPower::create([
            'nama' => $data['nama'],
            'grup' => $data['grup'],
            'station_id' => $data['station_id'] ?? null,
            'shift' => $data['shift'] ?? null,
            'line_area' => $lineAreaOfStation,
            'status' => 'pending', // <-- DIUBAH
            'troubleshooting_id' => $troubleshooting->id,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'waktu_mulai' => $data['waktu_mulai'],
        ]);

    } else {
        // Masuk ke manpower biasa
        ManPower::create([
            'nama' => $data['nama'],
            'grup' => $data['grup'],
            'station_id' => $data['station_id'],
            'line_area' => $lineAreaOfStation,
            'status' => 'pending', // <-- DIUBAH
            'tanggal_mulai' => $data['tanggal_mulai'],
            'waktu_mulai' => $data['waktu_mulai'],
        ]);
    }

    return redirect()->route('manpower.index')
                     ->with('success', 'Data Man Power berhasil ditambahkan.');
}




    // ==============================================================
    // EDIT MASTER MANPOWER
    // ==============================================================
   public function edit($id)
{
    
    // MENJADI SEPERTI INI:
    $man_power = ManPower::with('stations')->findOrFail($id);
    
    // Sisa kode Anda tetap sama
    $lineAreas = Station::select('line_area')->distinct()->pluck('line_area');
    $stations = Station::all();

    return view('manpower.edit_master', compact('man_power', 'lineAreas', 'stations'));
}
    // ==============================================================
    // UPDATE MASTER MANPOWER
    // ==============================================================
    public function updateMaster(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'station_id' => 'required|exists:stations,id',
            
            'grup' => 'required|in:A,B',
        ]);

        $man_power = ManPower::findOrFail($id);
        $man_power->update($validatedData);

        return redirect()->route('manpower.index')
            ->with('success', 'Data Man Power berhasil diperbarui.');
    }

    // ==============================================================
    // DESTROY MASTER MANPOWER
    // ==============================================================
    public function destroyMaster($id)
    {
        $man_power = ManPower::findOrFail($id);
        $man_power->delete();

        return redirect()->route('manpower.index')
            ->with('success', 'Data Man Power berhasil dihapus.');
    }

    // ==============================================================
    // CREATE HENKATEN FORM
    // ==============================================================
    public function createHenkatenForm()
    {
        $man_power = ManPower::with('station')->first();
        $stations = Station::all();

        return view('manpower.create_henkaten', compact('man_power', 'stations'));
    }

    public function createHenkaten($id)
    {
        $man_power = ManPower::findOrFail($id);
        $stations = Station::all();
        $lineAreas = Station::whereNotNull('line_area')
            ->orderBy('line_area', 'asc')
            ->pluck('line_area')
            ->unique();

        return view('manpower.create_henkaten', compact('man_power', 'stations', 'lineAreas'));
    }

    // ==============================================================
    // STORE HENKATEN DATA
    // ==============================================================
    public function storeHenkaten(Request $request)
    {
        $validated = $request->validate([
            'shift' => 'required|in:1,2',
            'line_area' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'man_power_id' => 'required|exists:man_power,id',
            'man_power_id_after' => 'required|exists:man_power,id',
            'keterangan' => 'nullable|string',
            'lampiran' => 'nullable|image|mimes:jpeg,png|max:2048',
            'nama' => 'required|string|max:255',
            'nama_after' => 'required|string|max:255',
        ]);

        $lampiranPath = $request->hasFile('lampiran')
            ? $request->file('lampiran')->store('lampiran_henkaten', 'public')
            : null;

        ManPowerHenkaten::create([
            ...$validated,
            'lampiran' => $lampiranPath,
        ]);

        return redirect()->route('manpower.index')
            ->with('success', 'Data Henkaten berhasil disimpan.');
    }

    // ==============================================================
    // SCHEDULER VIEW
    // ==============================================================
    public function createManpowerScheduler(Request $request): View
    {
        return view('manpower.schedulers');
    }

    // ==============================================================
    // STATION MANAGEMENT (MODAL)
    // ==============================================================
    public function storeStation(Request $request)
    {
        $request->validate([
            'line_area' => 'required|string|max:255',
            'station_name' => 'required|string|max:255',
        ]);

        $station = Station::create([
            'line_area' => $request->line_area,
            'station_name' => $request->station_name,
        ]);

        return response()->json($station);
    }

    public function destroyStation($id)
    {
        $station = Station::findOrFail($id);
        $station->delete();

        return response()->noContent();
    }

    public function updateStation(Request $request, $id)
{
    $request->validate(['station_name' => 'required|string|max:255']);

    $station = Station::findOrFail($id);
    $station->update(['station_name' => $request->station_name]);

    return response()->json($station);
}

public function search(Request $request)
{
    $request->validate([
        'q' => 'nullable|string',
        'grup' => 'required|string',
    ]);

    $q = $request->input('q', '');
    $grupInput = $request->input('grup');
    $grupTs = str_contains($grupInput, 'Troubleshooting') ? substr($grupInput, 0, 1) : $grupInput;

    // =================================================================
    // AWAL BLOK LOGIKA BARU (LEBIH AKURAT)
    // =================================================================

    $now = Carbon::now();
    $today = $now->copy()->startOfDay(); // 2025-11-04 00:00:00
    $currentTime = $now->toTimeString(); // 15:00:00 (misalnya)

    // 1. Dapatkan SEMUA ID man power yang sedang aktif henkaten
    $allBusyIds = ManPowerHenkaten::where(function ($query) use ($today, $currentTime) {
                        
                        // Kriteria 1: Henkaten permanen (belum ada tgl berakhir)
                        $query->whereNull('end_date');

                        // Kriteria 2: Henkaten berakhir di masa depan (besok atau lusa, dst.)
                        $query->orWhere('end_date', '>', $today); 

                        // Kriteria 3: Henkaten berakhir HARI INI, tapi JAM-nya belum lewat
                        $query->orWhere(function ($q) use ($today, $currentTime) {
                            $q->where('end_date', $today) // end_date adalah hari ini
                              ->where('time_end', '>', $currentTime); // dan jam berakhir > jam sekarang
                        });

                    })
                    ->pluck('man_power_id_after')
                    ->filter()
                    ->unique()
                    ->all();

    // 2. Pisahkan ID regular dan ID Troubleshooting (TS)
    $busyRegularIds = [];
    $busyTsIds = [];

    foreach ($allBusyIds as $id) {
        if (is_string($id) && str_starts_with($id, 't-')) {
            $busyTsIds[] = substr($id, 2);
        } else {
            $busyRegularIds[] = $id;
        }
    }

    // =================================================================
    // AKHIR BLOK LOGIKA BARU
    // =================================================================

    // Cari di manpower normal
    $manPower = ManPower::query()
        ->where('nama', 'like', "%$q%")
        ->where('grup', $grupInput)
        ->whereNotIn('id', $busyRegularIds) // <-- PASTIKAN BARIS INI ADA
        ->get(['id', 'nama', 'grup']);

    // Cari di troubleshooting
    $troubleshooting = Troubleshooting::query()
        ->where('nama', 'like', "%$q%")
        ->where('grup', $grupTs)
        ->whereNotIn('id', $busyTsIds) // <-- PASTIKAN BARIS INI ADA
        ->get(['id', 'nama', 'grup']);

    // Gabungkan hasil
    $resultManpower = $manPower->map(fn($item) => [
        'id' => $item->id,
        'nama' => $item->nama,
        'grup' => $item->grup,
    ]);

    $resultTroubleshooting = $troubleshooting->map(fn($item) => [
        'id' => 't-' . $item->id,
        'nama' => $item->nama . ' (TS)',
        'grup' => $item->grup,
    ]);

    $result = $resultManpower->merge($resultTroubleshooting)
                             ->unique('nama')
                             ->values();

    return response()->json($result);
}

public function getManPower(Request $request)
{
    $grup = $request->input('grup');
    $line_area = $request->input('line_area');
    $station_id = $request->input('station_id');

    $result = [];

    // --- 1. Ambil Man Power biasa yang station_id sama dan line_area sama ---
    if ($station_id && $line_area) {
        $manPowerQuery = ManPower::where('grup', $grup)
            ->where('station_id', $station_id)
            ->where('line_area', $line_area);

        // Ambil semua orang di station itu
        $manPowerInStation = $manPowerQuery->get();

        // Hanya tampilkan jika ada lebih dari 1 orang
        if ($manPowerInStation->count() > 1) {
            foreach ($manPowerInStation as $mp) {
                $result[] = [
                    'id' => $mp->id,
                    'nama' => $mp->nama,
                ];
            }
        }
    }

    // --- 2. Ambil Troubleshooting berdasarkan grup saja ---
    $tsGrup = substr($grup, 0, 1); // ambil 'A' atau 'B' saja
    $troubleshooting = Troubleshooting::where('grup', $tsGrup)->get();

    foreach ($troubleshooting as $ts) {
        $result[] = [
            'id' => 't-' . $ts->id, // prefix agar tidak bentrok
            'nama' => $ts->nama . ' (TS)',
        ];
    }

    return response()->json($result);
}

// Tambahkan di ManPowerController
public function confirmation()
{
    $manpowers = ManPower::where('status', 'pending')->get(); // contoh filter status pending
    $methods   = \App\Models\Method::where('status', 'pending')->get();
    $machines  = \App\Models\Machine::where('status', 'pending')->get();
    $materials = \App\Models\Material::where('status', 'pending')->get();

    return view('secthead.master-confirm', compact('manpowers', 'methods', 'machines', 'materials'));
}




}
