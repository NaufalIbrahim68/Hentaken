<?php

namespace App\Http\Controllers;

use App\Models\ManPower;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten; 
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
        return view('manpower.create_henkaten', compact('stations','lineAreas'));
    }

    public function store(Request $request)
    {
        // ... (Tidak ada perubahan di sini)
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
    // BAGIAN 2: HALAMAN START HENKATEN MAN POWER
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
        // ... (Tidak ada perubahan di sini)
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
        // ... (Tidak ada perubahan di sini)
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
        // ... (Tidak ada perubahan di sini)
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
            return redirect()->route('henkaten.methods.create')
                ->with('success', 'Data Method Henkaten berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    // ==============================================================
    // BAGIAN 4: HALAMAN START HENKATEN METHOD (INI YANG DIPERBAIKI)
    // ==============================================================

    /**
     * NAMA METHOD DIUBAH: dari createStartForm menjadi showMethodStartPage
     */
    public function showMethodStartPage()
    {
        // Ambil data henkaten method yang relevan
        $methodsHenkatens = MethodHenkaten::whereNull('serial_number_start')
                                         ->with('station')
                                         ->latest() // Menampilkan yang terbaru di atas
                                         ->get();

        // Tampilkan view dan kirim datanya
        return view('methods.create_henkaten_start', compact('methodsHenkatens'));
    }

    /**
     * NAMA METHOD DIUBAH: dari updateStart menjadi updateMethodStartData agar konsisten
     */
    public function updateMethodStartData(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
        ]);

        foreach ($request->updates as $id => $data) {
            if (!empty($data['serial_number_start']) || !empty($data['serial_number_end'])) {
                $henkaten = MethodHenkaten::find($id);
                if ($henkaten) {
                    $henkaten->update([
                        'serial_number_start' => $data['serial_number_start'],
                        'serial_number_end'   => $data['serial_number_end'],
                        'time_start'          => now(),
                    ]);
                }
            }
        }
        return back()->with('success', 'Data Serial Number Henkaten Method berhasil disimpan!');
    }

    // ==============================================================
    // BAGIAN 5: FUNGSI BANTUAN (API/AJAX)
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

    public function showMethodActivityLog(Request $request)
    {
        // Ambil tanggal filter dari request
        $created_date = $request->input('created_date');

        // Mulai query ke model MethodHenkaten (sudah di-import di atas)
        // Gunakan eager loading 'station'
        $query = MethodHenkaten::with('station'); 

        // Jika ada filter tanggal, terapkan
        if ($created_date) {
            $query->whereDate('created_at', $created_date);
        }

        // Ambil data, urutkan dari yang terbaru, dan paginasi
        // 'withQueryString()' akan otomatis menambahkan parameter filter (created_date)
        // ke link pagination
        $logs = $query->latest()->paginate(10)->appends($request->query());

        // Kirim data ke view
        // Pastikan 'activity_log.method' adalah path view blade Anda
       return view('methods.activity-log', compact('logs', 'created_date'));
    }
    

}
