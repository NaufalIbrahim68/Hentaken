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

class ManPowerController extends Controller
{
    // ==============================================================
    // INDEX: Tampilkan daftar Man Power
    // ==============================================================
    public function index()
    {
        $man_powers = ManPower::with('station')
            ->orderBy('nama', 'asc')
            ->paginate(5);

        return view('manpower.index', compact('man_powers'));
    }

    // ==============================================================
    // CREATE MASTER MANPOWER
    // ==============================================================
    public function create()
    {
        $lineAreas = Station::select('line_area')->distinct()->pluck('line_area');
        $stations = Station::all();

        return view('manpower.create', compact('lineAreas', 'stations'));
    }

    // ==============================================================
    // GET STATIONS BY LINE AREA (AJAX) — dipakai juga untuk modal
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
    // STORE MASTER MANPOWER
    // ==============================================================
    public function storeMaster(Request $request)
{
    $request->validate([
        'nama' => 'required|string|max:255',
        'station_id' => 'nullable|exists:stations,id',
        'shift' => 'nullable|in:1,2',
        'grup' => 'required|string', // bisa A, B, A(Troubleshooting), B(Troubleshooting)
    ]);

    $data = $request->only(['nama', 'station_id', 'shift', 'grup']);

    if (str_contains($data['grup'], 'Troubleshooting')) {
        // Ambil hanya A atau B untuk tabel troubleshooting
        $grupTs = substr($data['grup'], 0, 1);

        // Simpan ke troubleshooting
        $troubleshooting = Troubleshooting::create([
            'nama' => $data['nama'],
            'grup' => $grupTs,
            'status' => 'normal',
        ]);

        // Simpan juga ke master manpower
        ManPower::create([
            'nama' => $data['nama'],
            'grup' => $data['grup'], // lengkap, seperti A(Troubleshooting)
            'station_id' => $data['station_id'] ?? null,
            'shift' => $data['shift'] ?? null,
            'line_area' => null,
            'status' => 'normal',
            'troubleshooting_id' => $troubleshooting->id,
        ]);

    } else {
        // Masuk ke manpower biasa
        ManPower::create([
            'nama' => $data['nama'],
            'grup' => $data['grup'],
            'station_id' => $data['station_id'],
            'shift' => $data['shift'],
            'line_area' => null,
            'status' => 'normal',
        ]);
    }

    return redirect()->route('manpower.index')
                     ->with('success', 'Data Man Power berhasil ditambahkan.');
}

    // ==============================================================
    // EDIT MASTER MANPOWER
    // ==============================================================
    public function edit($id)
    {
        $man_power = ManPower::findOrFail($id);
        $lineAreas = Station::select('line_area')->distinct()->pluck('line_area');
        $stations = Station::all();

        return view('manpower.edit_master', compact('man_power', 'lineAreas', 'stations'));
    }

    // ==============================================================
    // UPDATE MASTER MANPOWER
    // ==============================================================
    public function updateMaster(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'station_id' => 'required|exists:stations,id',
            'shift' => 'required|in:1,2',
            'grup' => 'required|in:A,B',
        ]);

        $man_power = ManPower::findOrFail($id);
        $man_power->update($validatedData);

        return redirect()->route('manpower.index')
            ->with('success', 'Data Man Power berhasil diperbarui.');
    }

    // ==============================================================
    // DESTROY MASTER MANPOWER
    // ==============================================================
    public function destroyMaster($id)
    {
        $man_power = ManPower::findOrFail($id);
        $man_power->delete();

        return redirect()->route('manpower.index')
            ->with('success', 'Data Man Power berhasil dihapus.');
    }

    // ==============================================================
    // CREATE HENKATEN FORM
    // ==============================================================
    public function createHenkatenForm()
    {
        $man_power = ManPower::with('station')->first();
        $stations = Station::all();

        return view('manpower.create_henkaten', compact('man_power', 'stations'));
    }

    public function createHenkaten($id)
    {
        $man_power = ManPower::findOrFail($id);
        $stations = Station::all();
        $lineAreas = Station::whereNotNull('line_area')
            ->orderBy('line_area', 'asc')
            ->pluck('line_area')
            ->unique();

        return view('manpower.create_henkaten', compact('man_power', 'stations', 'lineAreas'));
    }

    // ==============================================================
    // STORE HENKATEN DATA
    // ==============================================================
    public function storeHenkaten(Request $request)
    {
        $validated = $request->validate([
            'shift' => 'required|in:1,2',
            'line_area' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'man_power_id' => 'required|exists:man_power,id',
            'man_power_id_after' => 'required|exists:man_power,id',
            'keterangan' => 'nullable|string',
            'lampiran' => 'nullable|image|mimes:jpeg,png|max:2048',
            'nama' => 'required|string|max:255',
            'nama_after' => 'required|string|max:255',
        ]);

        $lampiranPath = $request->hasFile('lampiran')
            ? $request->file('lampiran')->store('lampiran_henkaten', 'public')
            : null;

        ManPowerHenkaten::create([
            ...$validated,
            'lampiran' => $lampiranPath,
        ]);

        return redirect()->route('manpower.index')
            ->with('success', 'Data Henkaten berhasil disimpan.');
    }

    // ==============================================================
    // SCHEDULER VIEW
    // ==============================================================
    public function createManpowerScheduler(Request $request): View
    {
        return view('manpower.schedulers');
    }

    // ==============================================================
    // STATION MANAGEMENT (MODAL)
    // ==============================================================
    public function storeStation(Request $request)
    {
        $request->validate([
            'line_area' => 'required|string|max:255',
            'station_name' => 'required|string|max:255',
        ]);

        $station = Station::create([
            'line_area' => $request->line_area,
            'station_name' => $request->station_name,
        ]);

        return response()->json($station);
    }

    public function destroyStation($id)
    {
        $station = Station::findOrFail($id);
        $station->delete();

        return response()->noContent();
    }

    public function updateStation(Request $request, $id)
{
    $request->validate(['station_name' => 'required|string|max:255']);

    $station = Station::findOrFail($id);
    $station->update(['station_name' => $request->station_name]);

    return response()->json($station);
}

public function search(Request $request)
{
    $request->validate([
        'q' => 'nullable|string',
        'grup' => 'required|string', // misal A, B, A(Troubleshooting), B(Troubleshooting)
    ]);

    $q = $request->input('q', '');
    $grupInput = $request->input('grup');

    // Tentukan grup untuk query troubleshooting
    $grupTs = str_contains($grupInput, 'Troubleshooting') ? substr($grupInput, 0, 1) : $grupInput;

    // Cari di manpower normal
    $manPower = ManPower::query()
        ->where('nama', 'like', "%$q%")
        ->where('grup', $grupInput)
        ->get(['id', 'nama', 'grup']);

    // Cari di troubleshooting
    $troubleshooting = Troubleshooting::query()
        ->where('nama', 'like', "%$q%")
        ->where('grup', $grupTs)
        ->get(['id', 'nama', 'grup']);

    // Gabungkan hasil
    $resultManpower = $manPower->map(fn($item) => [
        'id' => $item->id,
        'nama' => $item->nama,
        'grup' => $item->grup,
    ]);

    $resultTroubleshooting = $troubleshooting->map(fn($item) => [
        'id' => 't-' . $item->id,
        'nama' => $item->nama . ' (TS)',
        'grup' => $item->grup,
    ]);

    $result = $resultManpower->merge($resultTroubleshooting)
                             ->unique('nama')
                             ->values();

    return response()->json($result);
}

public function getManPower(Request $request)
{
    $grup = $request->input('grup');
    $line_area = $request->input('line_area');
    $station_id = $request->input('station_id');

    $result = [];

    // --- 1. Ambil Man Power biasa yang station_id sama dan line_area sama ---
    if ($station_id && $line_area) {
        $manPowerQuery = ManPower::where('grup', $grup)
            ->where('station_id', $station_id)
            ->where('line_area', $line_area);

        // Ambil semua orang di station itu
        $manPowerInStation = $manPowerQuery->get();

        // Hanya tampilkan jika ada lebih dari 1 orang
        if ($manPowerInStation->count() > 1) {
            foreach ($manPowerInStation as $mp) {
                $result[] = [
                    'id' => $mp->id,
                    'nama' => $mp->nama,
                ];
            }
        }
    }

    // --- 2. Ambil Troubleshooting berdasarkan grup saja ---
    $tsGrup = substr($grup, 0, 1); // ambil 'A' atau 'B' saja
    $troubleshooting = Troubleshooting::where('grup', $tsGrup)->get();

    foreach ($troubleshooting as $ts) {
        $result[] = [
            'id' => 't-' . $ts->id, // prefix agar tidak bentrok
            'nama' => $ts->nama . ' (TS)',
        ];
    }

    return response()->json($result);
}


}
