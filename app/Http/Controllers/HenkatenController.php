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
        // Mendapatkan Line Area unik dari database
        $lineAreas = Station::distinct()->pluck('line_area')->sort()->toArray();

        // --- LOGIKA SHIFT OTOMATIS (BARU) ---
        // Atur timezone ke Waktu Indonesia Barat
        $now = Carbon::now('Asia/Jakarta');

        // Tentukan jam mulai Shift 2 (07:00) dan Shift 1 (19:00)
        $shift2Start = $now->copy()->setTime(7, 0, 0);
        $shift1Start = $now->copy()->setTime(19, 0, 0);

        $currentShift = 0; // Default
        if ($now->gte($shift2Start) && $now->lt($shift1Start)) {
            // Jika jam >= 07:00 DAN < 19:00 -> Shift 2
            $currentShift = 2;
        } else {
            // Jika jam >= 19:00 ATAU < 07:00 -> Shift 1
            $currentShift = 1;
        }
        // --- AKHIR LOGIKA SHIFT OTOMATIS ---

        // Mengambil grup dari session
        $currentGroup = Session::get('active_grup');
        
        // HANYA cek jika Grup belum dipilih
        if (!$currentGroup) {
            return redirect()->route('dashboard')
                ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.');
        }

        // Kirim data ke view 'create_henkaten'
        return view('manpower.create_henkaten', compact(
            'lineAreas',
            'currentShift',
            'currentGroup'
        ));
    }

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
'lampiran' => 'required|file|mimetypes:image/jpeg,image/png,application/zip,application/x-rar-compressed|max:2048',
        'time_start'          => 'required|date_format:H:i',
        'time_end'            => 'required|date_format:H:i|after_or_equal:time_start',
        'serial_number_start' => 'nullable|string|max:255',
        'serial_number_end'   => 'nullable|string|max:255',
    ], [
        'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.'
    ]);

    $lampiranPath = null;

    try {
        DB::beginTransaction();

        // 1. Ambil data terkait
        $manPowerAsli = ManPower::findOrFail($validated['man_power_id']);
        $manPowerAfter = ManPower::findOrFail($validated['man_power_id_after']);
        $station = Station::findOrFail($validated['station_id']);

        // 2. Upload lampiran
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
        }

        // 3. Siapkan data untuk tabel log Henkaten
        $dataToCreate = $validated;
        $dataToCreate['lampiran'] = $lampiranPath;
        $dataToCreate['nama'] = $manPowerAsli->nama;
        $dataToCreate['nama_after'] = $manPowerAfter->nama;
        $dataToCreate['status'] = 'PENDING';
        $dataToCreate['created_at'] = now();  // ✅ otomatis isi created_at
        $dataToCreate['updated_at'] = now();  // ✅ otomatis isi updated_at

        // 4. Simpan ke database
        ManPowerHenkaten::create($dataToCreate);

        // 5. Update status man power asli
        $manPowerAsli->status = 'henkaten';
        $manPowerAsli->save();

        // 6. Update man power pengganti
        $manPowerAfter->status = 'aktif';
        $manPowerAfter->station_id = $validated['station_id'];
        $manPowerAfter->save();

        DB::commit();

        return redirect()->route('henkaten.create')
            ->with('success', 'Data Henkaten berhasil dibuat. ' . $manPowerAfter->nama . ' sekarang ditugaskan di ' . $station->station_name);

    } catch (\Exception $e) {
        DB::rollBack();

        if ($lampiranPath && Storage::disk('public')->exists($lampiranPath)) {
            Storage::disk('public')->delete($lampiranPath);
        }

        if ($e instanceof ModelNotFoundException) {
            return back()->withErrors(['error' => 'Data Man Power atau Station tidak ditemukan di database.'])->withInput();
        }

        return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
    }
}

   

   

    // ==============================================================
    // BAGIAN 3: FORM PEMBUATAN HENKATEN METHOD
    // ==============================================================
    public function createMethodHenkaten()
{
    // Ambil data shift dari session
    $currentShift = session('active_shift', 1);

    $user = Auth::user();
    $role = $user ? $user->role : null;
    $isPredefinedRole = in_array($role, ['Leader QC', 'Leader PPIC']);

    // --- INISIALISASI VARIABEL BARU (PENTING!) ---
    $selectedLineArea = null;
    $predefinedLineArea = null; // <-- INISIALISASI
    $methodList = collect();
    $stations = collect(); // Pastikan stations diinisialisasi sebagai koleksi kosong
    // ---------------------------------------------

    // --- A. LOGIKA PENENTUAN LINE AREA ---
    if ($role === 'Leader QC') {
        $lineAreas = collect(['Incoming']);
        $predefinedLineArea = 'Incoming'; // Set nilai
    } elseif ($role === 'Leader PPIC') {
        $lineAreas = collect(['Delivery']);
        $predefinedLineArea = 'Delivery'; // Set nilai
    } else {
        // Default: Ambil semua line_area dari database
        $lineAreas = Station::select('line_area')
            ->distinct()
            ->whereNotNull('line_area')
            ->orderBy('line_area', 'asc')
            ->pluck('line_area');
    }

    // --- B. PENGAMBILAN DATA AWAL & OLD VALUE ---
    if ($isPredefinedRole) {
        // Mode Predefined: Line Area sudah pasti
        $selectedLineArea = $predefinedLineArea; // Menggunakan variabel yang sudah diinisialisasi dan diisi
        
        // Ambil daftar method name unik di line area tersebut
        $methodList = Method::whereHas('station', function ($query) use ($selectedLineArea) {
                             $query->where('line_area', $selectedLineArea);
                         })
                         ->select('methods_name')
                         ->distinct()
                         ->orderBy('methods_name')
                         ->pluck('methods_name');
        
        // Ambil semua station di line area tersebut
        $stations = Station::where('line_area', $selectedLineArea)->get();

    } elseif ($oldLineArea = old('line_area')) {
        // Mode Dynamic (Ada old value): Ambil stations dan methods sesuai old value
        $selectedLineArea = $oldLineArea;
        $stations = Station::where('line_area', $oldLineArea)->get();
        
        // Jika ada old station ID, ambil method list-nya
        if ($oldStationId = old('station_id')) {
             $methodList = Method::where('station_id', $oldStationId)
                                 ->select('id', 'methods_name')
                                 ->get();
        }
    }


    return view('methods.create_henkaten', compact(
        'stations',
        'lineAreas',
        'currentShift',
        
        // --- VARIABEL UNTUK KONTROL BLADE ---
        'isPredefinedRole',
        'predefinedLineArea', // Sekarang variabel ini pasti terdefinisi
        'selectedLineArea',
        'methodList'
    ));
}

 public function storeMethodHenkaten(Request $request)
{
    // --- 1. Tentukan Role Pengguna ---
    $userRole = Auth::user()->role ?? 'operator';
    $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

    $dataToValidate = $request->all();
    $lineArea = $request->input('line_area');
    $stationIdTarget = null;
    $methodIdTarget = null;
    $stationNameTarget = null;

    // =========================================================
    // PENCARIAN ID (Station & Method) untuk Role Predefined
    // =========================================================
    if ($isPredefinedRole) {

        $stationNameTarget = $request->input('station_name_predefined');
        $methodName = $request->input('methods_name');

        // 1. CARI METHOD ID
        $methodMaster = Method::where('methods_name', $methodName)->first();
        if ($methodMaster) {
            $methodIdTarget = $methodMaster->id;
        }

        // 2. CARI STATION ID
        if ($lineArea && $stationNameTarget) {

            // PENCARIAN KETAT
            $station = Station::whereRaw('LOWER(line_area) = ?', [Str::lower($lineArea)])
                ->whereRaw('LOWER(station_name) = ?', [Str::lower($stationNameTarget)])
                ->first();

            // FALLBACK
            if (!$station) {
                $station = Station::whereRaw('LOWER(line_area) = ?', [Str::lower($lineArea)])
                    ->first();
                if ($station) {
                    Log::warning("Method Henkaten: Fallback Station used for {$lineArea}.");
                }
            }

            $stationIdTarget = $station ? $station->id : null;
        }

        // PASANG ID
        $dataToValidate['station_id'] = $stationIdTarget;
        $dataToValidate['method_id'] = $methodIdTarget;
    }

    // =========================================================
    // 2. VALIDASI
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

        // Custom error jika ID tidak ditemukan
        if ($isPredefinedRole && ($dataToValidate['station_id'] === null || $dataToValidate['method_id'] === null)) {
            $msg = "Gagal menemukan ID: Pastikan Stasiun '{$stationNameTarget}' dan Method '{$methodName}' ada di database master.";
            throw ValidationException::withMessages(['station_id' => [$msg]]);
        }

    } catch (ValidationException $e) {
        return back()->withErrors($e->errors())->withInput();
    }

    // =========================================================
    // 3. PROSES SIMPAN DATA
    // =========================================================
    try {
        DB::beginTransaction();

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('lampiran_methods_henkaten', 'public');
        }

        // KONVERSI WAKTU KE DATETIME SQL SERVER
        $timeStart = Carbon::createFromFormat('H:i', $validated['time_start']);
        $timeEnd   = Carbon::createFromFormat('H:i', $validated['time_end']);

        // =========================================================
        // DATA YANG BENAR-BENAR SESUAI DENGAN TABEL
        // =========================================================
        $dataToCreate = [
            'station_id'            => $validated['station_id'],
            'method_id'             => $validated['method_id'],
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

        // SIMPAN
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
            'input'     => $request->all()
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
    $materials = collect(); // Untuk mode dinamis (operator)
    $materialsForPredefinedRole = collect(); // Untuk mode Leader QC/PPIC

    // 3. Logika mengisi data lama (old) untuk mode dinamis
    if (!$isPredefinedRole) {
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }
        if ($oldStation = old('station_id')) {
            // Ambil material untuk stasiun yang dipilih (mode dinamis)
            $materials = Material::where('station_id', $oldStation)->get();
        }
    }
    
    // 4. LOGIKA KHUSUS untuk Leader QC/PPIC (Mode Predefined)
    if ($isPredefinedRole && $predefinedLineArea) {
        
        // REVISI INI: Hapus klausa where('line_area', $predefinedLineArea) yang menyebabkan error.
        // Cukup gunakan whereHas('station') untuk mencari material yang stasiunnya berada di line area yang sesuai.
        $materialsForPredefinedRole = Material::whereHas('station', function ($query) use ($predefinedLineArea) {
                                            $query->where('line_area', $predefinedLineArea); 
                                        })
                                        ->select('id', 'material_name') // Ambil ID dan Nama
                                        ->get();
    }
    
    return view('materials.create_henkaten', compact(
        'lineAreas',
        'stations',
        'materials',
        'currentShift',
        'userRole', 
        'isPredefinedRole',
        'predefinedLineArea',
        'materialsForPredefinedRole' 
    ));
}

  public function storeMaterialHenkaten(Request $request)
{
    // 1. Tentukan Role Pengguna
    $userRole = Auth::user()->role ?? 'operator'; 
    $isPredefinedRole = ($userRole === 'Leader PPIC' || $userRole === 'Leader QC');
    
    $dataToValidate = $request->all();

    // =========================================================
    // MODIFIKASI: Pencarian ID (Station & Material) untuk Role Predefined
    // =========================================================
    if ($isPredefinedRole) {
        
        $lineArea = $request->input('line_area'); // 'Incoming' atau 'Delivery'
        $materialName = $request->input('material_name'); 
        
        // 1. Cari Material ID
        // Material::where('material_name', $materialName)->first()
        // PENTING: Jika material_name bisa sama di Line Area yang berbeda, Anda mungkin perlu membatasi pencarian.
        // Tapi berdasarkan data statis di Blade sebelumnya, diasumsikan nama ini unik per Line Area atau Material Henkaten
        $material = Material::where('material_name', $materialName)->first();

        if ($material) { 
            $dataToValidate['material_id'] = $material->id; 
        } else {
            // Jika Material tidak ditemukan, set ke null agar validasi 'required' Material ID gagal dengan pesan yang jelas.
            $dataToValidate['material_id'] = null; 
        }

        // 2. Cari Station ID
        // Mencari Station berdasarkan Line Area DAN Station Name (yang disetel sama dengan Line Area di data master)
        $station = Station::where('line_area', $lineArea)
                          ->where('station_name', $lineArea) 
                          ->first(); 
        
        // JIKA ANDA HANYA MENGINGINKAN SATU ID TERTENTU (misal ID 143 untuk Incoming):
        // $station = Station::where('id', ($lineArea === 'Incoming' ? 143 : 1150))->first();

        if ($station) {
            $dataToValidate['station_id'] = $station->id;
        } else {
            // Jika stasiun tidak ditemukan, set ke null.
            $dataToValidate['station_id'] = null;
        }
    }
    // =========================================================

    // 3. Validasi Data
    try {
        // Merge data yang baru ditemukan (material_id & station_id) ke dalam Request
        $request->merge($dataToValidate); 

        $validationRules = [
            'shift'               => 'required|string',
            'line_area'           => 'required|string',
            
            // karena kita sudah memastikan nilainya diisi di blok IF di atas
            'station_id'          => 'required|integer|exists:stations,id', 
            'material_id'         => 'required|integer|exists:materials,id', 
            
            // material_name hanya wajib jika role predefined (tapi tidak disimpan di DB)
            'material_name'       => $isPredefinedRole ? 'required|string|max:255' : 'nullable', 
            
            'effective_date'      => 'required|date',
            'end_date'            => 'required|date|after_or_equal:effective_date', 
            'time_start'          => 'required|date_format:H:i',
            'time_end'            => 'required|date_format:H:i|after_or_equal:time_start', 
            'description_before'  => 'required|string|max:255',
            'description_after'   => 'required|string|max:255',
            'keterangan'          => 'required|string',
            
            // Sesuaikan aturan file, perhatikan mimetypes (zip/rar mungkin bermasalah)
            'lampiran'            => (isset($log) ? 'nullable' : 'required') . '|file|mimes:jpeg,png,zip,rar|max:2048', 
            
            'serial_number_start' => 'nullable|string|max:255',
            'serial_number_end'   => 'nullable|string|max:255',
            'redirect_to'         => 'nullable|string'
        ];
        
        $customMessages = [];

        // Penanganan error kustom untuk kasus di mana ID tidak ditemukan (walaupun input ada)
        if ($isPredefinedRole) {
            if ($dataToValidate['station_id'] === null) {
                // Menimpa pesan 'station_id.required' jika master data tidak ada
                 $customMessages['station_id.required'] = "Data master Stasiun untuk '{$lineArea}' tidak ditemukan. Mohon periksa tabel stations Anda.";
            }
            if ($dataToValidate['material_id'] === null) {
                // Menimpa pesan 'material_id.required' jika master data tidak ada
                 $customMessages['material_id.required'] = "Data master Material '{$materialName}' tidak ditemukan. Mohon periksa tabel materials Anda.";
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
        
        // Hapus field yang tidak ada di Model/Tabel sebelum create
        unset($dataToCreate['material_name']);
        unset($dataToCreate['redirect_to']); // Tambahkan penghapusan ini agar tidak error di Model::create
        
        $dataToCreate['lampiran'] = $lampiranPath;
        $dataToCreate['status'] = 'PENDING';
        $dataToCreate['user_id'] = Auth::id();

        MaterialHenkaten::create($dataToCreate);

        DB::commit();

        // Gunakan redirect_to jika tersedia
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
            ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])
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

    // Tentukan Kategori Mesin
    if ($isPredefinedRole) {
        // Untuk Leader QC/PPIC: Ambil kategori unik dari database
        $machineCategories = Machine::select('machines_category')
            ->distinct()
            ->whereNotNull('machines_category')
            ->pluck('machines_category')
            ->toArray();
    } else {
        // Untuk role lain: Gunakan daftar statis (atau yang didefinisikan secara default)
        $machineCategories = ['Program', 'Machine & Jig', 'Equipment', 'Camera'];
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

    return view('machines.create_henkaten', compact(
        'lineAreas',
        'stations',
        'currentGroup',
        'currentShift',
        'machineCategories', // <--- VARIABLE BARU
        'isPredefinedRole', // <--- VARIABLE BARU
        'selectedLineArea' // <--- VARIABLE BARU
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
            $logHenkaten->status = 'PENDING';
            
            $logHenkaten->save();

            DB::commit();

            return redirect()->route('manpower.index')
                             ->with('success', 'Pengajuan perubahan Man Power telah berhasil dicatat dan menunggu approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal menyimpan Henkaten: ' . $e->getMessage());
            
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')
                             ->withInput();
        }
    }

    // HenkatenController.php
public function checkAfter(Request $request)
{
    // Ambil semua parameter yang diperlukan dari request
    $manPowerIdAfter = $request->query('man_power_id_after');
    $shift = $request->query('shift');
    $grup = $request->query('grup'); // Parameter baru
    $newEffectiveDate = $request->query('effective_date');
    $newEndDate = $request->query('end_date'); // Parameter baru

    if (!$manPowerIdAfter || !$shift || !$grup || !$newEffectiveDate || !$newEndDate) {
        // Tambahkan logging jika ada parameter yang hilang
        Log::warning('Parameter validasi Henkaten After tidak lengkap.', $request->query());
        return response()->json(['error' => 'Parameter tidak lengkap'], 400);
    }
    
    // --- Logika Deteksi Konflik (Date Range Overlap) ---
    // Logika ini memeriksa apakah ada log yang sudah ada
    // yang rentang waktunya (effective_date s/d end_date) 
    // berpotongan dengan rentang waktu yang baru (newEffectiveDate s/d newEndDate).
    
    $exists = ManPowerHenkaten::where('man_power_id_after', $manPowerIdAfter)
        ->where('shift', $shift)
        ->where('grup', $grup)
        
        // Memeriksa Overlap: Rentang lama berakhir SETELAH rentang baru dimulai 
        // DAN rentang lama dimulai SEBELUM rentang baru berakhir.
        // Dengan kata lain: NOT (Lama Berakhir < Baru Mulai OR Lama Mulai > Baru Berakhir)
        ->where(function ($query) use ($newEffectiveDate, $newEndDate) {
            $query->where('end_date', '>=', $newEffectiveDate)
                  ->where('effective_date', '<=', $newEndDate);
        })
        ->exists();

    return response()->json(['exists' => $exists]);
}

}