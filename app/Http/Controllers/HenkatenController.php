<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MaterialHenkaten;
use App\Models\MachineHenkaten;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use App\Models\Material;
use App\Models\Method;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str; // <--- ADD THIS LINE

class HenkatenController extends Controller
{
    // ==============================================================
    // BAGIAN 1: FORM PEMBUATAN HENKATEN MAN POWER
    // ==============================================================
  public function create()
{
    $user = Auth::user();
    // Gunakan user->role yang sudah terformat Title Case jika memungkinkan
    $userRole = $user->role ?? 'Operator';

    // Cek apakah user punya record manpower
    $manpower = ManPower::where('nama', $user->name)->first();
    // Gunakan ManPower::where('user_id', $user->id) jika sudah ada relasi
    $isMainOperator = $manpower?->is_main_operator == 1;

    // Ambil semua line area
    $allLineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();

    // Default values
    $stations = collect();
    $lineAreas = $allLineAreas;
    $showStationDropdown = false;
    $roleLineArea = ''; // Dipakai untuk role yang Line Area-nya fix

    // ============== ROLE: LEADER FA / SMT ==================
    if ($userRole === 'Leader FA' || $userRole === 'Leader SMT')
    {
        $prefix = (str_ends_with($userRole, 'FA')) ? 'FA' : 'SMT';

        // Leader FA/SMT boleh melihat semua line area mereka
        $stations = Station::where('line_area', 'LIKE', $prefix . '%')->get();
        $lineAreas = array_values(array_filter($allLineAreas, fn($a) => str_starts_with($a, $prefix)));

        $showStationDropdown = true;
    }

elseif (in_array($userRole, ['Leader PPIC', 'Leader QC']))
{
    $fixedLineArea = ($userRole === 'Leader QC') ? 'Incoming' : 'Delivery';

    $stations = DB::table('stations')
        ->where('stations.line_area', $fixedLineArea)
        ->select(
            'stations.id',
            'stations.station_name',
            'stations.line_area',
            DB::raw("(
                SELECT TOP 1 CAST(ISNULL(mp.is_main_operator, 0) AS INT)
                FROM man_power mp
                WHERE mp.station_id = stations.id
                  AND mp.line_area = '{$fixedLineArea}'
                ORDER BY mp.is_main_operator DESC, mp.id DESC
            ) as is_main_operator")
        )
        ->orderBy('stations.station_name')
        ->get();

    $lineAreas = [$fixedLineArea];
    $roleLineArea = $fixedLineArea;
    $showStationDropdown = true;

    // DEBUG
    Log::info('=== LEADER QC/PPIC DEBUG ===');
    Log::info('Stations Data: ', $stations->toArray());
}

// ============== ROLE LAIN (Operator/Admin/DLL) ==================
    else
    {
        if ($manpower) {
            // Operator memiliki Line Area dan Station yang fix
            $stations = Station::where('id', $manpower->station_id)->get();
            $lineAreas = [$manpower->line_area];
            $roleLineArea = $manpower->line_area;

            // Jika dia Main Operator, dia bisa mengisi Henkaten (dan stationnya terpilih otomatis/readonly)
            // Jika dia non-Main Operator, dia mungkin hanya bisa melihat/tidak boleh mengisi
            $showStationDropdown = $isMainOperator;

            // Jika dia bukan Main Operator, kita set station menjadi fix (hidden input)
            if (!$isMainOperator) {
                $stations = Station::where('id', $manpower->station_id)->get();
                $lineAreas = [$manpower->line_area];
                $showStationDropdown = false;
            }
        } else {
            // User bukan Manpower
            $stations = collect();
            $lineAreas = [];
            $showStationDropdown = false;
        }
    }

    // ================= SHIFT ==================
    $now = Carbon::now('Asia/Jakarta');
    // Asumsi shift 2: 07:00 - 18:59:59. Shift 1: 19:00 - 06:59:59 (+1 hari)
    $shift2Start = $now->copy()->setTime(7, 0, 0);
    $shift1Start = $now->copy()->setTime(19, 0, 0);

    // Logika Shift: Shift 2 jika antara 7 pagi dan 7 malam, Shift 1 selain itu.
    $currentShift = ($now->gte($shift2Start) && $now->lt($shift1Start)) ? 2 : 1;

    // ================= GROUP ==================
    $currentGroup = Session::get('active_grup');
    if (!$currentGroup) {
        return redirect()->route('dashboard')
            ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.');
    }

    return view('manpower.create_henkaten', compact(
        'lineAreas',
        'stations',
        'currentShift',
        'currentGroup',
        'userRole',
        'isMainOperator',
        'showStationDropdown',
        'roleLineArea'
    ));
}


    // ==============================================================
    // BAGIAN 3: FORM PEMBUATAN HENKATEN METHOD
    // ==============================================================
  public function store(Request $request)
{
    $validated = $request->validate([
        'shift'               => 'required|string',
        'grup'                => 'required|string',
        'man_power_id'        => 'required|integer|exists:man_power,id',
        'man_power_id_after'  => 'required|integer|exists:man_power,id|different:man_power_id',
        'station_id'          => 'required|integer|exists:stations,id',
        'keterangan'          => 'required|string',
        'line_area'           => 'required|string',
        'effective_date'      => 'required|date',
        'end_date'            => 'required|date|after_or_equal:effective_date',
        'lampiran'            => 'nullable|file|mimes:jpeg,png,pdf,zip,rar|max:2048',
        'lampiran_2'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
        'lampiran_3'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240',
        'time_start'          => 'required|date_format:H:i',
        'time_end'            => [
        'required',
        'date_format:H:i',
        function ($attribute, $value, $fail) use ($request) {
            // Gabungkan tanggal + waktu untuk perbandingan
            $effectiveDate = $request->input('effective_date');
            $endDate = $request->input('end_date');
            $timeStart = $request->input('time_start');
            
            if ($effectiveDate && $endDate && $timeStart && $value) {
                try {
                    $dateTimeStart = Carbon::parse($effectiveDate . ' ' . $timeStart);
                    $dateTimeEnd = Carbon::parse($endDate . ' ' . $value);
                    
                    if ($dateTimeEnd->lte($dateTimeStart)) {
                        $fail('Tanggal & waktu berakhir harus setelah tanggal & waktu mulai.');
                    }
                } catch (\Exception $e) {
                    $fail('Format tanggal atau waktu tidak valid.');
                }
            }
        }
    ],
        'serial_number_start' => 'nullable|string|max:255',
        'serial_number_end'   => 'nullable|string|max:255',
    ], [
        'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.',
        'lampiran.mimes'               => 'Lampiran harus berupa jpeg, png, pdf, zip, atau rar.',
        'end_date.after_or_equal'      => 'Tanggal berakhir harus sama dengan atau setelah tanggal efektif.',
        'time_end.after'               => 'Waktu selesai harus setelah waktu mulai.',
    ]);

    // Array untuk track uploaded files (untuk rollback jika error)
    $uploadedFiles = [];

    try {
        DB::beginTransaction();

        // Get ManPower data
        $manPowerAsli  = ManPower::findOrFail($validated['man_power_id']);
        $manPowerAfter = ManPower::findOrFail($validated['man_power_id_after']);

        // Upload lampiran 1
        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran')
                ->store('henkaten_man_power_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran'];
        }

        // Upload lampiran 2
        if ($request->hasFile('lampiran_2')) {
            $validated['lampiran_2'] = $request->file('lampiran_2')
                ->store('henkaten_man_power_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran_2'];
        }

        // Upload lampiran 3
        if ($request->hasFile('lampiran_3')) {
            $validated['lampiran_3'] = $request->file('lampiran_3')
                ->store('henkaten_man_power_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran_3'];
        }

        // Prepare data untuk create
        $validated['nama']       = $manPowerAsli->nama;
        $validated['nama_after'] = $manPowerAfter->nama;
        $validated['status']     = 'PENDING';
        $validated['user_id']    = Auth::id(); // Pastikan field ini ada di fillable model

        // Create henkaten record
        $henkaten = ManPowerHenkaten::create($validated);

        DB::commit();

        return redirect()->route('henkaten.create')
            ->with('success', 'Henkaten Man Power berhasil dibuat.');

    } catch (\Exception $e) {
        DB::rollBack();

        // Delete all uploaded files jika terjadi error
        foreach ($uploadedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        Log::error('Error creating ManPower Henkaten: ' . $e->getMessage());

        return back()
            ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
            ->withInput();
    }
}

 public function createMethodHenkaten()
{
    // Ambil data shift dari session
    $currentShift = session('active_shift', 1);
    

    $user = Auth::user();
    $role = $user ? $user->role : null;
    $isPredefinedRole = in_array($role, ['Leader QC', 'Leader PPIC']);

    // --- INISIALISASI VARIABEL BARU ---
    $selectedLineArea = null;
    $predefinedLineArea = null;
    $methodList = collect(); // pastikan collection kosong
    $stations = collect();
    $lineAreas = collect();
    // -----------------------------------

    // --- A. LOGIKA PENENTUAN LINE AREA ---
    if ($role === 'Leader QC') {
        $lineAreas = collect(['Incoming']);
        $predefinedLineArea = 'Incoming';
    } elseif ($role === 'Leader PPIC') {
        $lineAreas = collect(['Delivery']);
        $predefinedLineArea = 'Delivery';
    } else {
        // Semua line_area unik dari tabel station
        $lineAreas = Station::select('line_area')
            ->distinct()
            ->whereNotNull('line_area')
            ->orderBy('line_area', 'asc')
            ->pluck('line_area');
    }

    if ($isPredefinedRole) {
        $selectedLineArea = $predefinedLineArea;

        // Ambil semua station di line area predefined
        $stations = Station::where('line_area', $selectedLineArea)->get();

        // Ambil semua method untuk semua station di line area tersebut
        $methodList = Method::whereIn('station_id', $stations->pluck('id'))
                            ->select('id', 'methods_name', 'station_id')
                            ->orderBy('methods_name')
                            ->get();

    } else {
        // Mode dinamis (role lain)
        $selectedLineArea = old('line_area');
        if ($selectedLineArea) {
            $stations = Station::where('line_area', $selectedLineArea)->get();
        }

        // Jika ada old station, ambil method untuk station tersebut
        if ($oldStationId = old('station_id')) {
            $methodList = Method::where('station_id', $oldStationId)
                                ->select('id', 'methods_name', 'station_id')
                                ->orderBy('methods_name')
                                ->get();
        }
    }

    return view('methods.create_henkaten', compact(
        'stations',
        'lineAreas',
        'currentShift',
        'isPredefinedRole',
        'predefinedLineArea',
        'selectedLineArea',
        'methodList'
    ));
}




public function storeMethodHenkaten(Request $request)
{
    $userRole = Auth::user()->role ?? 'operator';
    $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

    // =========================================================
    // Tentukan station_id untuk predefined role
    // =========================================================
    if ($isPredefinedRole) {
        $predefinedLineArea = match ($userRole) {
            'Leader QC'   => 'Incoming',
            'Leader PPIC' => 'Delivery',
        };

        $defaultStation = \App\Models\Station::where('line_area', $predefinedLineArea)->first();
        $stationIdTarget = $defaultStation ? $defaultStation->id : null;

        // Tetapkan line_area untuk predefined role
        $request->merge(['line_area' => $predefinedLineArea]);
    } else {
        // Ambil dari request untuk role lainnya
        $stationIdTarget = $request->input('station_id');
    }

    $methodIdTarget = $request->input('method_id');

    // =========================================================
    // Validasi kondisional
    // =========================================================
   $rules = [
    'shift'               => 'required|string',
    'line_area'           => 'required|string',
    'station_id'          => $isPredefinedRole 
                             ? 'nullable|integer|exists:stations,id'
                             : 'required|integer|exists:stations,id',
    'method_id'           => $isPredefinedRole 
                             ? 'required|integer|exists:methods,id' 
                             : 'nullable|integer|exists:methods,id',
    'effective_date'      => 'required|date',
    'end_date'            => 'required|date|after_or_equal:effective_date',
    'time_start'          => 'required|date_format:H:i',
    'time_end'            => [
        'required',
        'date_format:H:i',
        function ($attribute, $value, $fail) use ($request) {
            // Gabungkan tanggal + waktu untuk perbandingan
            $effectiveDate = $request->input('effective_date');
            $endDate = $request->input('end_date');
            $timeStart = $request->input('time_start');
            
            if ($effectiveDate && $endDate && $timeStart && $value) {
                try {
                    $dateTimeStart = Carbon::parse($effectiveDate . ' ' . $timeStart);
                    $dateTimeEnd = Carbon::parse($endDate . ' ' . $value);
                    
                    if ($dateTimeEnd->lte($dateTimeStart)) {
                        $fail('Tanggal & waktu berakhir harus setelah tanggal & waktu mulai.');
                    }
                } catch (\Exception $e) {
                    $fail('Format tanggal atau waktu tidak valid.');
                }
            }
        }
    ],
    'keterangan'          => 'required|string',
    'keterangan_after'    => 'required|string',
    'serial_number_start' => 'nullable|string|max:100',
    'serial_number_end'   => 'nullable|string|max:100',
    'note'                => 'nullable|string',
    'lampiran'            => 'required|file|mimes:jpeg,png,jpg,zip,rar|max:2048',
    'lampiran_2'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
    'lampiran_3'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
];
    $validated = $request->validate($rules);

    // ✅ Array untuk track uploaded files (untuk rollback)
    $uploadedFiles = [];

    try {
        DB::beginTransaction();

        // =========================================================
        // Upload File - KONSISTEN
        // =========================================================
        
        // Upload lampiran 1
        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran')
                ->store('henkaten_methods_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran'];
        }

        // Upload lampiran 2
        if ($request->hasFile('lampiran_2')) {
            $validated['lampiran_2'] = $request->file('lampiran_2')
                ->store('henkaten_methods_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran_2'];
        }

        // Upload lampiran 3
        if ($request->hasFile('lampiran_3')) {
            $validated['lampiran_3'] = $request->file('lampiran_3')
                ->store('henkaten_methods_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran_3'];
        }

        // =========================================================
        // Ambil methods_name dari DB jika ada method_id
        // =========================================================
        $methodName = $validated['method_id'] 
                        ? Method::find($validated['method_id'])->methods_name 
                        : null;

        // =========================================================
        // Format Time
        // =========================================================
        $timeStart = Carbon::createFromFormat('H:i', $validated['time_start']);
        $timeEnd   = Carbon::createFromFormat('H:i', $validated['time_end']);

        // =========================================================
        // Prepare Data
        // =========================================================
        $dataToCreate = [
            'station_id'          => $isPredefinedRole ? $stationIdTarget : $validated['station_id'],
            'method_id'           => $validated['method_id'] ?? null,
            'methods_name'        => $methodName,
            'shift'               => $validated['shift'],
            'line_area'           => $validated['line_area'],
            'keterangan'          => $validated['keterangan'],
            'keterangan_after'    => $validated['keterangan_after'],
            'effective_date'      => $validated['effective_date'],
            'end_date'            => $validated['end_date'],
            'time_start'          => $timeStart,
            'time_end'            => $timeEnd,
            'lampiran'            => $validated['lampiran'] ?? null,      // ✅ DIPERBAIKI
            'lampiran_2'          => $validated['lampiran_2'] ?? null,    // ✅ DITAMBAHKAN
            'lampiran_3'          => $validated['lampiran_3'] ?? null,    // ✅ DITAMBAHKAN
            'serial_number_start' => $validated['serial_number_start'] ?? null,
            'serial_number_end'   => $validated['serial_number_end'] ?? null,
            'note'                => $validated['note'] ?? null,
            'status'              => 'PENDING',
            'user_id'             => Auth::id(), // ✅ TAMBAHKAN jika ada di fillable
        ];

        MethodHenkaten::create($dataToCreate);

        DB::commit();

        return redirect()->route('henkaten.method.create')
            ->with('success', 'Data Henkaten Method berhasil dibuat.');

    } catch (\Exception $e) {
        DB::rollBack();

        // ✅ DELETE semua uploaded files jika error
        foreach ($uploadedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        Log::error('Method Henkaten store failed: ' . $e->getMessage(), [
            'exception' => $e,
            'input' => $request->except(['lampiran', 'lampiran_2', 'lampiran_3']) // Jangan log file
        ]);

        return back()
            ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data. Error: ' . $e->getMessage()])
            ->withInput();
    }
}

    public function getMethodsByStation(Request $request)
    {
        $stationId = $request->input('station_id');

        if (!$stationId) {
            // Mengembalikan 400 Bad Request jika ID hilang
            return response()->json(['error' => 'Station ID diperlukan.'], 400);
        }


        $methods = Method::where('station_id', $stationId)
                         ->select('id', 'methods_name')
                         ->get();

        return response()->json($methods);
    }




    // ==============================================================
    // BAGIAN 4: FORM PEMBUATAN HENKATEN MATERIAL
    // ==============================================================
   public function createMaterialHenkaten()
{
    // Cek Role dan Shift saat ini
    $currentShift = session('active_shift', 1);

    // --- LOGIKA ROLE PREDEFINED ---
    $userRole = Auth::user()->role ?? 'operator';
    $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

    $predefinedLineArea = match ($userRole) {
        'Leader QC' => 'Incoming',
        'Leader PPIC' => 'Delivery',
        default => null,
    };
    // -----------------------------

    // 1. Ambil semua Line Area unik (untuk dropdown dinamis, jika role bukan predefined)
    $lineAreas = Station::whereNotNull('line_area')
                        ->orderBy('line_area', 'asc')
                        ->pluck('line_area')
                        ->unique();

    // 2. Inisialisasi daftar model
    $stations = collect();
    $materialsForDynamicRole = collect(); // ✅ Ganti nama agar tidak konflik
    $defaultMaterialOptions = collect(); // ✅ Nama variabel yang dicari di Blade

    // 3. Logika mengisi data lama (old) untuk mode dinamis
    if (!$isPredefinedRole) {
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }
        if ($oldStation = old('station_id')) {
            // Ambil material untuk stasiun yang dipilih (mode dinamis)
            $materialsForDynamicRole = Material::where('station_id', $oldStation)->get();
        }
    }

    // 4. LOGIKA KHUSUS untuk Leader QC/PPIC (Mode Predefined)
    if ($isPredefinedRole && $predefinedLineArea) {
        // Ambil material yang stasiunnya berada di line area yang sesuai.
        // Hasilnya adalah Collection objek Material Eloquent (dengan ID dan Nama).
        $defaultMaterialOptions = Material::whereHas('station', function ($query) use ($predefinedLineArea) {
                                                $query->where('line_area', $predefinedLineArea);
                                            })
                                            ->select('id', 'material_name') // Ambil ID dan Nama
                                            ->get();
    }

    return view('materials.create_henkaten', compact(
        'lineAreas',
        'stations',
        'materialsForDynamicRole', // Digunakan untuk mode dinamis (jika old() ada)
        'currentShift',
        'userRole',
        'isPredefinedRole',
        'predefinedLineArea',
        'defaultMaterialOptions' // ✅ Variabel yang akan digunakan di Blade untuk Leader QC/PPIC
    ));
}

 public function storeMaterialHenkaten(Request $request)
{
    // 1. Tentukan Role Pengguna
    $userRole = Auth::user()->role ?? 'operator';
    $isPredefinedRole = ($userRole === 'Leader PPIC' || $userRole === 'Leader QC');

    $stationIdValue = null;

    // =========================================================
    // MODIFIKASI: Pencarian ID (Station & Material) untuk Role Predefined
    // =========================================================
    if ($isPredefinedRole) {
        $lineArea = $request->input('line_area');
        $materialName = $request->input('material_name');

        $material = Material::where('material_name', $materialName)
            ->whereHas('station', function ($query) use ($lineArea) {
                $query->where('line_area', $lineArea);
            })
            ->first();

        if ($material) {
            $stationIdValue = $material->station_id;
            $station = Station::find($stationIdValue);

            if ($station) {
                $request->merge([
                    'material_id' => $material->id,
                    'station_id' => $station->id
                ]);
            } else {
                $request->merge(['material_id' => null, 'station_id' => null]);
            }
        } else {
            $request->merge(['material_id' => null, 'station_id' => null]);
        }
    }

    // 3. Validasi Data
    try {
        $validationRules = [
            'shift'               => 'required|string',
            'line_area'           => 'required|string',
            'station_id'          => 'required|integer|exists:stations,id',
            'material_id'         => 'required|integer|exists:materials,id',
            'material_name'       => 'nullable|string|max:255',
            'effective_date'      => 'required|date',
            'end_date'            => 'required|date|after_or_equal:effective_date',
            'time_start'          => 'required|date_format:H:i',
            'time_end'            => [
        'required',
        'date_format:H:i',
        function ($attribute, $value, $fail) use ($request) {
            // Gabungkan tanggal + waktu untuk perbandingan
            $effectiveDate = $request->input('effective_date');
            $endDate = $request->input('end_date');
            $timeStart = $request->input('time_start');
            
            if ($effectiveDate && $endDate && $timeStart && $value) {
                try {
                    $dateTimeStart = Carbon::parse($effectiveDate . ' ' . $timeStart);
                    $dateTimeEnd = Carbon::parse($endDate . ' ' . $value);
                    
                    if ($dateTimeEnd->lte($dateTimeStart)) {
                        $fail('Tanggal & waktu berakhir harus setelah tanggal & waktu mulai.');
                    }
                } catch (\Exception $e) {
                    $fail('Format tanggal atau waktu tidak valid.');
                }
            }
        }
    ],
            'description_before'  => 'required|string|max:255',
            'description_after'   => 'required|string|max:255',
            'keterangan'          => 'required|string',
            'lampiran'            => 'required|file|mimes:jpeg,png,jpg,zip,rar|max:2048',
            'lampiran_2'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
            'lampiran_3'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
            'serial_number_start' => 'nullable|string|max:255',
            'serial_number_end'   => 'nullable|string|max:255',
            'redirect_to'         => 'nullable|string'
        ];

        $customMessages = [];

        if ($isPredefinedRole) {
            $resolvedStationId = $request->input('station_id');
            $resolvedMaterialId = $request->input('material_id');
            $inputLineArea = $request->input('line_area');
            $inputMaterialName = $request->input('material_name');

            if ($resolvedStationId === null) {
                $customMessages['station_id.required'] = "Data master Stasiun default untuk '{$inputLineArea}' tidak ditemukan (ID Material: {$resolvedMaterialId}). Mohon periksa tabel stations Anda.";
            }
            if ($resolvedMaterialId === null) {
                $customMessages['material_id.required'] = "Data master Material '{$inputMaterialName}' tidak ditemukan di Line Area '{$inputLineArea}'. Mohon periksa tabel materials Anda.";
            }
        }

        $validatedData = $request->validate($validationRules, $customMessages);

    } catch (ValidationException $e) {
        return back()->withErrors($e->errors())->withInput();
    }

    // ✅ Array untuk track uploaded files (untuk rollback)
    $uploadedFiles = [];

    // 4. Proses Penyimpanan Data ke Database
    try {
        DB::beginTransaction();

        // =========================================================
        // Upload File - KONSISTEN
        // =========================================================
        
        // Upload lampiran 1
        if ($request->hasFile('lampiran')) {
            $validatedData['lampiran'] = $request->file('lampiran')
                ->store('henkaten_materials_lampiran', 'public');
            $uploadedFiles[] = $validatedData['lampiran'];
        }

        // Upload lampiran 2
        if ($request->hasFile('lampiran_2')) {
            $validatedData['lampiran_2'] = $request->file('lampiran_2')
                ->store('henkaten_materials_lampiran', 'public');
            $uploadedFiles[] = $validatedData['lampiran_2'];
        }

        // Upload lampiran 3
        if ($request->hasFile('lampiran_3')) {
            $validatedData['lampiran_3'] = $request->file('lampiran_3')
                ->store('henkaten_materials_lampiran', 'public');
            $uploadedFiles[] = $validatedData['lampiran_3'];
        }

        // =========================================================
        // Prepare Data
        // =========================================================
        $dataToCreate = $validatedData;

        // Hapus field yang tidak ada di Model/Tabel
        unset($dataToCreate['material_name']);
        unset($dataToCreate['redirect_to']);

        // Tambahkan field tambahan
        $dataToCreate['status'] = 'PENDING';
        $dataToCreate['user_id'] = Auth::id();

        MaterialHenkaten::create($dataToCreate);

        DB::commit();

        $redirectTo = $request->input('redirect_to', route('henkaten.material.create'));
        return redirect($redirectTo)
            ->with('success', 'Data Material Henkaten berhasil disimpan!');

    } catch (\Exception $e) {
        DB::rollBack();

        // ✅ DELETE semua uploaded files jika error
        foreach ($uploadedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        Log::error('Material Henkaten store failed: ' . $e->getMessage(), [
            'exception' => $e,
            'input' => $request->except(['lampiran', 'lampiran_2', 'lampiran_3']) // Jangan log file
        ]);

        return back()
            ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data. Hubungi administrator.'])
            ->withInput();
    }
}

    // ==============================================================
    // BAGIAN 5: FORM PEMBUATAN HENKATEN MACHINE
    // ==============================================================
   
public function createMachineHenkaten()
{
    $log = null; // Inisialisasi untuk mode create
    
    // 1. Ambil data shift & grup dari session
    $currentGroup = session('active_grup');
    $currentShift = session('active_shift', 1);

    if (!$currentGroup) {
        return redirect()->route('dashboard')
            ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.');
    }

    // --- LOGIKA ROLE & FILTER LINE AREA ---
    $user = Auth::user();
    $role = $user ? $user->role : null;
    
    // 🟢 PERUBAHAN: Leader FA tidak termasuk isPredefinedRole
    $isPredefinedRole = in_array($role, ['Leader QC', 'Leader PPIC']);
    $isLeaderFA = ($role === 'Leader FA');

    // Ambil SEMUA Line Area unik yang ada di tabel 'stations'
    $allLineAreas = Station::select('line_area')
        ->distinct()
        ->whereNotNull('line_area')
        ->orderBy('line_area', 'asc')
        ->pluck('line_area');

    // Filter Line Areas yang akan ditampilkan/dipilih berdasarkan role
    if ($role === 'Leader QC') {
        $lineAreas = $allLineAreas->filter(fn($area) => $area === 'Incoming')->values();
    } elseif ($role === 'Leader PPIC') {
        $lineAreas = $allLineAreas->filter(fn($area) => $area === 'Delivery')->values();
    } elseif ($isLeaderFA) {
        // 🟢 BARU: Filter Line Area khusus Leader FA
        $allowedFALineAreas = ['FA L1', 'FA L2', 'FA L3', 'FA L5', 'FA L6'];
        $lineAreas = $allLineAreas->filter(fn($area) => in_array($area, $allowedFALineAreas))->values();
    } else {
        $lineAreas = $allLineAreas; // Role lain melihat semua
    }

    // Tentukan Line Area yang sedang dipilih/aktif (default ke Line Area pertama yang tersedia)
    $selectedLineArea = old('line_area', $lineAreas->first());
    
    // Jika role adalah predefined, gunakan Line Area yang sudah difilter sebagai selected default
    $predefinedLineArea = $isPredefinedRole ? $lineAreas->first() : null;
    
    if ($isPredefinedRole) {
        $selectedLineArea = $predefinedLineArea;
    }
    
    // --- AMBIL DATA STATIONS DAN MACHINES BERDASARKAN SELECTED LINE AREA ---
    $stations = collect();
   $machinesToDisplay = collect([
    (object)['id' => 1, 'machines_category' => 'PROGRAM'],
    (object)['id' => 2, 'machines_category' => 'Machine & JIG'],
    (object)['id' => 3, 'machines_category' => 'Equipement'],
    (object)['id' => 4, 'machines_category' => 'Kamera'],
]);

    $machineCategories = collect(); // Nama kategori unik

   if ($selectedLineArea) {

    $stations = Station::where('line_area', $selectedLineArea)->get();

    // khusus Leader FA → selalu gunakan hardcode
    if ($isLeaderFA) {

        $machinesToDisplay = collect([
            (object)['id' => 1, 'machines_category' => 'PROGRAM'],
            (object)['id' => 2, 'machines_category' => 'Machine & JIG'],
            (object)['id' => 3, 'machines_category' => 'Equipement'],
            (object)['id' => 4, 'machines_category' => 'Kamera'],
        ]);

        $machineCategories = collect([
            'PROGRAM',
            'Machine & JIG',
            'Equipement',
            'Kamera'
        ]);

    } else {

        // role lain tetap pakai database
        $stationIds = $stations->pluck('id')->toArray();

        $machinesToDisplay = Machine::select('id','machines_category','station_id','deskripsi')
            ->whereIn('station_id', $stationIds)
            ->whereNotNull('machines_category')
            ->get();

        $machineCategories = $machinesToDisplay->pluck('machines_category')->unique();
    }



    // --- LOGIKA 'old' data untuk station ---
    // Di mode predefined, kita tidak perlu old('station_id') karena form hanya mengirim Line Area.
    $predefinedStationId = $stations->first()->id ?? null; // ID stasiun pertama di Line Area jika predefined

    // Untuk mode dynamic/operator, kita perlu old('station_id') untuk re-populasi.
    $oldStationId = old('station_id', $log?->station_id);

    return view('machines.create_henkaten', compact(
        'log',
        'lineAreas',
        'stations',
        'currentGroup',
        'currentShift',
        'isPredefinedRole',
        'predefinedLineArea', // Digunakan di blade untuk input hidden/static
        'selectedLineArea', // Digunakan untuk initial load stations di mode dynamic
        'predefinedStationId', // ID stasiun default untuk role predefined (jika ada)
        'machineCategories', // Daftar Category Name (Hanya untuk referensi)
        'machinesToDisplay' // KUNCI: Daftar Mesin Lengkap (ID, Category, Station ID) untuk Blade/JS
    ));
}
}


public function storeMachineHenkaten(Request $request)
{
    // --- 1. Tentukan Role Pengguna dan Inisialisasi Variabel ---
    $userRole = Auth::user()->role ?? 'operator';
    $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);
    $isLeaderFA = ($userRole === 'Leader FA');

    $dataToValidate = $request->all();
    $lineArea = $request->input('line_area'); 

    $machineIdTarget = null;
    $targetMachineId = null;
    $selectedMachine = null;
    $uploadedFiles = []; // ✅ Track uploaded files untuk rollback

    // --- A. Mencari/Menentukan STATION_ID dan MACHINE_ID ---
    
    if ($isLeaderFA) {
        $categoryInput = $request->input('category');
        
        if (!$categoryInput) {
            throw ValidationException::withMessages([
                'category' => 'Kategori mesin harus dipilih.'
            ]);
        }

        $allowedFACategories = ['PROGRAM', 'Machine & JIG', 'Equipement', 'Kamera'];
        if (!in_array($categoryInput, $allowedFACategories)) {
            throw ValidationException::withMessages([
                'category' => "Kategori '{$categoryInput}' tidak diizinkan untuk Leader FA."
            ]);
        }

        $allowedFALineAreas = ['FA L1', 'FA L2', 'FA L3', 'FA L5', 'FA L6'];
        if (!in_array($lineArea, $allowedFALineAreas)) {
            throw ValidationException::withMessages([
                'line_area' => "Line Area '{$lineArea}' tidak diizinkan untuk Leader FA."
            ]);
        }
        
        $stationExists = Station::where('id', $request->input('station_id'))
            ->where('line_area', $lineArea)
            ->exists();
            
        if (!$stationExists) {
            throw ValidationException::withMessages([
                'station_id' => "Station yang dipilih tidak valid untuk Line Area '{$lineArea}'."
            ]);
        }
        
        $dataToValidate['station_id'] = $request->input('station_id');
        $dataToValidate['id_machines'] = null;
        
        $machineCategory = $categoryInput;
        
    } else {
        $machineIdTarget = $request->input('id_machines');
        
        $selectedMachine = Machine::find($machineIdTarget);
        if (!$selectedMachine) {
            throw ValidationException::withMessages([
                'id_machines' => 'Machine ID tidak valid atau data master mesin tidak ditemukan.'
            ]);
        }

        $targetMachineId = $machineIdTarget;
        $machineCategory = $selectedMachine->machines_category;

        if ($isPredefinedRole) {
            $targetLineArea = match($userRole) {
                'Leader QC' => 'Incoming',
                'Leader PPIC' => 'Delivery',
                default => null
            };
            
            $machineStation = Station::find($selectedMachine->station_id);
            $machineLineArea = $machineStation ? $machineStation->line_area : null;
            
            if ($machineLineArea !== $targetLineArea) {
                throw ValidationException::withMessages([
                    'id_machines' => "Mesin '{$selectedMachine->machines_category}' tidak valid untuk Line Area '{$targetLineArea}'. Mesin ini berada di Line Area '{$machineLineArea}'."
                ]);
            }
            
            $dataToValidate['station_id'] = $selectedMachine->station_id;
        }
        
        $dataToValidate['id_machines'] = $targetMachineId;
    }

    // --- B. Validasi Data ---

    try {
        $request->merge($dataToValidate); 

        $validationRules = [
            'shift'               => 'required|string',
            'line_area'           => 'required|string',
            'station_id'          => 'required|integer|exists:stations,id',
            'effective_date'      => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:effective_date',
            'time_start'          => 'required|date_format:H:i',
            'time_end'            => [
        'required',
        'date_format:H:i',
        function ($attribute, $value, $fail) use ($request) {
            // Gabungkan tanggal + waktu untuk perbandingan
            $effectiveDate = $request->input('effective_date');
            $endDate = $request->input('end_date');
            $timeStart = $request->input('time_start');
            
            if ($effectiveDate && $endDate && $timeStart && $value) {
                try {
                    $dateTimeStart = Carbon::parse($effectiveDate . ' ' . $timeStart);
                    $dateTimeEnd = Carbon::parse($endDate . ' ' . $value);
                    
                    if ($dateTimeEnd->lte($dateTimeStart)) {
                        $fail('Tanggal & waktu berakhir harus setelah tanggal & waktu mulai.');
                    }
                } catch (\Exception $e) {
                    $fail('Format tanggal atau waktu tidak valid.');
                }
            }
        }
    ],
            'before_value'        => 'required|string|max:255',
            'after_value'         => 'required|string|max:255',
            'keterangan'          => 'required|string',
            'lampiran'            => 'required|file|mimes:jpeg,png,jpg,zip,rar|max:2048', // ✅ Ubah mimetypes ke mimes
            'lampiran_2'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
            'lampiran_3'          => 'nullable|file|mimes:png,jpg,jpeg,pdf,zip,rar|max:10240', 
            'serial_number_start' => 'nullable|string|max:255',
            'serial_number_end'   => 'nullable|string|max:255',
        ];

        if ($isLeaderFA) {
            $validationRules['category'] = 'required|string';
        } else {
            $validationRules['id_machines'] = 'required|integer|exists:machines,id';
        }

        $validated = $request->validate($validationRules);
        
        // --- C. Pengecekan Tumpang Tindih Waktu (Overlap Check) ---
        
        $newEffectiveDate = $validated['effective_date'];
        $newEndDate = $validated['end_date'] ?? '2999-12-31';
        $newTimeStart = $validated['time_start'];
        $newTimeEnd = $validated['time_end'];

        if ($isLeaderFA) {
            $overlappingHenkaten = MachineHenkaten::where('station_id', $validated['station_id'])
                ->where('machine', $machineCategory)
                ->whereIn('status', ['Approved', 'PENDING'])
                ->where(function ($query) use ($newEffectiveDate, $newEndDate, $newTimeStart, $newTimeEnd) {
                    $query->whereDate('effective_date', '<=', $newEndDate)
                          ->where(fn($q) => $q->whereDate('end_date', '>=', $newEffectiveDate)->orWhereNull('end_date'))
                          ->where(fn($q) => $q->whereTime('time_start', '<', $newTimeEnd)->whereTime('time_end', '>', $newTimeStart));
                })
                ->exists();
        } else {
            $overlappingHenkaten = MachineHenkaten::where('id_machines', $targetMachineId)
                ->whereIn('status', ['Approved', 'PENDING'])
                ->where(function ($query) use ($newEffectiveDate, $newEndDate, $newTimeStart, $newTimeEnd) {
                    $query->whereDate('effective_date', '<=', $newEndDate)
                          ->where(fn($q) => $q->whereDate('end_date', '>=', $newEffectiveDate)->orWhereNull('end_date'))
                          ->where(fn($q) => $q->whereTime('time_start', '<', $newTimeEnd)->whereTime('time_end', '>', $newTimeStart));
                })
                ->exists();
        }

        if ($overlappingHenkaten) {
            throw ValidationException::withMessages([
                'effective_date' => "Henkaten Mesin ('{$machineCategory}') sudah memiliki log yang masih aktif atau tumpang tindih dalam periode tanggal/waktu yang sama."
            ]);
        }
        
        // --- D. Proses Penyimpanan Data ---

        DB::beginTransaction();

        // =========================================================
        // Upload File - KONSISTEN
        // =========================================================
        
        // Upload lampiran 1
        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran')
                ->store('henkaten_machines_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran'];
        }

        // Upload lampiran 2
        if ($request->hasFile('lampiran_2')) {
            $validated['lampiran_2'] = $request->file('lampiran_2')
                ->store('henkaten_machines_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran_2'];
        }

        // Upload lampiran 3
        if ($request->hasFile('lampiran_3')) {
            $validated['lampiran_3'] = $request->file('lampiran_3')
                ->store('henkaten_machines_lampiran', 'public');
            $uploadedFiles[] = $validated['lampiran_3'];
        }

        // =========================================================
        // Prepare Data
        // =========================================================
        $dataToCreate = $validated;

        // Mapping kolom ke nama database yang benar
        if ($isLeaderFA) {
            $dataToCreate['machine'] = $machineCategory;
            $dataToCreate['id_machines'] = null;
        } else {
            $dataToCreate['machine'] = $selectedMachine->machines_category;
            $dataToCreate['id_machines'] = $targetMachineId;
        }
        
        $dataToCreate['description_before'] = $validated['before_value'];
        $dataToCreate['description_after'] = $validated['after_value'];
        $dataToCreate['status'] = 'PENDING';
        $dataToCreate['user_id'] = Auth::id(); // ✅ Tambahkan jika ada di fillable

        // Hapus key-key asli dari form yang tidak ada di tabel database
        unset($dataToCreate['before_value']);
        unset($dataToCreate['after_value']);
        
        // ✅ lampiran, lampiran_2, lampiran_3 sudah otomatis ada di $dataToCreate

        MachineHenkaten::create($dataToCreate);

        DB::commit();

        return redirect()->route('henkaten.machine.create')
            ->with('success', 'Data Henkaten Machine berhasil dibuat.');

    } catch (ValidationException $e) {
        return back()->withErrors($e->errors())->withInput();
    } 
    catch (\Exception $e) {
        DB::rollBack();
        
        // ✅ DELETE semua uploaded files jika error
        foreach ($uploadedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }
        
        Log::error('Machine Henkaten store failed: ' . $e->getMessage(), [
            'exception' => $e, 
            'input' => $request->except(['lampiran', 'lampiran_2', 'lampiran_3']) // Jangan log file
        ]);

        return back()
            ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data. Error: ' . $e->getMessage()])
            ->withInput();
    }
}   

    // ==============================================================
    // BAGIAN 6: API BANTUAN
    // ==============================================================


   public function getStationsByLine(Request $request)
{
    $request->validate(['line_area' => 'required|string']);
    
    $stations = Station::where('line_area', $request->line_area)
                        ->orderBy('station_name', 'asc')
                        ->get(['id', 'station_name', 'line_area']); // Tanpa is_main_operator dulu
    
    return response()->json($stations);
}

    public function getMaterialsByStation(Request $request)
    {
        $request->validate([
            'station_id' => 'required|integer|exists:stations,id'
        ]);

        $materials = Material::where('station_id', $request->station_id)
                            ->select('id', 'material_name')
                            ->get();

        return response()->json($materials);
    }

    public function getManPower(Request $request)
    {
        $data = $request->validate([
            'grup' => 'required|string',
            'line_area' => 'required|string',
            'station_id' => 'required|integer|exists:stations,id',
        ]);

        $employeeToReplace = ManPower::where('station_id', $data['station_id'])
            ->where('grup', $data['grup'])
            ->first(['id', 'nama']);

        if ($employeeToReplace) {
            return response()->json($employeeToReplace);
        }

        return response()->json([
            'id' => null,
            'nama' => 'Man power tidak ditemukan di station ini'
        ]);
    }

    // ==============================================================
    // BAGIAN 7: APPROVAL HENKATEN
    // ==============================================================
    public function approval()
    {
        $manpowers = ManPowerHenkaten::where('status', 'PENDING')->get();
        $machines  = MachineHenkaten::where('status', 'PENDING')->get();
        $methods   = MethodHenkaten::where('status', 'PENDING')->get();
        $materials = MaterialHenkaten::where('status', 'PENDING')->get();

        return view('secthead.henkaten-approval', compact(
            'manpowers',
            'machines',
            'methods',
            'materials'
        ));
    }

    private function getHenkatenItem($type, $id)
    {
        $modelClass = null;

        switch ($type) {
            case 'manpower':
                $modelClass = ManPowerHenkaten::class;
                break;
            case 'machine':
                $modelClass = MachineHenkaten::class;
                break;
            case 'method':
                $modelClass = MethodHenkaten::class;
                break;
            case 'material':
                $modelClass = MaterialHenkaten::class;
                break;
        }

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($id);
    }

    public function getHenkatenDetail($type, $id)
    {
        $modelClass = null;

        switch ($type) {
            case 'manpower':
                $modelClass = ManPowerHenkaten::class;
                break;
            case 'machine':
                $modelClass = MachineHenkaten::class;
                break;
            case 'method':
                $modelClass = MethodHenkaten::class;
                break;
            case 'material':
                $modelClass = MaterialHenkaten::class;
                break;
            default:
                return response()->json(['error' => 'Invalid type.'], 404);
        }

        $query = $modelClass::query();

        if ($type === 'manpower') {
            $query->with(['station', 'manPower']);
        } elseif ($type === 'machine') {
            $query->with(['station']);
        } elseif ($type === 'material') {
            $query->with(['station', 'material']);
        } elseif ($type === 'method') {
            $query->with(['station']);
        }

        $item = $query->find($id);

        if (!$item) {
            return response()->json(['error' => 'Data not found.'], 404);
        }

        return response()->json($item);
    }

    public function approveHenkaten(Request $request, $type, $id)
    {
        $item = $this->getHenkatenItem($type, $id);

        if (!$item) {
            return redirect()->route('henkaten.approval')->with('error', 'Data Henkaten tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $statusToSet = 'Approved';

            // Logika khusus untuk manpower PERMANEN
            if ($type == 'manpower' && $item->note == '-') {
                $masterManPower = ManPower::find($item->man_power_id);

                if ($masterManPower) {
                    $masterManPower->nama = $item->nama_after;
                    $masterManPower->save();
                } else {
                    throw new \Exception('Data Master ManPower (ID: ' . $item->man_power_id . ') tidak ditemukan. Approval dibatalkan.');
                }

                $statusToSet = 'APPROVED';
            }

            $item->status = $statusToSet;
            $item->save();

            DB::commit();

            return redirect()->route('henkaten.approval')->with('success', 'Henkaten ' . ucfirst($type) . ' berhasil di-approve.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Gagal approve Henkaten (ID: '.$item->id.', Tipe: '.$type.'): ' . $e->getMessage());

            return redirect()->route('henkaten.approval')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function revisiHenkaten(Request $request, $type, $id)
    {
        $item = $this->getHenkatenItem($type, $id);

        if (!$item) {
            return redirect()->route('henkaten.approval')->with('error', 'Data Henkaten tidak ditemukan.');
        }

        $catatanRevisi = $request->input('revision_notes');
        $item->note = $catatanRevisi;
        $item->status = 'Revisi';
        $item->save();

        return redirect()->route('henkaten.approval')->with('success', 'Henkaten ' . ucfirst($type) . ' dikirim kembali untuk revisi.');
    }

    // ==============================================================
    // BAGIAN 8: CHANGE/PERGANTIAN MANPOWER
    // ==============================================================
    public function createChange($id_manpower)
    {
        $manPower = ManPower::with('station')->findOrFail($id_manpower);

        return view('manpower.create_change', [
            'manPower' => $manPower,
        ]);
    }

    public function storeChange(Request $request)
{
    $validatedData = $request->validate([
        'line_area' => 'required|string',
        'station_id' => 'required|integer|exists:stations,id',
        'grup' => 'required|string',
        'nama_sebelum' => 'required|string',
        'nama_sesudah' => 'required|string',
        'tanggal_mulai' => 'required|date',
        'master_man_power_id' => 'required|integer|exists:man_power,id',
        'keterangan' => 'nullable|string|max:1000',
    ]);

    try {
        DB::beginTransaction();

        $today = now()->toDateString();
        $effectiveDate = $validatedData['tanggal_mulai'];

        $status = ($today >= $effectiveDate) ? 'Approved' : 'Pending';

        // Simpan log Henkaten
        $logHenkaten = new ManPowerHenkaten();
        $logHenkaten->man_power_id = $validatedData['master_man_power_id'];
        $logHenkaten->line_area = $validatedData['line_area'];
        $logHenkaten->station_id = $validatedData['station_id'];
        $logHenkaten->grup = $validatedData['grup'];
        $logHenkaten->keterangan = $validatedData['keterangan'] ?? null;
        $logHenkaten->nama = $validatedData['nama_sebelum'];
        $logHenkaten->nama_after = $validatedData['nama_sesudah'];
        $logHenkaten->effective_date = $effectiveDate;
        $logHenkaten->status = $status; 
        $logHenkaten->save();

        // ✅ TAMBAHKAN INI: Update tabel man_power jika sudah effective
        if ($status === 'Approved') {
            $manPower = ManPower::findOrFail($validatedData['master_man_power_id']);
            $manPower->nama = $validatedData['nama_sesudah'];
            $manPower->station_id = $validatedData['station_id'];
            $manPower->line_area = $validatedData['line_area'];
            $manPower->grup = $validatedData['grup'];
            $manPower->save();
        }

        DB::commit();

        return redirect()->route('manpower.index')
            ->with('success', 'Perubahan berhasil disimpan. Operator baru akan aktif pada tanggal efektif.');
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Gagal menyimpan Henkaten: ' . $e->getMessage());

        return redirect()->back()
            ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')
            ->withInput();
    }
}






public function checkAfter(Request $request)
{
    try {
        // ✅ Validasi input dengan Laravel validator
        $validated = $request->validate([
            'man_power_id_after' => 'required|integer',
            'shift' => 'required|string',
            'grup' => 'required|string|in:A,B',
            'effective_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:effective_date',
            'ignore_log_id' => 'nullable|integer',
        ]);

        // ✅ Cek apakah man_power_id_after ada di database
        $manPowerExists = ManPower::where('id', $validated['man_power_id_after'])->exists();
        
        if (!$manPowerExists) {
            return response()->json([
                'exists' => false,
                'error' => 'Man Power tidak ditemukan'
            ], 404);
        }

        // ✅ Cek konflik jadwal
        $query = ManPowerHenkaten::where('man_power_id_after', $validated['man_power_id_after'])
            ->where('shift', $validated['shift'])
            ->where('grup', $validated['grup'])
            ->where(function ($q) use ($validated) {
                // Cek overlap tanggal
                $q->where(function ($query) use ($validated) {
                    // Case 1: effective_date baru berada di antara range existing
                    $query->whereBetween('effective_date', [
                        $validated['effective_date'], 
                        $validated['end_date']
                    ]);
                })
                ->orWhere(function ($query) use ($validated) {
                    // Case 2: end_date baru berada di antara range existing
                    $query->whereBetween('end_date', [
                        $validated['effective_date'], 
                        $validated['end_date']
                    ]);
                })
                ->orWhere(function ($query) use ($validated) {
                    // Case 3: range baru mencakup range existing
                    $query->where('effective_date', '>=', $validated['effective_date'])
                          ->where('end_date', '<=', $validated['end_date']);
                })
                ->orWhere(function ($query) use ($validated) {
                    // Case 4: range existing mencakup range baru
                    $query->where('effective_date', '<=', $validated['effective_date'])
                          ->where('end_date', '>=', $validated['end_date']);
                });
            });

        // ✅ Jika edit, ignore record yang sedang diedit
        if (!empty($validated['ignore_log_id'])) {
            $query->where('id', '!=', $validated['ignore_log_id']);
        }

        $exists = $query->exists();

        // ✅ Log untuk debugging (optional)
        if ($exists) {
            Log::info('Konflik jadwal Henkaten ditemukan', [
                'man_power_id_after' => $validated['man_power_id_after'],
                'shift' => $validated['shift'],
                'grup' => $validated['grup'],
                'date_range' => $validated['effective_date'] . ' - ' . $validated['end_date']
            ]);
        }

        return response()->json([
            'exists' => $exists,
            'message' => $exists 
                ? 'Karyawan ini sudah dijadwalkan pada periode tersebut' 
                : 'Jadwal tersedia'
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // ✅ Tangani validation error
        Log::warning('Validasi checkAfter gagal', [
            'errors' => $e->errors(),
            'input' => $request->all()
        ]);

        return response()->json([
            'exists' => false,
            'error' => 'Validasi gagal',
            'message' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        // ✅ Tangani error umum
        Log::error('Error checkAfter: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'input' => $request->all()
        ]);

        return response()->json([
            'exists' => false,
            'error' => 'Terjadi kesalahan server',
            'message' => config('app.debug') ? $e->getMessage() : 'Silakan coba lagi'
        ], 500);
    }
}
}