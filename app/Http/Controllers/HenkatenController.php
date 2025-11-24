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

    // ============== ROLE: LEADER PPIC / QC ==================
  elseif (in_array($userRole, ['Leader PPIC', 'Leader QC']))
{
    // Tentukan Line Area berdasarkan role
    $fixedLineArea = ($userRole === 'Leader QC') ? 'Incoming' : 'Delivery';

    // REVISI KRUSIAL: Menggunakan JOIN untuk mengambil is_main_operator dari tabel man_powers.
    // Kita harus memastikan stasiun hanya muncul sekali, jadi gunakan DISTINCT.
    $stations = DB::table('stations')
        ->where('stations.line_area', $fixedLineArea)
        ->leftJoin('man_power', function($join) use ($fixedLineArea) {
            $join->on('stations.id', '=', 'man_power.station_id')
                 ->where('man_power.line_area', '=', $fixedLineArea);
        })
        ->select('stations.id', 'stations.station_name', 'stations.line_area', 'man_power.is_main_operator')
        ->distinct('stations.id') // Pastikan stasiun tidak ganda jika ada banyak manpower di stasiun itu
        ->orderBy('stations.station_name')
        ->get();


    $lineAreas = [$fixedLineArea]; // Hanya 1 line area yang boleh mereka lihat/input
    $roleLineArea = $fixedLineArea; // Set Line Area fixed

    // Set $showStationDropdown = true agar Alpine menampilkan dropdown Station
    $showStationDropdown = true;
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
        'lampiran'            => 'required|file|mimes:jpeg,png,zip,rar|max:2048',
        'time_start'          => 'required|date_format:H:i',
        'time_end'            => 'required|date_format:H:i|after_or_equal:time_start',
        'serial_number_start' => 'nullable|string|max:255',
        'serial_number_end'   => 'nullable|string|max:255',
    ], [
        'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.',
        'lampiran.mimes'               => 'Lampiran harus berupa jpeg, png, zip, atau rar.',
        'end_date.after_or_equal'      => 'Tanggal berakhir harus sama dengan atau setelah tanggal efektif.',
    ]);

    $lampiranPath = null;

    try {
        DB::beginTransaction();

        $manPowerAsli  = ManPower::findOrFail($validated['man_power_id']);
        $manPowerAfter = ManPower::findOrFail($validated['man_power_id_after']);

        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
        }

        $dataToCreate = $validated;
        $dataToCreate['lampiran']   = $lampiranPath;
        $dataToCreate['nama']       = $manPowerAsli->nama;
        $dataToCreate['nama_after'] = $manPowerAfter->nama;
        $dataToCreate['status']     = 'PENDING';
        $dataToCreate['created_at'] = now();
        $dataToCreate['updated_at'] = now();
        $dataToCreate['user_id']    = Auth::id();

        ManPowerHenkaten::create($dataToCreate);

        DB::commit();

        return redirect()->route('henkaten.create')
            ->with('success', 'Henkaten Man Power Berhasil Di Buat');

    } catch (\Exception $e) {
        DB::rollBack();

        if ($lampiranPath && Storage::disk('public')->exists($lampiranPath)) {
            Storage::disk('public')->delete($lampiranPath);
        }

        return back()->withErrors(['error' => 'Kesalahan: ' . $e->getMessage()])->withInput();
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

    // --- B. PENGAMBILAN DATA AWAL & METHOD LIST ---
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

$dataToValidate = $request->all();
$lineArea = $request->input('line_area');
$stationIdTarget = null;
$methodIdTarget = null;
$stationNameTarget = null;
$methodName = null;

// =========================================================
// PENCARIAN ID (Station & Method) untuk Role Predefined
// =========================================================
if ($isPredefinedRole) {
        // Ambil Method Name dari form
        $methodName = $request->input('methods_name');

        // Cek Method ID (Station ID diambil langsung dari hidden input form)
        if ($methodName) {
            $methodMaster = Method::where('methods_name', $methodName)->first();
            $methodIdTarget = $methodMaster ? $methodMaster->id : null;
        }

        // Ambil station_id langsung dari request (HARUS dikirim dari hidden input Blade)
        $stationIdTarget = $request->input('station_id');

        // Update dataToValidate HANYA untuk method_id
        $dataToValidate['method_id'] = $methodIdTarget;

        // Jika station_id dari hidden input kosong/null, ini akan memicu error validasi yang tepat
        if (!$stationIdTarget) {
            // Ini akan memastikan error "station_id is required" muncul
            // atau jika Anda ingin error custom:
            // throw ValidationException::withMessages(['station_id' => ['Station ID tidak terkirim dari form.']]);
        }    $dataToValidate['station_id'] = $stationIdTarget;
    $dataToValidate['method_id'] = $methodIdTarget;

    Log::info('Predefined role search', [
        'station_name' => $stationNameTarget,
        'station_id' => $stationIdTarget,
        'method_name' => $methodName,
        'method_id' => $methodIdTarget
    ]);
}

// =========================================================
// VALIDASI
// =========================================================
try {
    $request->merge($dataToValidate);

    $validated = $request->validate([
        'shift'               => 'required|string',
        'line_area'           => 'required|string',
        'station_id'          => 'required|integer|exists:stations,id',
        'method_id'           => 'required|integer|exists:methods,id',
        'methods_name'        => $isPredefinedRole ? 'required|string|max:255' : 'nullable',
        'effective_date'      => 'required|date',
        'end_date'            => 'required|date|after_or_equal:effective_date',
        'time_start'          => 'required|date_format:H:i',
        'time_end'            => 'required|date_format:H:i|after_or_equal:time_start',
        'keterangan'          => 'required|string',
        'keterangan_after'    => 'required|string',
        'serial_number_start' => 'nullable|string|max:100',
        'serial_number_end'   => 'nullable|string|max:100',
        'note'                => 'nullable|string',
        'lampiran'            => 'required|file|mimetypes:image/jpeg,image/png,application/zip,application/x-rar-compressed|max:2048',
    ]);

    // custom error jika ID tidak ditemukan
    if ($isPredefinedRole && (!$stationIdTarget || !$methodIdTarget)) {
        $msg = "Gagal menemukan ID: Pastikan Stasiun '{$stationNameTarget}' dan Method '{$methodName}' ada di database master.";
        throw ValidationException::withMessages(['station_id' => [$msg]]);
    }

} catch (ValidationException $e) {
    return back()->withErrors($e->errors())->withInput();
}

// =========================================================
// SIMPAN DATA
// =========================================================
try {
    DB::beginTransaction();

    $lampiranPath = null;
    if ($request->hasFile('lampiran')) {
        $lampiranPath = $request->file('lampiran')->store('lampiran_methods_henkaten', 'public');
    }

    $timeStart = Carbon::createFromFormat('H:i', $validated['time_start']);
    $timeEnd   = Carbon::createFromFormat('H:i', $validated['time_end']);

    $dataToCreate = [
        'station_id'            => $validated['station_id'],
        'method_id'             => $validated['method_id'],
        'methods_name'          => $validated['methods_name'] ?? null,
        'shift'                 => $validated['shift'],
        'line_area'             => $validated['line_area'],
        'keterangan'            => $validated['keterangan'],
        'keterangan_after'      => $validated['keterangan_after'],
        'effective_date'        => $validated['effective_date'],
        'end_date'              => $validated['end_date'],
        'time_start'            => $timeStart,
        'time_end'              => $timeEnd,
        'lampiran'              => $lampiranPath,
        'serial_number_start'   => $validated['serial_number_start'] ?? null,
        'serial_number_end'     => $validated['serial_number_end'] ?? null,
        'note'                  => $validated['note'] ?? null,
        'status'                => 'PENDING',
    ];

    MethodHenkaten::create($dataToCreate);

    DB::commit();

    return redirect()->route('henkaten.method.create')
        ->with('success', 'Data Henkaten Method berhasil dibuat.');

} catch (\Exception $e) {
    DB::rollBack();

    if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
        Storage::disk('public')->delete($lampiranPath);
    }

    Log::error('Method Henkaten store failed: ' . $e->getMessage(), [
        'exception' => $e,
        'input' => $request->all()
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

        // ✅ PERBAIKAN: Inisialisasi variabel di luar scope IF agar selalu terdefinisi untuk pesan error
        $stationIdValue = null;

        // =========================================================
        // MODIFIKASI: Pencarian ID (Station & Material) untuk Role Predefined
        // =========================================================
        if ($isPredefinedRole) {

            $lineArea = $request->input('line_area');
            $materialName = $request->input('material_name');

            // 1. Cari Material ID (Logika ini sudah benar dan spesifik per Line Area)
            $material = Material::where('material_name', $materialName)
                ->whereHas('station', function ($query) use ($lineArea) {
                    $query->where('line_area', $lineArea);
                })
                ->first();

            if ($material) {
                // ✅ Benar: Ambil Station ID dari objek Material yang ditemukan
                $stationIdValue = $material->station_id; // Diperlukan untuk merge & pesan error
                $station = Station::find($stationIdValue);

                if ($station) {
                    $request->merge([
                        'material_id' => $material->id,
                        'station_id' => $station->id
                    ]);
                } else {
                    // Fallback jika stasiun tidak ditemukan (set NULL)
                    $request->merge(['material_id' => null, 'station_id' => null]);
                }
            } else {
                // Jika material tidak ditemukan, keduanya NULL
                $request->merge(['material_id' => null, 'station_id' => null]);
            }
        }
        // =========================================================

        // 3. Validasi Data
        try {
            // Kita sudah merge material_id dan station_id di Request di blok IF di atas.

            $validationRules = [
                'shift'                  => 'required|string',
                'line_area'              => 'required|string',

                // material_id dan station_id sekarang wajib
                'station_id'             => 'required|integer|exists:stations,id',
                'material_id'            => 'required|integer|exists:materials,id',

                // material_name hanya untuk resolusi ID, tidak perlu required
                'material_name'          => 'nullable|string|max:255',

                'effective_date'         => 'required|date',
                'end_date'               => 'required|date|after_or_equal:effective_date',
                'time_start'             => 'required|date_format:H:i',
                'time_end'               => 'required|date_format:H:i|after_or_equal:time_start',
                'description_before'     => 'required|string|max:255',
                'description_after'      => 'required|string|max:255',
                'keterangan'             => 'required|string',

                'lampiran'               => (isset($log) ? 'nullable' : 'required') . '|file|mimes:jpeg,png,zip,rar|max:2048',

                'serial_number_start'    => 'nullable|string|max:255',
                'serial_number_end'      => 'nullable|string|max:255',
                'redirect_to'            => 'nullable|string'
            ];

            $customMessages = [];

            // Penanganan error kustom untuk kasus di mana ID tidak ditemukan
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

        // 4. Proses Penyimpanan Data ke Database
        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_materials_henkaten', 'public');
            }

            $dataToCreate = $validatedData;

            // Wajib: Hapus field yang tidak ada di Model/Tabel sebelum create
            unset($dataToCreate['material_name']);
            unset($dataToCreate['redirect_to']);

            $dataToCreate['lampiran'] = $lampiranPath;
            $dataToCreate['status'] = 'PENDING';
            $dataToCreate['user_id'] = Auth::id();

            MaterialHenkaten::create($dataToCreate);

            DB::commit();

            $redirectTo = $request->input('redirect_to', route('henkaten.material.create'));
            return redirect($redirectTo)
                ->with('success', 'Data Material Henkaten berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
                Storage::disk('public')->delete($lampiranPath);
            }

            Log::error('Material Henkaten store failed: ' . $e->getMessage(), ['exception' => $e, 'input' => $request->all()]);

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
    // 1. Ambil data shift & grup dari session
    $currentGroup = session('active_grup');
    $currentShift = session('active_shift', 1);

    // 2. Cek jika grup belum dipilih
    if (!$currentGroup) {
        return redirect()->route('dashboard')
            ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.');
    }

    // --- LOGIKA ROLE & LINE AREA ---
    $user = Auth::user();
    $role = $user ? $user->role : null;
    $isPredefinedRole = in_array($role, ['Leader QC', 'Leader PPIC']);

    // Tentukan Line Area berdasarkan role
    if ($role === 'Leader QC') {
        $lineAreas = collect(['Incoming']);
    } elseif ($role === 'Leader PPIC') {
        $lineAreas = collect(['Delivery']);
    } else {
        // Default: Ambil semua line_area dari database
        $lineAreas = Station::select('line_area')
            ->distinct()
            ->whereNotNull('line_area')
            ->orderBy('line_area', 'asc')
            ->pluck('line_area');
    }


    // Ambil SEMUA kategori unik yang ada di tabel 'machines'
    $allMachineCategories = Machine::select('machines_category')
        ->distinct()
        ->whereNotNull('machines_category')
        ->pluck('machines_category');

    // Inisialisasi daftar kategori yang akan digunakan
    $categoriesToUse = $allMachineCategories->toArray();

    if ($role === 'Leader QC') {
        // Leader QC (Incoming) hanya berhak melihat kategori yang spesifik (Komputer, PACO Machine)
        // Kita gunakan array_intersect untuk memfilter, memastikan kategori ini ADA di database.
        $specificQCCategories = ['Komputer', 'PACO Machine'];
        $categoriesToUse = $allMachineCategories->filter(function($item) use ($specificQCCategories) {
            return in_array($item, $specificQCCategories);
        })->toArray();

    } elseif ($role === 'Leader PPIC') {
        // Leader PPIC (Delivery) hanya berhak melihat kategori yang spesifik (Record Delivery)
        $specificPPICCategory = 'Record Delivery';
         $categoriesToUse = $allMachineCategories->filter(function($item) use ($specificPPICCategory) {
            return $item === $specificPPICCategory;
        })->toArray();

    } else {
         $categoriesToUse = ['Program', 'Machine & Jig', 'Equipment', 'Camera'];

         // Jika Anda ingin semua kategori mesin muncul untuk Operator/Role Lain:
         // $categoriesToUse = $allMachineCategories->toArray();
    }

    // --- LOGIKA 'old' data untuk station (DIPERTAHANKAN) ---
    $stations = [];
    $selectedLineArea = null;

    if ($isPredefinedRole) {
        // Jika role predefined, Line Area sudah pasti (ambil yang pertama)
        $selectedLineArea = $lineAreas->first();
        if ($selectedLineArea) {
            // Jika Line Area sudah pasti, ambil semua station di area tersebut untuk AJAX/initial load
            $stations = Station::where('line_area', $selectedLineArea)->get();
        }
    } elseif ($oldLineArea = old('line_area')) {
        // Jika mode dinamis (role lain), gunakan old('line_area')
        $selectedLineArea = $oldLineArea;
        $stations = Station::where('line_area', $oldLineArea)->get();
    }

    $machineCategories = $categoriesToUse;

    return view('machines.create_henkaten', compact(
        'lineAreas',
        'stations',
        'currentGroup',
        'currentShift',
        'isPredefinedRole',
        'selectedLineArea',
        'machineCategories'
    ));
}

   public function storeMachineHenkaten(Request $request)
    {
        // --- 1. Tentukan Role Pengguna ---
        $userRole = Auth::user()->role ?? 'operator';
        $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

        $dataToValidate = $request->all();
        $stationNameTarget = null;
        $lineArea = $request->input('line_area');

        // =========================================================
        // PENCARIAN Station ID untuk Role Predefined (Dengan Fallback yang Kuat)
        // =========================================================
        if ($isPredefinedRole) {

            // Tentukan target station_name berdasarkan Line Area (sesuai data master Anda)
            if ($lineArea === 'Delivery') {
                $stationNameTarget = 'Delivery';
            } elseif ($lineArea === 'Incoming') {
                $stationNameTarget = 'Incoming';
            }

            $stationIdTarget = null;
            $station = null;

            // 1. PENCARIAN KETAT (Line Area + Nama Stasiun)
            if ($lineArea && $stationNameTarget) {
                $station = Station::where('line_area', $lineArea) // Query biasa (sensitive/insensitive tergantung driver)
                                  ->where('station_name', $stationNameTarget)
                                  ->first();
            }

            // 2. FALLBACK: Cari ID stasiun manapun di Line Area tersebut jika pencarian ketat gagal
            if (!$station) {
                $station = Station::where('line_area', $lineArea)
                                    ->first();

                if ($station) {
                    Log::warning("Fallback used for Machine Henkaten: Defaulting to ID {$station->id} for line area {$lineArea}.");
                }
            }

            // Dapatkan ID yang ditemukan
            $stationIdTarget = $station ? $station->id : null;

            // Tambahkan ID yang ditemukan (atau null) ke data validasi
            $dataToValidate['station_id'] = $stationIdTarget;
        }
        // =========================================================

        // 2. Validasi Data
        try {
            // Gabungkan data yang sudah dicari ke Request
            $request->merge($dataToValidate);

            $validated = $request->validate([
                'shift'              => 'required|string',
                'line_area'          => 'required|string',
                'station_id'         => 'required|integer|exists:stations,id',
                'category'           => 'required|string|in:Program,Machine & Jig,Equipment,Camera,Record Delivery,Komputer,PACO Machine',                'effective_date'     => 'required|date',
                'end_date'           => 'nullable|date|after_or_equal:effective_date',
                'time_start'         => 'required|date_format:H:i',
                'time_end'           => 'required|date_format:H:i|after_or_equal:time_start',
                'before_value'       => 'required|string|max:255',
                'after_value'        => 'required|string|max:255',
                'keterangan'         => 'required|string',
                'lampiran'           => 'required|file|mimetypes:image/jpeg,image/png,application/zip,application/x-rar-compressed|max:2048',
                'serial_number_start'=> 'nullable|string|max:255',
                'serial_number_end'  => 'nullable|string|max:255',
            ]);

            // Tambahkan custom error message jika stasiun tidak ditemukan (setelah proses fallback)
            if ($isPredefinedRole && $dataToValidate['station_id'] === null) {
                $target = "Line Area '{$lineArea}' dan Nama Stasiun '{$stationNameTarget}'";
                throw ValidationException::withMessages([
                     'station_id' => ["Data master Stasiun untuk {$target} tidak ditemukan. Mohon periksa tabel stations."]
                ]);
            }

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // 3. Proses Penyimpanan Data
        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_machines_henkaten', 'public');
            }

           $dataToCreate = $validated;

// SIMPAN PATH LAMPIRAN YANG BENAR
$dataToCreate['lampiran'] = $lampiranPath;

// Mapping kolom ke nama database yang benar
$dataToCreate['machine'] = $validated['category'];
$dataToCreate['description_before'] = $validated['before_value'];
$dataToCreate['description_after'] = $validated['after_value'];

$dataToCreate['status'] = 'PENDING';
            // $dataToCreate['user_id'] = Auth::id(); // DIHAPUS karena kolom tidak ada di DB

            // Hapus key-key asli dari form yang tidak ada di tabel database
            unset($dataToCreate['category']);
            unset($dataToCreate['before_value']);
            unset($dataToCreate['after_value']);

            // PASTIKAN BARIS INI TIDAK DIKOMENTARI
            MachineHenkaten::create($dataToCreate);

            DB::commit();

            return redirect()->route('henkaten.machine.create')
                ->with('success', 'Data Henkaten Machine berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
                Storage::disk('public')->delete($lampiranPath);
            }
            // Log error untuk debugging yang lebih mudah
            Log::error('Machine Henkaten store failed: ' . $e->getMessage(), ['exception' => $e, 'input' => $request->all()]);

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data. Error: ' . $e->getMessage()])
                ->withInput();
        }
    }




    public function showMachineActivityLog(Request $request): View
    {
        $created_date = $request->input('created_date');

        $query = MachineHenkaten::with('station')->latest();

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->paginate(15);

        return view('machines.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date
        ]);
    }

    // ==============================================================
    // BAGIAN 6: API BANTUAN
    // ==============================================================
    public function searchManPower(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'grup'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = $request->get('query');
        $grup = $request->get('grup');

        $results = ManPower::where('nama', 'like', "%{$query}%")
            ->where('grup', 'LIKE', $grup . '%')
            ->select('id', 'nama')
            ->orderBy('nama', 'asc')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function getStationsByLine(Request $request)
    {
        $request->validate(['line_area' => 'required|string']);
        $stations = Station::where('line_area', $request->line_area)
                            ->orderBy('station_name', 'asc')
                            ->get(['id', 'station_name']);
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
            if ($type == 'manpower' && $item->note == 'PERMANEN') {
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
            'jenis_henkaten' => 'required|string|in:PERMANEN',
            'tanggal_mulai' => 'required|date',
            'master_man_power_id' => 'required|integer|exists:man_power,id',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Catat log Henkaten sebagai pengajuan
            $logHenkaten = new ManPowerHenkaten();

            $logHenkaten->man_power_id = $validatedData['master_man_power_id'];
            $logHenkaten->line_area = $validatedData['line_area'];
            $logHenkaten->station_id = $validatedData['station_id'];
            $logHenkaten->grup = $validatedData['grup'];
            $logHenkaten->keterangan = $validatedData['keterangan'] ?? null;
            $logHenkaten->nama = $validatedData['nama_sebelum'];
            $logHenkaten->nama_after = $validatedData['nama_sesudah'];
            $logHenkaten->effective_date = $validatedData['tanggal_mulai'];
            $logHenkaten->note = $validatedData['jenis_henkaten'];
            $logHenkaten->status = 'Approved';

            $logHenkaten->save();

            // Langsung update master manpower karena tidak ada approval
$master = ManPower::find($validatedData['master_man_power_id']);

if ($master) {
    $master->nama = $validatedData['nama_sesudah'];   // update nama
    $master->line_area = $validatedData['line_area']; // jika butuh update line
    $master->station_id = $validatedData['station_id']; // jika berubah
    $master->grup = $validatedData['grup']; // jika berubah
    $master->save();
}


            DB::commit();

            return redirect()->route('manpower.index')
->with('success', 'Perubahan Man Power berhasil disimpan dan langsung disetujui.');

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
    $manPowerIdAfter = $request->query('man_power_id_after');
    $shift = $request->query('shift');
    $grup = $request->query('grup');
    $newEffectiveDate = $request->query('effective_date');
    $newEndDate = $request->query('end_date');

    if (!$manPowerIdAfter || !$shift || !$grup || !$newEffectiveDate || !$newEndDate) {
        Log::warning('Parameter validasi Henkaten After tidak lengkap.', $request->query());
        return response()->json(['error' => 'Parameter tidak lengkap'], 400);
    }


    $exists = ManPowerHenkaten::where('man_power_id_after', $manPowerIdAfter)
        ->where('shift', $shift)
        ->where('grup', $grup)

        ->where(function ($query) use ($newEffectiveDate, $newEndDate) {
            $query->where('end_date', '>=', $newEffectiveDate)
                  ->where('effective_date', '<=', $newEndDate);
        })
        ->exists();

    return response()->json(['exists' => $exists]);
}

}
