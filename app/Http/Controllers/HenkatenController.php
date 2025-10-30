<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MaterialHenkaten; 
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use App\Models\Material;     

class HenkatenController extends Controller
{
    // ==============================================================  
    // BAGIAN 1: FORM PEMBUATAN HENKATEN MAN POWER  
    // ==============================================================
    public function create()
{
    // 1. Ambil data shift & grup dari session
    $currentGroup = session('active_grup');
    $currentShift = session('active_shift', 1); // Beri default '1' jika session shift kosong

    // 2. [WAJIB] Cek jika grup belum dipilih, paksa kembali ke dashboard
    if (!$currentGroup) {
        // Ganti 'dashboard.index' jika nama route dashboard Anda berbeda
        return redirect()->route('dashboard') 
                         ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.');
    }

    // 3. Ambil line_areas (Ini dari kode Anda dan sudah benar)
    $lineAreas = Station::whereNotNull('line_area')
                         ->orderBy('line_area', 'asc')
                         ->pluck('line_area')
                         ->unique();

   

    // 5. Kirim data yang diperlukan ke view
    return view('manpower.create_henkaten', compact(
        'lineAreas', 
        'currentGroup', 
        'currentShift'
    ));
}

   public function store(Request $request)
{
    $validated = $request->validate([
        'shift'              => 'required|string',
        'man_power_id'       => 'required|integer|exists:man_power,id', // <-- 'nama' dihapus
        'man_power_id_after' => 'required|integer|exists:man_power,id', // <-- 'nama_after' dihapus
        'station_id'         => 'required|integer|exists:stations,id',
        'keterangan'         => 'nullable|string', // Sesuai dengan form, 'required' jika wajib
        'line_area'          => 'required|string',
        'effective_date'     => 'nullable|date',
        'end_date'           => 'nullable|date|after_or_equal:effective_date',
'lampiran'           => 'required|image|mimes:jpeg,png|max:2048',        
 'time_start'         => 'nullable|date_format:H:i',
        'time_end'           => 'nullable|date_format:H:i|after_or_equal:time_start',
    ]);

    try {
        DB::beginTransaction();

        // 1. Ambil data Man Power yang valid dari database
        $manPowerAsli = ManPower::find($validated['man_power_id']);
        $manPowerAfter = ManPower::find($validated['man_power_id_after']);

        // Jika karena satu dan lain hal data tidak ditemukan, batalkan.
        if (!$manPowerAsli || !$manPowerAfter) {
            throw new \Exception('Data Man Power tidak ditemukan.');
        }

        // 2. Handle file upload
        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('lampiran_henkaten', 'public');
        }

        // 3. Siapkan data untuk tabel log Henkaten
        $dataToCreate = $validated;
        $dataToCreate['lampiran'] = $lampiranPath;
        $dataToCreate['nama'] = $manPowerAsli->nama; // <-- Ambil nama asli dari DB
        $dataToCreate['nama_after'] = $manPowerAfter->nama; // <-- Ambil nama asli dari DB
        
        // Buat log henkaten
        ManPowerHenkaten::create($dataToCreate);

        // 4. Update Man Power Asli (yang diganti)
        // Set status dan lepas dari station
        $manPowerAsli->status = 'henkaten';
        $manPowerAsli->station_id = null; // <-- Asumsi: 'null' berarti tidak ditugaskan
        $manPowerAsli->save();

        // 5. Update Man Power After (pengganti)
        // Set status dan tugaskan ke station yang baru
        $manPowerAfter->status = 'aktif';
        $manPowerAfter->station_id = $validated['station_id']; // <-- Tugaskan ke station
        $manPowerAfter->save();

        DB::commit();

        return redirect()->route('henkaten.create')
            ->with('success', 'Data Henkaten berhasil dibuat. ' . $manPowerAfter->nama . ' sekarang ditugaskan di station tersebut.');

    } catch (\Exception $e) {
        DB::rollBack();
        // Hapus file yang terlanjur di-upload jika terjadi error
        if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
            Storage::disk('public')->delete($lampiranPath);
        }
        return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
    }
}

    // ==============================================================  
    // BAGIAN 2: START PAGE HENKATEN MAN POWER  
    // ==============================================================
    public function showStartPage()
    {
        $pendingHenkatens = ManPowerHenkaten::with('station')
            ->whereNull('serial_number_start')
            ->latest()
            ->get();

        return view('manpower.create_henkaten_start', [
            'henkatens' => $pendingHenkatens,
        ]);
    }

    public function updateStartData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'updates'=> 'required|array',
            'updates.*.serial_number_start' => 'nullable|string|max:255',
            'updates.*.serial_number_end'=> 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updates = $request->input('updates', []);

        try {
            DB::transaction(function () use ($updates) {
                foreach ($updates as $id => $data) {
                    $henkaten = ManPowerHenkaten::find($id);
                    if ($henkaten && (!empty($data['serial_number_start']) || !empty($data['serial_number_end']))) {
                        $henkaten->update([
                            'serial_number_start' => $data['serial_number_start'] ?? $henkaten->serial_number_start,
                            'serial_number_end'=> $data['serial_number_end'] ?? $henkaten->serial_number_end,
                        ]);
                    }
                }
            });

            return redirect()->route('henkaten.manpower.start.page')
                ->with('success', 'Data Serial Number berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui serial number: ' . $e->getMessage()]);
        }
    }

    // ==============================================================  
    // BAGIAN 3: FORM PEMBUATAN HENKATEN METHOD  
    // ==============================================================
  public function createMethodHenkaten()
{
    // 1. BARU: Ambil data shift dari session
    $currentShift = session('active_shift', 1); // Default '1' jika session shift kosong

    // 2. Ambil line_areas (Logika Anda sudah benar)
    $lineAreas = Station::whereNotNull('line_area')
                         ->orderBy('line_area', 'asc')
                         ->pluck('line_area')
                         ->unique();

    // 3. Logika 'old' data untuk station (Logika Anda sudah benar)
    //    Ini diperlukan agar dropdown station terisi jika validasi gagal
    $stations = [];
    if ($oldLineArea = old('line_area')) {
        $stations = Station::where('line_area', $oldLineArea)->get();
    }

    // 4. Kirim semua data yang diperlukan ke view
    return view('methods.create_henkaten', compact(
        'stations',     // Untuk 'old' data Alpine
        'lineAreas',
        'currentShift'  // Kirim shift saat ini ke view
    ));
}

    public function storeMethodHenkaten(Request $request)
    {
        $validated = $request->validate([
            'shift'              => 'required|string',
        'line_area'          => 'required|string',
        'station_id'         => 'required|integer|exists:stations,id',
        'effective_date'     => 'required|date',
        'end_date'           => 'required|date|after_or_equal:effective_date',
        'time_start'         => 'required|date_format:H:i',
        'time_end'           => 'required|date_format:H:i|after_or_equal:time_start',
        'keterangan'         => 'required|string',
        'keterangan_after'   => 'required|string',
        'lampiran'           => 'required|image|mimes:jpeg,png|max:2048',
    
        ]);

        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_methods_henkaten', 'public');
            }

            $dataToCreate = $validated;
            $dataToCreate['lampiran'] = $lampiranPath;
            MethodHenkaten::create($dataToCreate);

            DB::commit();
            return redirect()->route('henkaten.method.create')
                ->with('success', 'Data Method Henkaten berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }



  


       public function showMethodStartPage(): View
    {
        // Ambil data Henkaten Method yang 'serial_number_start'-nya masih kosong
        $methodsHenkatens = MethodHenkaten::with('station')
                                ->whereNull('serial_number_start')
                                ->get();

        // Menggunakan path view yang Anda berikan:
        // 'resource/views/methods/create_henkaten_start.blade.php'
        // menjadi 'methods.create_henkaten_start'
        return view('methods.create_henkaten_start', [
            'methodsHenkatens' => $methodsHenkatens
        ]);
    }

    /**
     * INI ADALAH METHOD UNTUK MENANGANI SUBMIT FORM
     * (Dibutuhkan oleh form Anda yang memiliki action 'henkaten.method.start.update')
     */
    public function updateMethodStart(Request $request)
    {
        $updates = $request->input('updates', []);

        foreach ($updates as $id => $data) {
            // Hanya update jika serial_number_start diisi
            if (!empty($data['serial_number_start'])) {
                $henkaten = MethodHenkaten::find($id);
                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end' => $data['serial_number_end'] ?? null,
                        'status' => 'on_progress', // Anda bisa update status di sini
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Serial number Henkaten Method berhasil diupdate.');
    }

    public function showMethodActivityLog(Request $request): View
    {
        $created_date = $request->input('created_date');
        
        // Query builder
        $query = MethodHenkaten::with('station')
                                ->latest(); // Mengurutkan dari yang terbaru

        // Terapkan filter tanggal jika ada
        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        // Ambil data dengan pagination (withQueryString() dihapus)
        $logs = $query->paginate(15);

        // Kembalikan view (sesuai Blade yang Anda berikan sebelumnya)
        // (Asumsi view Anda ada di resources/views/activity_log/method.blade.php)
        return view('methods.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date
        ]);
    }




   // ==============================================================  
// BAGIAN 4: FORM PEMBUATAN HENKATEN MATERIAL  
// ==============================================================
public function createMaterialHenkaten()
{
    // 1. TAMBAHKAN INI: Ambil shift dari session
    $currentShift = session('active_shift', 1); // Default '1' jika session kosong

    $lineAreas = Station::whereNotNull('line_area')
                        ->orderBy('line_area', 'asc')
                        ->pluck('line_area')
                        ->unique();

    // Inisialisasi untuk dropdown dependent
    $stations = [];
    $materials = []; // <-- Penting untuk di-pass ke view

    // Jika ada error validasi, isi ulang dropdown berdasarkan old input
    if ($oldLineArea = old('line_area')) {
        $stations = Station::where('line_area', $oldLineArea)->get();
    }
    
    if ($oldStation = old('station_id')) {
        $materials = Material::where('station_id', $oldStation)->get(); 
    }

    // 2. TAMBAHKAN 'currentShift' DI SINI:
    return view('materials.create_henkaten', compact(
        'lineAreas', 
        'stations', 
        'materials', 
        'currentShift' // <-- Variabel yang hilang sudah ditambahkan
    ));
}
public function storeMaterialHenkaten(Request $request)
    {
        // 1. VALIDASI FINAL (Sesuai DB baru dan Form baru)
        $validatedData = $request->validate([
          'shift'              => 'required|string',
        'line_area'          => 'required|string',
        'station_id'         => 'required|integer|exists:stations,id',
        'material_id'        => 'required|integer|exists:materials,id', // Pastikan 'materials' adalah nama tabel Anda
        'effective_date'     => 'required|date',
        'end_date'           => 'required|date|after_or_equal:effective_date',
        'time_start'         => 'required|date_format:H:i',
        'time_end'           => 'required|date_format:H:i|after_or_equal:time_start',
        'description_before' => 'required|string|max:255',
        'description_after'  => 'required|string|max:255',
        'keterangan'         => 'required|string',
        'lampiran'           => 'required|image|mimes:jpeg,png|max:2048',
        'redirect_to'        => 'nullable|string' // Ini 'nullable' karena hidden input
    ]);


        try {
            DB::beginTransaction();

            // 2. LOGIKA LAMA (Mencari material_name) SUDAH DIHAPUS

            // 3. Simpan lampiran jika ada
            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_materials_henkaten', 'public');
            }

            // 4. Buat data baru untuk disimpan
            $dataToCreate = $validatedData; 
            $dataToCreate['lampiran'] = $lampiranPath;
            
            // $dataToCreate sudah berisi semua kolom yang divalidasi
            // (material_id, description_before, description_after, dll)
            MaterialHenkaten::create($dataToCreate);

            DB::commit();

            // Arahkan ke route 'create' lagi agar bisa input baru
            return redirect()->route('henkaten.material.create') 
                ->with('success', 'Data Material Henkaten berhasil disimpan!');
                
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // ==============================================================  
    // BAGIAN 5: API BANTUAN  
    // ==============================================================
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
        $request->validate(['line_area' => 'required|string']);
        $stations = Station::where('line_area', $request->line_area)
                            ->orderBy('station_name', 'asc')
                            ->get(['id', 'station_name']);
        return response()->json($stations);
    }

    public function showMaterialStartPage(): View
    {
        // Ambil data Henkaten Material yang 'serial_number_start'-nya masih kosong
        // (Pastikan nama model 'MaterialHenkaten' sudah benar)
        $materialHenkatens = MaterialHenkaten::with('station')
                                ->whereNull('serial_number_start')
                                ->get();

        // Menggunakan path view Anda:
        // 'resource/views/material/create_henkaten_start.blade.php'
        return view('materials.create_henkaten_start', [
            'materialHenkatens' => $materialHenkatens
        ]);
    }

    public function getMaterialsByStation(Request $request)
    {
        $request->validate([
            'station_id' => 'required|integer|exists:stations,id' // Asumsi tabel 'stations'
        ]);

        // Asumsi Model Material Anda memiliki relasi atau kolom 'station_id'
        // Sesuaikan 'material_name' jika nama kolomnya berbeda
        $materials = Material::where('station_id', $request->station_id)
                            ->select('id', 'material_name') 
                            ->get();

        return response()->json($materials);
    }

    /**
     * ===================================================================
     * MATERIAL HENKATEN - UPDATE START DATA (INI UNTUK SUBMIT FORM)
     * ===================================================================
     * Menyimpan data serial number start & end dari form Henkaten Material.
     * Method ini dipanggil dari route 'henkaten.material.start.update'
     */
    public function updateMaterialStartData(Request $request)
    {
        $updates = $request->input('updates', []);

        foreach ($updates as $id => $data) {
            // Hanya update jika serial_number_start diisi
            if (!empty($data['serial_number_start'])) {
                
                // (Pastikan nama model 'MaterialHenkaten' sudah benar)
                $henkaten = MaterialHenkaten::find($id);
                
                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end' => $data['serial_number_end'] ?? null,
                        'status' => 'on_progress', // Update status jika perlu
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Serial number Henkaten Material berhasil diupdate.');
    }

    public function showMaterialActivityLog(Request $request): View
    {
        $created_date = $request->input('created_date');
        
        // Query builder (menggunakan model MaterialHenkaten)
        $query = MaterialHenkaten::with('station')
                                 ->latest(); // Mengurutkan dari yang terbaru

        // Terapkan filter tanggal jika ada
        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        // Ambil data dengan pagination (withQueryString() dihapus)
        $logs = $query->paginate(15);

        // Kembalikan view (sesuai path yang Anda tentukan)
        // 'resource/views/materials/activity-log.blade.php' -> 'materials.activity-log'
        return view('materials.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date
        ]);
    }

 public function getManPower(Request $request)
{
    // 1. Validasi input
    $data = $request->validate([
        'grup' => 'required|string',
        'line_area' => 'required|string',
        'station_id' => 'required', // Tetap 'required' (bukan 'integer')
    ]);

    // 2. Cari SATU KARYAWAN SPESIFIK berdasarkan TIGA kriteria
    $employeeToReplace = ManPower::where('line_area', $data['line_area'])
        ->where('station_id', $data['station_id'])
        ->where('grup', $data['grup']) // <-- Filter grup ditambahkan di query utama
        ->first(['id', 'nama']); // Kita hanya butuh ->first()

    // 3. Kirim respons JSON
    if ($employeeToReplace) {
        // Karyawan ditemukan (misal: Nurul Z)
        // Kirim datanya: {"id": 8, "nama": "Nurul Z"}
        return response()->json($employeeToReplace);
    }
        
    // 4. Jika tidak ada karyawan yang cocok sama sekali
    return response()->json([
        'id' => null,
        'nama' => 'Man power tidak ditemukan'
    ]);
}
}
