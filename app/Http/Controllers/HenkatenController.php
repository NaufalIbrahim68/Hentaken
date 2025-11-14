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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        'lampiran'            => 'required|image|mimes:jpeg,png,jpg|max:2048',
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
            'updates' => 'required|array',
            'updates.*.serial_number_start' => 'nullable|string|max:255',
            'updates.*.serial_number_end' => 'nullable|string|max:255',
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
                            'serial_number_end' => $data['serial_number_end'] ?? $henkaten->serial_number_end,
                            'status' => 'on_progress'
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
        // Ambil data shift dari session
        $currentShift = session('active_shift', 1);

        // Ambil line_areas
        $lineAreas = Station::whereNotNull('line_area')
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->unique();

        // Logika 'old' data untuk station
        $stations = [];
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }

        return view('methods.create_henkaten', compact(
            'stations',
            'lineAreas',
            'currentShift'
        ));
    }

    public function storeMethodHenkaten(Request $request)
    {
        $validated = $request->validate([
            'shift'               => 'required|string',
            'line_area'           => 'required|string',
            'station_id'          => 'required|integer|exists:stations,id',
            'effective_date'      => 'required|date',
            'end_date'            => 'required|date|after_or_equal:effective_date',
            'time_start'          => 'required|date_format:H:i',
            'time_end'            => 'required|date_format:H:i|after_or_equal:time_start',
            'keterangan'          => 'required|string',
            'keterangan_after'    => 'required|string',
            'lampiran'            => 'required|image|mimes:jpeg,png|max:2048',
            'serial_number_start' => 'nullable|string|max:255',
            'serial_number_end'   => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_methods_henkaten', 'public');
            }

            $dataToCreate = $validated;
            $dataToCreate['lampiran'] = $lampiranPath;
            $dataToCreate['status'] = 'PENDING';

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
            'serial_number_start' =>'nullable|string|max:255',
            'serial_number_end'  => 'nullable|string|max:255',
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
            $dataToCreate['status'] = 'PENDING';

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

        // 3. Ambil line_areas
        $lineAreas = Station::whereNotNull('line_area')
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->unique();

        // 4. Logika 'old' data untuk station
        $stations = [];
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }

        return view('machines.create_henkaten', compact(
            'lineAreas',
            'stations',
            'currentGroup',
            'currentShift'
        ));
    }

    public function storeMachineHenkaten(Request $request)
    {
        $validated = $request->validate([
            'shift'          => 'required|string',
            'line_area'      => 'required|string',
            'station_id'     => 'required|integer|exists:stations,id',
            'category'       => 'required|string|in:Program,Machine & Jig,Equipment,Camera',
            'effective_date' => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:effective_date',
            'time_start'     => 'required|date_format:H:i',
            'time_end'       => 'required|date_format:H:i|after_or_equal:time_start',
            'before_value'   => 'required|string|max:255',
            'after_value'    => 'required|string|max:255',
            'keterangan'     => 'required|string',
            'lampiran'       => 'required|image|mimes:jpeg,png|max:2048',
            'serial_number_start'=> 'nullable|string|max:255',
            'serial_number_end' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_machines_henkaten', 'public');
            }

            // Buat mapping dari NAMA FORM ke NAMA KOLOM
            $dataToCreate = $validated;
            $dataToCreate['lampiran'] = $lampiranPath;
            $dataToCreate['machine'] = $validated['category'];
            $dataToCreate['description_before'] = $validated['before_value'];
            $dataToCreate['description_after'] = $validated['after_value'];
            $dataToCreate['status'] = 'PENDING';

            // Hapus key-key asli dari form yang tidak ada di tabel database
            unset($dataToCreate['category']);
            unset($dataToCreate['before_value']);
            unset($dataToCreate['after_value']);

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
            if (!empty($data['serial_number_start'])) {
                $henkaten = MachineHenkaten::find($id);

                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end' => $data['serial_number_end'] ?? null,
                        'status' => 'on_progress',
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Serial number Henkaten Machine berhasil diupdate.');
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
        \Log::warning('Parameter validasi Henkaten After tidak lengkap.', $request->query());
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