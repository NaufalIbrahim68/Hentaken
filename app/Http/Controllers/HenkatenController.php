<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MaterialHenkaten; 
use App\Models\MachineHenkaten; // <-- BARU: Ditambahkan
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
            'grup'               => 'required|string', // Pastikan grup ada di validasi
            'man_power_id'       => 'required|integer|exists:man_power,id',
            'man_power_id_after' => 'required|integer|exists:man_power,id',
            'station_id'         => 'required|integer|exists:stations,id',
            'keterangan'         => 'required|string', // Dibuat required sesuai form
            'line_area'          => 'required|string',
            'effective_date'     => 'required|date', // Dibuat required sesuai form
            'end_date'           => 'nullable|date|after_or_equal:effective_date',
            'lampiran'           => 'required|image|mimes:jpeg,png|max:2048',      
            'time_start'         => 'required|date_format:H:i', // Dibuat required sesuai form
            'time_end'           => 'required|date_format:H:i|after_or_equal:time_start', // Dibuat required
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
            // $manPowerAsli->station_id = null; // <-- Asumsi: 'null' berarti tidak ditugaskan
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
        $query = $request->get('query', ''); // ubah dari 'q' jadi 'query'

        if (strlen($query) < 2) {
            return response()->json([]);
        }

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
            'line_area' => 'required|string',
            'station_id' => 'required', 
        ]);

        // 2. Cari SATU KARYAWAN SPESIFIK berdasarkan TIGA kriteria
        $employeeToReplace = ManPower::where('line_area', $data['line_area'])
            ->where('station_id', $data['station_id'])
            ->where('grup', $data['grup']) // <-- Filter grup ditambahkan di query utama
            ->first(['id', 'nama']); // Kita hanya butuh ->first()

        // 3. Kirim respons JSON
        if ($employeeToReplace) {
            return response()->json($employeeToReplace);
        }
            
        // 4. Jika tidak ada karyawan yang cocok sama sekali
        return response()->json([
            'id' => null,
            'nama' => 'Man power tidak ditemukan'
        ]);
    }
}