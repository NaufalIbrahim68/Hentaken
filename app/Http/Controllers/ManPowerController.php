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
use Illuminate\Support\Facades\Auth;


class ManPowerController extends Controller
{
    
    // ==============================================================
    // ✅ INDEX - FIXED: Prevent Duplicates
    // ==============================================================
    public function index(Request $request) 
    {
        // 1. Ambil nilai filter dari request
        $selectedLineArea = $request->get('line_area');

        // 2. Ambil semua line_area yang unik untuk opsi dropdown
        $lineAreas = ManPower::select('line_area')
                            ->whereNotNull('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area');

        // 3. ✅ Buat query dengan DISTINCT dan eager loading proper
        $query = ManPower::query()
            ->with(['station', 'stations']) // Load relasi station utama dan many stations
            ->select('man_power.*') // Select semua kolom dari man_power
            ->distinct(); // ✅ Hindari duplikasi

        // 4. Terapkan filter JIKA $selectedLineArea ada isinya
        if ($selectedLineArea) {
            $query->where('man_power.line_area', $selectedLineArea); // Specify table
        }

        // 5. ✅ Order by dan paginate dengan distinct
        $man_powers = $query->orderBy('man_power.nama', 'asc')
                           ->paginate(10)
                           ->appends(['line_area' => $selectedLineArea]); // Keep filter on pagination

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
        $lineAreas = Station::select('line_area')
                            ->whereNotNull('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc') 
                            ->pluck('line_area');

        return view('manpower.create', compact('lineAreas'));
    }

    // ==============================================================
    // GET STATIONS BY LINE AREA (AJAX)
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
    // ✅ STORE MASTER MANPOWER - FIXED: No Duplicates
    // ==============================================================
    public function storeMaster(Request $request)
    {
        // 1. VALIDASI
        $request->validate([
            'nama' => 'required|string|max:255',
            'station_id' => 'required|exists:stations,id', // ✅ Main station wajib
            'grup' => 'required|string',
            'shift' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'waktu_mulai' => 'required|date_format:H:i',
        ]);

        DB::beginTransaction();
        try {
            // 2. AMBIL DATA REQUEST
            $data = $request->only(['nama', 'station_id', 'shift', 'grup', 'tanggal_mulai', 'waktu_mulai']);

            // 3. CARI LINE AREA BERDASARKAN STATION_ID
            $station = Station::find($data['station_id']);
            $lineAreaOfStation = $station ? $station->line_area : null;

            // 4. PROSES SIMPAN DATA
            if (str_contains($data['grup'], 'Troubleshooting')) {
                
                // Simpan ke troubleshooting
                $grupTs = substr($data['grup'], 0, 1);
                $troubleshooting = Troubleshooting::create([
                    'nama' => $data['nama'],
                    'grup' => $grupTs,
                    'status' => 'normal', 
                ]);

                // ✅ Buat 1 RECORD manpower saja
                $manPower = ManPower::create([
                    'nama' => $data['nama'],
                    'grup' => $data['grup'],
                    'station_id' => $data['station_id'], // Main station
                    'shift' => $data['shift'] ?? null,
                    'line_area' => $lineAreaOfStation,
                    'status' => 'pending',
                    'troubleshooting_id' => $troubleshooting->id,
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'waktu_mulai' => $data['waktu_mulai'],
                ]);

                // ✅ Simpan main station ke relasi dengan flag is_main_operator = 1
                $manPower->stations()->attach($data['station_id'], [
                    'is_main_operator' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            } else {
                // ✅ Buat 1 RECORD manpower biasa
                $manPower = ManPower::create([
                    'nama' => $data['nama'],
                    'grup' => $data['grup'],
                    'station_id' => $data['station_id'], // Main station
                    'line_area' => $lineAreaOfStation,
                    'status' => 'pending',
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'waktu_mulai' => $data['waktu_mulai'],
                ]);

                // ✅ Simpan main station ke relasi dengan flag is_main_operator = 1
                $manPower->stations()->attach($data['station_id'], [
                    'is_main_operator' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            Log::info('ManPower Created Successfully', [
                'id' => $manPower->id,
                'nama' => $manPower->nama,
                'station_id' => $data['station_id'],
            ]);

            return redirect()->route('manpower.index')
                         ->with('success', 'Data Man Power berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ManPower Store Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    // ==============================================================
    // EDIT MASTER MANPOWER
    // ==============================================================
    public function edit($id)
    {
        $man_power = ManPower::with('stations')->findOrFail($id);
        
        $lineAreas = Station::select('line_area')->distinct()->pluck('line_area');
        $stations = Station::all();

        return view('manpower.edit_master', compact('man_power', 'lineAreas', 'stations'));
    }

    // ==============================================================
    // ✅ UPDATE MASTER MANPOWER - FIXED
    // ==============================================================
    public function updateMaster(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama'      => 'required|string|max:255',
            'line_area' => 'required|string|max:255',
            'group'     => 'required|in:A,B',
        ]);

        DB::beginTransaction();
        try {
            $man_power = ManPower::findOrFail($id);
            
            // ✅ Update HANYA data utama, TIDAK update station_id
            // karena station dikelola via relasi many-to-many
            $man_power->update([
                'nama' => $validatedData['nama'],
                'line_area' => $validatedData['line_area'],
                'grup' => $validatedData['group'], // Note: validasi pakai 'group', model pakai 'grup'
            ]);

            DB::commit();

            Log::info('ManPower Updated Successfully', [
                'id' => $man_power->id,
                'nama' => $man_power->nama,
            ]);

            return redirect()->route('manpower.index')
                ->with('success', 'Data Man Power berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ManPower Update Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal mengupdate data: ' . $e->getMessage()]);
        }
    }

    // ==============================================================
    // DESTROY MASTER MANPOWER
    // ==============================================================
    public function destroyMaster($id)
    {
        DB::beginTransaction();
        try {
            $man_power = ManPower::findOrFail($id);
            
            // ✅ Hapus relasi stations terlebih dahulu
            $man_power->stations()->detach();
            
            // Hapus man power
            $man_power->delete();

            DB::commit();

            Log::info('ManPower Deleted Successfully', [
                'id' => $id,
            ]);

            return redirect()->route('manpower.index')
                ->with('success', 'Data Man Power berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ManPower Delete Error', [
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }

        // ==============================================================
    // ✅ STATION MANAGEMENT (MODAL) - FIXED
    // ==============================================================
    public function storeStation(Request $request)
    {
        $data = $request->validate([
            'man_power_id' => 'required|exists:man_power,id',
            'station_id'   => 'required|exists:stations,id',
        ]);

        DB::beginTransaction();
        try {
            $manPower = ManPower::findOrFail($data['man_power_id']);

            // ✅ Cek duplikat
            if ($manPower->stations()->where('station_id', $data['station_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Station sudah ada.'
                ], 422);
            }

            // ✅ PROTEKSI: Tidak boleh tambah station yang sama dengan main station
            if ($manPower->station_id == $data['station_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Station ini sudah menjadi main station.'
                ], 422);
            }

            // ✅ Attach dengan flag is_main_operator = 0 (backup)
            $manPower->stations()->attach($data['station_id'], [
                    'status' => 'PENDING',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Station Added to ManPower', [
                'man_power_id' => $data['man_power_id'],
                'station_id' => $data['station_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Station berhasil ditambahkan.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Add Station Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan station.'
            ], 500);
        }
    }

    public function destroyStation(Request $request, $id)
    {
        $request->validate([
            'man_power_id' => 'required|exists:man_power,id',
        ]);

        DB::beginTransaction();
        try {
            $manPower = ManPower::findOrFail($request->man_power_id);
            
            // ✅ PROTEKSI: Tidak boleh hapus main station
            if ($manPower->station_id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Station utama tidak dapat dihapus!'
                ], 403);
            }
            
            // ✅ Detach station dari relasi
            $deleted = $manPower->stations()->detach($id);

            if ($deleted === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Station tidak ditemukan.'
                ], 404);
            }

            DB::commit();

            Log::info('Station Removed from ManPower', [
                'man_power_id' => $request->man_power_id,
                'station_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Station berhasil dihapus.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Delete Station Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus station.'
            ], 500);
        }
    }

    public function updateStation(Request $request, $id)
    {
        $request->validate(['station_name' => 'required|string|max:255']);

        $station = Station::findOrFail($id);
        $station->update(['station_name' => $request->station_name]);

        return response()->json($station);
    }

    // ==============================================================
    // ✅ SEARCH - FIXED: Proper station filtering
    // ==============================================================
   public function search(Request $request)
{
    try {
        $q = $request->input('query', '');
        $grupInput = $request->input('grup', '');
        $lineArea = $request->input('line_area', '');
        
        // Mengubah input station_id menjadi integer (akan jadi 0 jika inputnya '', null, atau 'null')
        $stationIdInput = $request->input('station_id');
        $stationIdInt = filter_var($stationIdInput, FILTER_VALIDATE_INT) ? (int)$stationIdInput : 0;
        
        if (strlen($q) < 2 || empty($grupInput)) {
            return response()->json([]);
        }

        $nowFull = Carbon::now();
        $userRole = Auth::check() ? Auth::user()->role : null;
        
        // --- 1. Ambil ID Man Power yang SEDANG BERTUGAS (Busy IDs) ---
        $activeManpowerIds = ManPowerHenkaten::whereIn('status', ['Approved', 'PENDING'])
            ->where(function ($query) use ($nowFull) {
                $query->whereNull('end_date')
                    ->orWhere(fn($q) => 
                        $q->whereRaw("CONCAT(end_date, ' ', time_end) >= ?", [$nowFull->toDateTimeString()])
                    );
            })
            ->pluck('man_power_id_after')
            ->filter()
            ->unique();
        
        $busyRegularIds = [];
        $busyTsIds = [];

        foreach ($activeManpowerIds as $id) {
            if (is_string($id) && str_starts_with($id, 't-')) {
                $busyTsIds[] = (int) substr($id, 2); 
            } else {
                $busyRegularIds[] = (int) $id; 
            }
        }
        
        // --- 2. Query Man Power REGULER ---
        $manPowerQuery = ManPower::query()
            ->select('id', 'nama', 'grup', 'line_area')
            ->where('grup', $grupInput)
            ->where('nama', 'like', "%{$q}%")
            ->whereNotIn('id', $busyRegularIds);

        // Filter role/line area
        if ($userRole === 'Leader QC') {
            $manPowerQuery->where('line_area', 'Incoming');
        } elseif ($userRole === 'Leader PPIC') {
            $manPowerQuery->where('line_area', 'Delivery');
        } elseif (!empty($lineArea)) {
            $manPowerQuery->where('line_area', $lineArea);
        }

        // Filter Station: Hanya jalankan jika stationId terdeteksi (yaitu > 0)
        if ($stationIdInt > 0) {
            $manPowerQuery->where(function($query) use ($stationIdInt) {
                // Asumsi: ManPower memiliki relasi belongsToMany 'stations'
                $query->whereHas('stations', function ($q) use ($stationIdInt) {
                    $q->where('station_id', $stationIdInt);
                })
                // Opsi 2: Hapus atau perbaiki jika kolom 'station_id' tidak ada di tabel man_power
                ->orWhere('station_id', $stationIdInt); 
            });
        }
        
        $manPower = $manPowerQuery->get();
        
        // --- 3. Query Man Power TROUBLESHOOTING (Grup spesifik) ---
        $troubleshootingQuery = ManPower::query()
            ->select('id', 'nama', 'grup', 'line_area')
            ->where('grup', 'like', "{$grupInput}(Troubleshooting)%")
            ->where('nama', 'like', "%{$q}%")
            ->whereNotIn('id', $busyTsIds);

        // Filter role/line area untuk TS
        if ($userRole === 'Leader QC') {
            $troubleshootingQuery->where(fn($q) => $q->where('line_area', 'Incoming')->orWhereNull('line_area'));
        } elseif ($userRole === 'Leader PPIC') {
            $troubleshootingQuery->where(fn($q) => $q->where('line_area', 'Delivery')->orWhereNull('line_area'));
        } elseif (!empty($lineArea)) {
            $troubleshootingQuery->where(fn($q) => $q->where('line_area', $lineArea)->orWhereNull('line_area'));
        }

        $troubleshooting = $troubleshootingQuery->get();
        
        // --- 4. Query Man Power UNIVERSAL TROUBLESHOOTING ---
        $universalQuery = ManPower::query()
            ->select('id', 'nama', 'grup', 'line_area')
            ->where('grup', 'Universal(Troubleshooting)')
            ->where('nama', 'like', "%{$q}%")
            ->whereNotIn('id', $busyTsIds); 
        
        $universal = $universalQuery->get();

        // --- 5. Gabungkan dan Format Hasil ---
        $result = collect($manPower)
            ->map(fn($item) => [
                'id' => (string) $item->id,
                'nama' => $item->nama,
                'grup' => $item->grup,
                'line_area' => $item->line_area,
                'type' => 'regular'
            ])
            ->merge(
                $troubleshooting->map(fn($item) => [
                    'id' => 't-' . $item->id, // Format ID dengan prefix 't-'
                    'nama' => $item->nama,
                    'grup' => $item->grup,
                    'line_area' => $item->line_area,
                    'type' => 'troubleshooting'
                ])
            )
            ->merge(
                $universal->map(fn($item) => [
                    'id' => 't-' . $item->id, // Format ID dengan prefix 't-'
                    'nama' => $item->nama,
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
            'line' => $e->getLine(),
            'request_params' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json(['error' => 'Search failed', 'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'], 500);
    }
}

    // ==============================================================
    // OTHER METHODS (unchanged)
    // ==============================================================
    public function getManPower(Request $request)
    {
        $grup = $request->input('grup');
        $line_area = $request->input('line_area');
        $station_id = $request->input('station_id');

        $result = [];

        if ($station_id && $line_area) {
            $manPowerQuery = ManPower::where('grup', $grup)
                ->where('station_id', $station_id)
                ->where('line_area', $line_area);

            $manPowerInStation = $manPowerQuery->get();

            if ($manPowerInStation->count() > 1) {
                foreach ($manPowerInStation as $mp) {
                    $result[] = [
                        'id' => $mp->id,
                        'nama' => $mp->nama,
                    ];
                }
            }
        }

        $tsGrup = substr($grup, 0, 1);
        $troubleshooting = Troubleshooting::where('grup', $tsGrup)->get();

        foreach ($troubleshooting as $ts) {
            $result[] = [
                'id' => 't-' . $ts->id,
                'nama' => $ts->nama . ' (TS)',
            ];
        }

        return response()->json($result);
    }

    public function confirmation()
    {
        $manpowers = ManPower::where('status', 'pending')->get();
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

        $activeHenkatenIds = \App\Models\ManPowerHenkaten::where(function ($q) {
                $q->whereNull('updated_at')
                  ->orWhere('status', 'PENDING');
            })
            ->pluck('man_power_id_after')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $busyRegularIds = [];
        $busyTsIds = [];

        foreach ($activeHenkatenIds as $id) {
            if (is_string($id) && str_starts_with($id, 't-')) {
                $busyTsIds[] = (int) substr($id, 2);
            } else {
                $busyRegularIds[] = (int) $id;
            }
        }

        $availableRegular = \App\Models\ManPower::query()
            ->where('grup', $grupInput)
            ->where('status', 'aktif')
            ->when($query, fn($q) => $q->where('nama', 'like', "%{$query}%"))
            ->whereNotIn('id', $busyRegularIds)
            ->get(['id', 'nama', 'grup']);

        $grupTs = str_contains($grupInput, 'Troubleshooting') ? substr($grupInput, 0, 1) : $grupInput;

        $availableTs = \App\Models\Troubleshooting::query()
            ->where('grup', $grupTs)
            ->when($query, fn($q) => $q->where('nama', 'like', "%{$query}%"))
            ->whereNotIn('id', $busyTsIds)
            ->get(['id', 'nama', 'grup']);

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