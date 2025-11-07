<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Session;  
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon; 

 

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

        // Mengambil grup dari session (shift sudah tidak diperlukan)
$currentGroup = Session::get('active_grup');
        // HANYA cek jika Grup belum dipilih
        if (!$currentGroup) {
            return redirect()->route('dashboard')
                ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.'); // Pesan error diubah
        }

        // Kirim data ke view 'create_henkaten'
        // PENTING: $log TIDAK dikirim di sini
        return view('manpower.create_henkaten', compact(
            'lineAreas',
            'currentShift', // Mengirim shift yang baru dihitung
            'currentGroup'
        ));
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'shift'              => 'required|string',
            'grup'               => 'required|string', 
            'man_power_id'       => 'required|integer|exists:man_power,id',
            'man_power_id_after' => 'required|integer|exists:man_power,id|different:man_power_id', 
            'station_id'         => 'required|integer|exists:stations,id',
            'keterangan'         => 'required|string',
            'line_area'          => 'required|string',
            'effective_date'     => 'required|date',
            'end_date'           => 'required|date|after_or_equal:effective_date',
            'lampiran'           => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'time_start'         => 'required|date_format:H:i',
            'time_end'           => 'required|date_format:H:i|after_or_equal:time_start',
        ], [
            'man_power_id_after.different' => 'Man Power Pengganti tidak boleh sama dengan Man Power Sebelum.'
        ]);

        $lampiranPath = null; // Definisikan di luar try-catch untuk rollback

        try {
            DB::beginTransaction();

            // 1. Ambil data (Gunakan findOrFail agar otomatis error jika data tidak ada)
            $manPowerAsli = ManPower::findOrFail($validated['man_power_id']);
            $manPowerAfter = ManPower::findOrFail($validated['man_power_id_after']);
            $station = Station::findOrFail($validated['station_id']); // <-- Ambil data station untuk pesan sukses

            // 2. Handle file upload
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('henkaten_man_power_lampiran', 'public');
            }

            // 3. Siapkan data untuk tabel log Henkaten
            // (Ini sudah benar, $validated['station_id'] akan tersimpan di log)
            $dataToCreate = $validated;
            $dataToCreate['lampiran'] = $lampiranPath;
            $dataToCreate['nama'] = $manPowerAsli->nama; 
            $dataToCreate['nama_after'] = $manPowerAfter->nama;
            
            ManPowerHenkaten::create($dataToCreate);

            // ===================================================================
            // 4. Update Man Power Asli (YANG DIMINTA)
            // ===================================================================
            $manPowerAsli->status = 'henkaten';
            
            // $manPowerAsli->station_id = null; // <-- BARIS INI DINONAKTIFKAN
            // Dengan menonaktifkan baris di atas, 'station_id' Man Power Asli
            // akan tetap merujuk ke station terakhirnya.
            
            $manPowerAsli->save();
            // ===================================================================

            // 5. Update Man Power After (pengganti)
            $manPowerAfter->status = 'aktif';
            $manPowerAfter->station_id = $validated['station_id']; // <-- Tugaskan ke station
            $manPowerAfter->save();

            DB::commit();

            // REVISI PESAN SUKSES: Menggunakan variabel $station agar lebih pasti
            return redirect()->route('henkaten.create')
                ->with('success', 'Data Henkaten berhasil dibuat. ' . $manPowerAfter->nama . ' sekarang ditugaskan di ' . $station->station_name);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus file yang terlanjur di-upload jika terjadi error
            if ($lampiranPath && Storage::disk('public')->exists($lampiranPath)) {
                Storage::disk('public')->delete($lampiranPath);
            }
            
            // PENYEMPURNAAN ERROR: Beri pesan lebih jelas jika data tidak ditemukan
            if ($e instanceof ModelNotFoundException) {
                 return back()->withErrors(['error' => 'Data Man Power atau Station tidak ditemukan di database.'])->withInput();
            }
            
            // Error umum
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
                            'status' => 'on_progress' // Otomatis update status
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
            'keterangan'         => 'required|string', // 'keterangan' adalah 'before'
            'keterangan_after'   => 'required|string', // 'keterangan_after' adalah 'after'
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
             if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
                Storage::disk('public')->delete($lampiranPath);
            }
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    public function showMethodStartPage(): View
    {
        $methodsHenkatens = MethodHenkaten::with('station')
                                        ->whereNull('serial_number_start')
                                        ->get();
        return view('methods.create_henkaten_start', [
            'methodsHenkatens' => $methodsHenkatens
        ]);
    }

    public function updateMethodStart(Request $request)
    {
        $updates = $request->input('updates', []);

        foreach ($updates as $id => $data) {
            if (!empty($data['serial_number_start'])) {
                $henkaten = MethodHenkaten::find($id);
                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end' => $data['serial_number_end'] ?? null,
                        'status' => 'on_progress', 
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Serial number Henkaten Method berhasil diupdate.');
    }

    public function showMethodActivityLog(Request $request): View
    {
        $created_date = $request->input('created_date');
        
        $query = MethodHenkaten::with('station')
                                ->latest(); 

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->paginate(15);

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
        $currentShift = session('active_shift', 1); 

        $lineAreas = Station::whereNotNull('line_area')
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->unique();
        $stations = [];
        $materials = []; 

        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }
        
        if ($oldStation = old('station_id')) {
            $materials = Material::where('station_id', $oldStation)->get(); 
        }
        return view('materials.create_henkaten', compact(
            'lineAreas', 
            'stations', 
            'materials', 
            'currentShift' 
        ));
    }

    public function storeMaterialHenkaten(Request $request)
    {
        $validatedData = $request->validate([
            'shift'              => 'required|string',
            'line_area'          => 'required|string',
            'station_id'         => 'required|integer|exists:stations,id',
            'material_id'        => 'required|integer|exists:materials,id', 
            'effective_date'     => 'required|date',
            'end_date'           => 'required|date|after_or_equal:effective_date',
            'time_start'         => 'required|date_format:H:i',
            'time_end'           => 'required|date_format:H:i|after_or_equal:time_start',
            'description_before' => 'required|string|max:255',
            'description_after'  => 'required|string|max:255',
            'keterangan'         => 'required|string',
            'lampiran'           => 'required|image|mimes:jpeg,png|max:2048',
            'redirect_to'        => 'nullable|string' 
        ]);

        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_materials_henkaten', 'public');
            }

            $dataToCreate = $validatedData; 
            $dataToCreate['lampiran'] = $lampiranPath;
            
            MaterialHenkaten::create($dataToCreate);

            DB::commit();

            return redirect()->route('henkaten.material.create') 
                ->with('success', 'Data Material Henkaten berhasil disimpan!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
                Storage::disk('public')->delete($lampiranPath);
            }
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function showMaterialStartPage(): View
    {
        $materialHenkatens = MaterialHenkaten::with('station')
                                            ->whereNull('serial_number_start')
                                            ->get();

        return view('materials.create_henkaten_start', [
            'materialHenkatens' => $materialHenkatens
        ]);
    }

    public function updateMaterialStartData(Request $request)
    {
        $updates = $request->input('updates', []);

        foreach ($updates as $id => $data) {
            if (!empty($data['serial_number_start'])) {
                
                $henkaten = MaterialHenkaten::find($id);
                
                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end' => $data['serial_number_end'] ?? null,
                        'status' => 'on_progress', 
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Serial number Henkaten Material berhasil diupdate.');
    }

    public function showMaterialActivityLog(Request $request): View
    {
        $created_date = $request->input('created_date');
        
        $query = MaterialHenkaten::with('station')
                                    ->latest(); 

        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        $logs = $query->paginate(15);
        return view('materials.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date
        ]);
    }

    // ==============================================================  
    // BAGIAN 6: FORM PEMBUATAN HENKATEN MACHINE (BARU)
    // ==============================================================
    public function createMachineHenkaten()
    {
        // 1. Ambil data shift & grup dari session
        $currentGroup = session('active_grup');
        $currentShift = session('active_shift', 1);

        // 2. [WAJIB] Cek jika grup belum dipilih
        if (!$currentGroup) {
            return redirect()->route('dashboard') 
                             ->with('error', 'Silakan pilih Grup di Dashboard terlebih dahulu.');
        }

        // 3. Ambil line_areas
        $lineAreas = Station::whereNotNull('line_area')
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->unique();

        // 4. Logika 'old' data untuk station (Untuk validasi gagal)
        $stations = [];
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }

        // 5. Kirim semua data yang diperlukan ke view
        return view('machines.create_henkaten', compact(
            'lineAreas',
            'stations',     // Untuk 'old' data Alpine
            'currentGroup', 
            'currentShift'
        ));
    }

   public function storeMachineHenkaten(Request $request)
    {
        // ==========================================================
        // PERUBAHAN VALIDASI (SESUAI SQL)
        // ==========================================================
        $validated = $request->validate([
            'shift'          => 'required|string',
            // 'grup'        => 'required|string', // <-- DIHAPUS, tidak ada di tabel SQL
            'line_area'      => 'required|string',
            'station_id'     => 'required|integer|exists:stations,id',
            'category'       => 'required|string|in:Program,Machine & Jig,Equipment,Camera', // <-- Ini 'category' dari form
            'effective_date' => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:effective_date',
            'time_start'     => 'required|date_format:H:i',
            'time_end'       => 'required|date_format:H:i|after_or_equal:time_start',
            'before_value'   => 'required|string|max:255', // <-- Ini 'before_value' dari form
            'after_value'    => 'required|string|max:255', // <-- Ini 'after_value' dari form
            'keterangan'     => 'required|string',
            'lampiran'       => 'required|image|mimes:jpeg,png|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_machines_henkaten', 'public');
            }

            // ==========================================================
            // PERUBAHAN LOGIKA PENYIMPANAN (SESUAI SQL)
            // ==========================================================
            
            // 1. Salin semua data yang sudah tervalidasi
            $dataToCreate = $validated;
            
            // 2. Tambahkan path lampiran
            $dataToCreate['lampiran'] = $lampiranPath;
            
            // 3. Buat mapping dari NAMA FORM (Blade) ke NAMA KOLOM (SQL)
            $dataToCreate['machine'] = $validated['category'];
            $dataToCreate['description_before'] = $validated['before_value'];
            $dataToCreate['description_after'] = $validated['after_value'];

            // 4. Hapus key-key asli dari form yang tidak ada di tabel database
            unset($dataToCreate['category']);
            unset($dataToCreate['before_value']);
            unset($dataToCreate['after_value']);

            // 5. Simpan data (Sekarang $dataToCreate cocok dengan skema SQL)
            MachineHenkaten::create($dataToCreate);

            DB::commit();
            
            return redirect()->route('henkaten.machine.create') 
                ->with('success', 'Data Machine Henkaten berhasil dibuat.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($lampiranPath) && Storage::disk('public')->exists($lampiranPath)) {
                Storage::disk('public')->delete($lampiranPath);
            }
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function showMachineStartPage(): View
    {
        $machineHenkatens = MachineHenkaten::with('station')
                                        ->whereNull('serial_number_start')
                                        ->latest()
                                        ->get();
        return view('machines.create_henkaten_start', [
            'henkatens' => $machineHenkatens 
        ]);
    }


    public function updateMachineStartData(Request $request)
    {
        $updates = $request->input('updates', []);

        foreach ($updates as $id => $data) {
            // Hanya update jika serial_number_start diisi
            if (!empty($data['serial_number_start'])) {
                
                $henkaten = MachineHenkaten::find($id);
                
                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end' => $data['serial_number_end'] ?? null,
                        'status' => 'on_progress', // Update status jika perlu
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Serial number Henkaten Machine berhasil diupdate.');
    }


    public function showMachineActivityLog(Request $request): View
    {
        $created_date = $request->input('created_date');
        
        // Query builder (menggunakan model MachineHenkaten)
        $query = MachineHenkaten::with('station')
                                    ->latest(); // Mengurutkan dari yang terbaru

        // Terapkan filter tanggal jika ada
        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        // Ambil data dengan pagination
        $logs = $query->paginate(15);

        return view('machines.activity-log', [
            'logs' => $logs,
            'created_date' => $created_date
        ]);
    }

    // ==============================================================  
    // BAGIAN 5: API BANTUAN  
    // (Dipindahkan ke bawah agar rapi)
    // ==============================================================
   public function searchManPower(Request $request)
    {
        // 1. Validasi input
        $validator = Validator::make($request->all(), [
            // Sesuai dengan Alpine.js: 'query'
            'query' => 'required|string|min:2',
            'grup'  => 'required|string',
        ]);

        // Jika validasi gagal (misal: grup tidak terkirim), kirim error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Ambil parameter yang sudah divalidasi
        $query = $request->get('query');
        $grup = $request->get('grup');

        // 3. Sesuaikan Query Database
        $results = ManPower::where('nama', 'like', "%{$query}%")
            
            // =================================================================
            // PERUBAHAN KUNCI:
            // Menggunakan 'LIKE' dengan wildcard '%'
            // Ini akan membuat 'B' cocok dengan 'B(Troubleshooting)', dll.
            ->where('grup', 'LIKE', $grup . '%')
            // =================================================================

            // Opsional: Anda bisa aktifkan filter ini jika man power pengganti
            // harus yang berstatus 'aktif' atau 'standby'.
            // ->where('status', 'aktif') 
            // ->orWhere('status', 'standby')

            ->select('id', 'nama') // Hanya ambil kolom yang diperlukan
            ->orderBy('nama', 'asc')
            ->limit(10) // Batasi hasil untuk performa
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
        // 1. Validasi input
        $data = $request->validate([
            'grup' => 'required|string',
            'line_area' => 'required|string', // Validasi tetap ada, tapi tidak dipakai di query ManPower
            'station_id' => 'required|integer|exists:stations,id', // Validasi lebih ketat
        ]);

        // 2. Cari KARYAWAN SPESIFIK berdasarkan Kriteria UTAMA
        //    Seorang Man Power 'melekat' pada 'station_id' dan 'grup'-nya.
        //    Kita tidak perlu 'line_area' untuk query ManPower, karena station_id sudah unik.
        $employeeToReplace = ManPower::where('station_id', $data['station_id'])
            ->where('grup', $data['grup'])
            // ->where('status', 'aktif') // Opsional: jika Anda ingin memastikan hanya MP aktif
            ->first(['id', 'nama']); // Kita hanya butuh ->first()

        // 3. Kirim respons JSON
        if ($employeeToReplace) {
            return response()->json($employeeToReplace);
        }
            
        // 4. Jika tidak ada karyawan yang cocok
        return response()->json([
            'id' => null,
            'nama' => 'Man power tidak ditemukan di station ini' // Pesan lebih jelas
        ]);
    }
    // Tambahkan di HenkatenController
public function approval()
    {
        // Ambil semua data henkaten dari 4M yang statusnya 'Pending'
        // Sesuaikan nama Model dan nama status 'Pending' jika berbeda
        $manpowers = ManPowerHenkaten::where('status', 'Pending')->get();
        $machines  = MachineHenkaten::where('status', 'Pending')->get();
        $methods   = MethodHenkaten::where('status', 'Pending')->get();
        $materials = MaterialHenkaten::where('status', 'Pending')->get();

        // Kirim data ke view approval
        return view('secthead.henkaten-approval', compact(
            'manpowers', 
            'machines', 
            'methods', 
            'materials'
        ));
    }

    /**
     * [BARU] Helper function internal untuk mengambil item henkaten
     * berdasarkan tipe dan ID.
     */
    private function getHenkatenItem($type, $id)
    {
        $modelClass = null;
        
        // Tentukan Model mana yang akan di-query berdasarkan $type
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
            return null; // Tipe tidak valid
        }
        
        // Cari item berdasarkan ID
        return $modelClass::find($id);
    }

    /**
     * [BARU] Mengambil detail data Henkaten untuk modal (API).
     * Method ini dipanggil oleh route 'api/henkaten-detail/{type}/{id}'
     */
    public function getHenkatenDetail($type, $id)
{
    $modelClass = null;

    // 1. Tentukan Model Class
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

    // 2. Buat Query Builder
    $query = $modelClass::query();

    // ============================================================
    // == PERBAIKAN LOGIKA IF DIMULAI DI SINI ==
    // ============================================================

    // 3. Tambahkan Eager Loading berdasarkan tipe
    // (Gunakan 'if...elseif' agar strukturnya benar)

    if ($type === 'manpower') {
        $query->with(['station', 'manPower']);
    
    } elseif ($type === 'machine') {
        $query->with(['station']);
        
    } elseif ($type === 'material') {
        $query->with(['station', 'material']); 
        
    } elseif ($type === 'method') {
        $query->with(['station']); 
    }

    // ============================================================
    // == PERBAIKAN LOGIKA IF SELESAI ==
    // ============================================================

    // 4. Ambil data
    // (Lebih baik pakai findOrFail untuk auto-handle 404 jika ID tidak ada)
    $item = $query->find($id);
    
    // 5. Cek jika item tidak ditemukan (jika Anda tidak pakai findOrFail)
    if (!$item) {
        return response()->json(['error' => 'Data not found.'], 404);
    }

    // 6. Kembalikan data sebagai JSON (Ini sudah BENAR)
    return response()->json($item);
}
    /**
     * [BARU] Memproses aksi 'Approve' Henkaten.
     * Method ini dipanggil oleh route 'henkaten/approval/{type}/{id}/approve'
     */
    public function approveHenkaten(Request $request, $type, $id)
    {
        $item = $this->getHenkatenItem($type, $id);

        if (!$item) {
            return redirect()->route('henkaten.approval')->with('error', 'Data Henkaten tidak ditemukan.');
        }

        // Ganti 'Approved' sesuai dengan value status di database Anda
        $item->status = 'Approved'; 
        $item->save();
        
        // (Opsional) Tambahkan ke Activity Log di sini

        return redirect()->route('henkaten.approval')->with('success', 'Henkaten ' . ucfirst($type) . ' berhasil di-approve.');
    }

    /**
     * [BARU] Memproses aksi 'Revisi' Henkaten.
     * Method ini dipanggil oleh route 'henkaten/approval/{type}/{id}/revisi'
     */
   public function revisiHenkaten(Request $request, $type, $id)
{
    $item = $this->getHenkatenItem($type, $id);

    if (!$item) {
        return redirect()->route('henkaten.approval')->with('error', 'Data Henkaten tidak ditemukan.');
    }

    // --- TAMBAHAN DI SINI ---
    // 1. Ambil catatan revisi dari request
    $catatanRevisi = $request->input('revision_notes');

    // 2. Simpan catatan ke kolom 'note' (atau nama kolom Anda)
    $item->note = $catatanRevisi; 
    // --- SELESAI ---

    // Ganti 'Revisi' sesuai dengan value status di database Anda
    $item->status = 'Revisi'; 
    $item->save();

    // (Opsional) Tambahkan ke Activity Log di sini
    
    return redirect()->route('henkaten.approval')->with('success', 'Henkaten ' . ucfirst($type) . ' dikirim kembali untuk revisi.');
}

}