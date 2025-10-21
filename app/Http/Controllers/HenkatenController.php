<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MaterialHenkaten; // <-- Model baru untuk Material
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HenkatenController extends Controller
{
    // ==============================================================  
    // BAGIAN 1: FORM PEMBUATAN HENKATEN MAN POWER  
    // ==============================================================
    public function create()
    {
        $stations = Station::all();
        $lineAreas = Station::whereNotNull('line_area')
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->unique();

        $stations = [];
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }

        return view('manpower.create_henkaten', compact('stations', 'lineAreas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift'              => 'required|string',
            'nama'               => 'required|string',
            'nama_after'         => 'required|string',
            'man_power_id'       => 'required|integer|exists:man_power,id',
            'man_power_id_after' => 'required|integer|exists:man_power,id',
            'station_id'         => 'required|integer|exists:stations,id',
            'keterangan'         => 'nullable|string',
            'line_area'          => 'required|string',
            'effective_date'     => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:effective_date',
            'lampiran'           => 'nullable|image|mimes:jpeg,png|max:2048',
            'time_start'         => 'nullable|date_format:H:i',
            'time_end'           => 'nullable|date_format:H:i|after_or_equal:time_start',
        ]);

        try {
            DB::beginTransaction();

            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran_henkaten', 'public');
            }

            $dataToCreate = $validated;
            $dataToCreate['lampiran'] = $lampiranPath;
            $henkaten = ManPowerHenkaten::create($dataToCreate);

            $manPowerAsli = ManPower::find($request->man_power_id);
            if ($manPowerAsli) {
                $manPowerAsli->status = 'henkaten';
                $manPowerAsli->save();
            }

            $manPowerAfter = ManPower::find($request->man_power_id_after);
            if ($manPowerAfter) {
                $manPowerAfter->status = 'aktif';
                $manPowerAfter->save();
            }

            DB::commit();

            return redirect()->route('henkaten.create')
                ->with('success', 'Data Henkaten berhasil dibuat. Selanjutnya isi Serial Number di halaman Man Power Start.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()]);
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
        $stations = Station::all();
        $lineAreas = Station::whereNotNull('line_area')
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area')
                            ->unique();

        $stations = [];
        if ($oldLineArea = old('line_area')) {
            $stations = Station::where('line_area', $oldLineArea)->get();
        }

        return view('methods.create_henkaten', compact('stations', 'lineAreas'));
    }

    public function storeMethodHenkaten(Request $request)
    {
        $validated = $request->validate([
            'shift'            => 'required|integer',
            'keterangan'       => 'required|string|max:1000',
            'keterangan_after' => 'required|string|max:1000',
            'station_id'       => 'required|integer|exists:stations,id',
            'line_area'        => 'required|string',
            'effective_date'   => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:effective_date',
            'lampiran'         => 'nullable|image|mimes:jpeg,png|max:2048',
            'time_start'       => 'nullable|date_format:H:i',
            'time_end'         => 'nullable|date_format:H:i',
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

   // ==============================================================  
// BAGIAN 4: FORM PEMBUATAN HENKATEN MATERIAL  
// ==============================================================
public function createMaterialHenkaten()
{
    $stations = Station::all();
    $lineAreas = Station::whereNotNull('line_area')
                        ->orderBy('line_area', 'asc')
                        ->pluck('line_area')
                        ->unique();

    $stations = [];
    if ($oldLineArea = old('line_area')) {
        $stations = Station::where('line_area', $oldLineArea)->get();
    }

    return view('materials.create_henkaten', compact('stations', 'lineAreas'));
}

public function storeMaterialHenkaten(Request $request)
{
    $validated = $request->validate([
        'shift'            => 'required|integer',
        'material_name'    => 'required|string|max:255',
        'material_after'   => 'required|string|max:255',
        'keterangan'       => 'nullable|string|max:1000',
        'station_id'       => 'required|integer|exists:stations,id',
        'line_area'        => 'required|string',
        'effective_date'   => 'nullable|date',
        'end_date'         => 'nullable|date|after_or_equal:effective_date',
        'lampiran'         => 'nullable|image|mimes:jpeg,png|max:2048',
        'serial_number_start' => 'nullable|string|max:255',
        'serial_number_end'   => 'nullable|string|max:255',
        'time_start'       => 'nullable|date_format:H:i',
        'time_end'         => 'nullable|date_format:H:i',
    ]);

    try {
        DB::beginTransaction();

        // ============================================================
        // Cari ID material berdasarkan nama
        // ============================================================
        $materialBefore = DB::table('materials')
            ->where('material_name', $request->material_name)
            ->first();

        $materialAfter = DB::table('materials')
            ->where('material_name', $request->material_after)
            ->first();

        // Jika tidak ditemukan, bisa diberi fallback error
        if (!$materialBefore || !$materialAfter) {
            throw new \Exception('Nama material tidak ditemukan di tabel materials.');
        }

        // ============================================================
        // Simpan lampiran jika ada
        // ============================================================
        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('lampiran_materials_henkaten', 'public');
        }

        // ============================================================
        // Buat data baru untuk disimpan
        // ============================================================
        $dataToCreate = $validated;
        $dataToCreate['lampiran'] = $lampiranPath;
        $dataToCreate['material_id'] = $materialBefore->id;
        $dataToCreate['material_id_after'] = $materialAfter->id;

        MaterialHenkaten::create($dataToCreate);

        DB::commit();

        return redirect()->route('dashboard')
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
}
