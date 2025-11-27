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
use Illuminate\Support\Facades\Log;      
use Illuminate\Support\Facades\Schema;


class ManPowerController extends Controller
{
    
   public function index(Request $request) 
    {
        // 1. Ambil nilai filter dari request
        $selectedLineArea = $request->get('line_area');

        // 2. Ambil semua line_area yang unik untuk opsi dropdown
        //    Ini akan mengambil semua 'line_area' yang ada di tabel Anda
        $lineAreas = ManPower::select('line_area')
                            ->whereNotNull('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area');

        // 3. Buat query dasar, sama seperti yang Anda miliki
        $query = ManPower::with('station');

        // 4. Terapkan filter JIKA $selectedLineArea ada isinya
        if ($selectedLineArea) {
            $query->where('line_area', $selectedLineArea);
        }

       
        $man_powers = $query->orderBy('nama', 'asc')->paginate(5);

        // 6. Kirim semua data yang diperlukan ke view
        return view('manpower.index', [
            'man_powers' => $man_powers,
            'lineAreas' => $lineAreas,           
            'selectedLineArea' => $selectedLineArea, 
        ]);
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
        // Sesuaikan validasi dengan input form
        $validatedData = $request->validate([
            'nama'      => 'required|string|max:255',
            'line_area' => 'required|string|max:255', // <-- TAMBAHKAN INI
            'group'     => 'required|in:A,B',         // <-- UBAH 'grup' JADI 'group'
            // 'station_id' dihapus karena di-handle oleh AlpineJS
        ]);

        $man_power = ManPower::findOrFail($id);
        
        // Pastikan $fillable di Model ManPower Anda
        // juga 'line_area' dan 'group' (bukan 'grup')
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
    $data = $request->validate([
        'man_power_id' => 'required|exists:man_power,id',
        'station_id'   => 'required|exists:stations,id',
    ]);

    $manPower = ManPower::find($data['man_power_id']);

    // Cek dulu agar tidak duplikat
    if ($manPower->stations()->where('station_id', $data['station_id'])->exists()) {
        return response()->json(['message' => 'Station sudah ada.'], 422);
    }

    // attach() akan otomatis INSERT ke tabel man_power_stations
    $manPower->stations()->attach($data['station_id']);

    return response()->json(['message' => 'Station berhasil ditambahkan.']);
}

   public function destroyStation(Request $request, $id)
{
    $request->validate([
        'man_power_id' => 'required|exists:man_power,id',
    ]);

    $manPower = ManPower::find($request->man_power_id);
    
    // detach() akan otomatis DELETE dari tabel man_power_stations
    // di mana man_power_id = $manPower->id DAN station_id = $id
    $manPower->stations()->detach($id);

    return response()->json(['message' => 'Station berhasil dihapus.']);
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
    try {
        $q = $request->input('query', '');
        $grupInput = $request->input('grup', '');
        $lineArea = $request->input('line_area', '');
        $stationId = $request->input('station_id', null);
        
        if (strlen($q) < 2 || empty($grupInput)) {
            return response()->json([]);
        }

        $nowFull = Carbon::now();
        
        // ✅ Ambil role user yang sedang login
        $userRole = auth()->user()->role ?? null;

        // Ambil busy IDs
        $activeManpowerIds = ManPowerHenkaten::whereIn('status', ['Approved', 'PENDING'])
            ->where(function ($query) use ($nowFull) {
                $query->whereNull('end_date')
                    ->orWhere(function($q) use ($nowFull) {
                        $q->whereRaw("CONCAT(end_date, ' ', time_end) >= ?", [$nowFull]);
                    });
            })
            ->pluck('man_power_id_after')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $busyRegularIds = [];
        $busyTsIds = [];

        foreach ($activeManpowerIds as $id) {
            if (is_string($id) && str_starts_with($id, 't-')) {
                $busyTsIds[] = (int) substr($id, 2);
            } else {
                $busyRegularIds[] = (int) $id;
            }
        }

        // ===============================
        // 1. Man Power REGULER (Grup Specific)
        // ===============================
        $manPowerQuery = ManPower::query()
            ->where('grup', $grupInput)
            ->where('nama', 'like', "%{$q}%")
            ->whereNotIn('id', $busyRegularIds);

        // ✅ Filter berdasarkan role untuk regular manpower
        if ($userRole === 'Leader QC') {
            $manPowerQuery->where('line_area', 'Incoming');
        } elseif ($userRole === 'Leader PPIC') {
            $manPowerQuery->where('line_area', 'Delivery');
        } elseif (!empty($lineArea)) {
            $manPowerQuery->where('line_area', $lineArea);
        }

        // ✅ Filter station - hanya ambil yang is_main_operator = 0 (backup/support)
        if (!empty($stationId)) {
            $manPowerQuery->whereHas('manyStations', function ($q) use ($stationId) {
                $q->where('station_id', $stationId)
                  ->where('is_main_operator', 0); // ✅ Hanya operator backup
            });
        }

        $manPower = $manPowerQuery->get(['id', 'nama', 'grup', 'line_area']);
        
        Log::info('ManPower Query Debug', [
            'query' => $q,
            'grup' => $grupInput,
            'line_area' => $lineArea,
            'station_id' => $stationId,
            'user_role' => $userRole,
            'busy_ids' => $busyRegularIds,
            'result_count' => $manPower->count(),
            'sql' => $manPowerQuery->toSql()
        ]);

        // ===============================
        // 2. Troubleshooting (Grup Specific)
        // ===============================
        $troubleshootingQuery = ManPower::query()
            ->where('grup', 'like', "{$grupInput}(Troubleshooting)%")
            ->where('nama', 'like', "%{$q}%")
            ->whereNotIn('id', $busyTsIds);

        // ✅ Filter berdasarkan role untuk troubleshooting
        if ($userRole === 'Leader QC') {
            $troubleshootingQuery->where(function($q) {
                $q->where('line_area', 'Incoming')
                  ->orWhereNull('line_area');
            });
        } elseif ($userRole === 'Leader PPIC') {
            $troubleshootingQuery->where(function($q) {
                $q->where('line_area', 'Delivery')
                  ->orWhereNull('line_area');
            });
        } elseif (!empty($lineArea)) {
            $troubleshootingQuery->where(function($q) use ($lineArea) {
                $q->where('line_area', $lineArea)
                  ->orWhereNull('line_area');
            });
        }

        $troubleshooting = $troubleshootingQuery->get(['id', 'nama', 'grup', 'line_area']);
        
        Log::info('Troubleshooting Query Debug', [
            'query' => $q,
            'grup_pattern' => "{$grupInput}(Troubleshooting)%",
            'result_count' => $troubleshooting->count()
        ]);

        // ===============================
        // 3. ✅ UNIVERSAL TROUBLESHOOTING (Bebas Grup, Line, Station)
        // ===============================
        $universalQuery = ManPower::query()
            ->where('grup', 'Universal(Troubleshooting)')
            ->where('nama', 'like', "%{$q}%")
            ->whereNotIn('id', $busyTsIds);

        $universal = $universalQuery->get(['id', 'nama', 'grup', 'line_area']);

        Log::info('Search Results', [
            'user_role' => $userRole,
            'regular' => $manPower->count(),
            'troubleshooting' => $troubleshooting->count(),
            'universal' => $universal->count()
        ]);

        // ===============================
        // Gabungkan Hasil
        // ===============================
        $result = collect($manPower)
            ->map(fn($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'grup' => $item->grup,
                'line_area' => $item->line_area,
                'type' => 'regular'
            ])
            ->merge(
                $troubleshooting->map(fn($item) => [
                    'id' => 't-' . $item->id,
                    'nama' => $item->nama . ' (TS)',
                    'grup' => $item->grup,
                    'line_area' => $item->line_area,
                    'type' => 'troubleshooting'
                ])
            )
            ->merge(
                $universal->map(fn($item) => [
                    'id' => 't-' . $item->id,
                    'nama' => $item->nama . ' (UNIVERSAL)',
                    'grup' => 'Universal',
                    'line_area' => 'ALL',
                    'type' => 'universal'
                ])
            )
            ->unique('id')
            ->values();

        return response()->json($result);

    } catch (\Exception $e) {
        Log::error('ManPower Search Error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'error' => 'Search failed',
            'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
        ], 500);
    }
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

public function searchAvailableReplacement(Request $request)
{
    $request->validate([
        'query' => 'nullable|string',
        'grup' => 'required|string',
    ]);

    $query = $request->input('query', '');
    $grupInput = $request->input('grup');

    // Ambil semua Man Power yang sedang aktif di tabel Henkaten (masih berlangsung)
    $activeHenkatenIds = \App\Models\ManPowerHenkaten::where(function ($q) {
            $q->whereNull('updated_at') // belum ditutup
              ->orWhere('status', 'PENDING'); // atau masih menunggu approval
        })
        ->pluck('man_power_id_after')
        ->filter()
        ->unique()
        ->values()
        ->all();

    // Pisahkan ID regular dan TS
    $busyRegularIds = [];
    $busyTsIds = [];

    foreach ($activeHenkatenIds as $id) {
        if (is_string($id) && str_starts_with($id, 't-')) {
            $busyTsIds[] = (int) substr($id, 2);
        } else {
            $busyRegularIds[] = (int) $id;
        }
    }

    // Ambil Man Power reguler yang bisa jadi pengganti
    $availableRegular = \App\Models\ManPower::query()
        ->where('grup', $grupInput)
        ->where('status', 'aktif')
        ->when($query, fn($q) => $q->where('nama', 'like', "%{$query}%"))
        ->whereNotIn('id', $busyRegularIds)
        ->get(['id', 'nama', 'grup']);

    // Ambil Troubleshooting (TS)
    $grupTs = str_contains($grupInput, 'Troubleshooting') ? substr($grupInput, 0, 1) : $grupInput;

    $availableTs = \App\Models\Troubleshooting::query()
        ->where('grup', $grupTs)
        ->when($query, fn($q) => $q->where('nama', 'like', "%{$query}%"))
        ->whereNotIn('id', $busyTsIds)
        ->get(['id', 'nama', 'grup']);

    // Gabungkan hasilnya
    $result = collect()
        ->merge(
            $availableRegular->map(fn($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'grup' => $item->grup,
            ])
        )
        ->merge(
            $availableTs->map(fn($item) => [
                'id' => 't-' . $item->id,
                'nama' => $item->nama . ' (TS)',
                'grup' => $item->grup,
            ])
        )
        ->unique('nama')
        ->values();

    return response()->json($result);
}

 

}
