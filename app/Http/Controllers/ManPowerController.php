<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Station;
use App\Models\ManPower;
use App\Models\ManPowerHenkaten; 
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
            'station_id' => 'required|exists:stations,id',
            'shift' => 'required|in:1,2',
            'grup' => 'required|in:A,B',
        ]);

        ManPower::create($request->only(['nama', 'station_id', 'shift', 'grup']));

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

}
