<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // [DIUBAH] Kita perlu Request
use Illuminate\Support\Facades\Auth;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Station;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MachineHenkaten;
use App\Models\MaterialHenkaten;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // [DIUBAH] Tambahkan (Request $request) agar kita bisa membaca URL
    public function index(Request $request) : View
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->toTimeString();
        $shiftNumForQuery = ($currentTime >= '07:00:00' && $currentTime <= '18:59:59') ? 2 : 1;
        session(['active_shift' => $shiftNumForQuery]);

        $grupForQuery = session('active_grup');

        // =========================================================================
        // [BARU] LOGIKA DROPDOWN LINE AREA
        // =========================================================================
        // 1. Ambil semua line_area unik dari database untuk dropdown
        $lineAreas = Station::select('line_area')
                            ->distinct()
                            ->orderBy('line_area', 'asc')
                            ->pluck('line_area');

        // 2. Tentukan line_area yang dipilih dari URL (?line_area=...)
        //    Jika tidak ada, gunakan line pertama sebagai default
        $selectedLineArea = $request->query('line_area', $lineAreas->first());

        // 3. Gunakan $selectedLineArea sebagai $lineForQuery Anda
        $lineForQuery = $selectedLineArea;
        session(['active_line' => $lineForQuery]); // Update session juga
        // =========================================================================


        $baseHenkatenQuery = function ($query) use ($now) {
            $query->where(function ($q) use ($now) {
                $q->where('effective_date', '<=', $now)
                    ->where(function ($sub) use ($now) {
                        $sub->where('end_date', '>=', $now)
                            ->orWhereNull('end_date');
                    });
            });
            try {
                $columns = Schema::getColumnListing($query->getModel()->getTable());
                if (in_array('time_start', $columns) && in_array('time_end', $columns)) {
                    $query->orWhere(function ($sameDay) use ($now) {
                        $sameDay->whereDate('effective_date', '=', $now->toDateString())
                            ->whereDate('end_date', '=', $now->toDateString())
                            ->whereTime('time_start', '<=', $now->toTimeString())
                            ->whereTime('time_end', '>=', $now->toTimeString());
                    });
                }
            } catch (\Exception $e) { }
        };

        // === AMBIL SEMUA DATA HENKATEN ===
        // [DIUBAH] Tambahkan filter whereHas untuk line_area
        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
            ->latest('effective_date')->get();

        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
            ->latest('effective_date')->get();

        $machineHenkatens = MachineHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
            ->latest('effective_date')->get();

        $materialHenkatens = MaterialHenkaten::with(['station', 'material'])
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
            ->latest('effective_date')->get();

        // === DATA MANPOWER ===
        $manPower = collect();
        if ($grupForQuery) {
            $manPower = ManPower::with('station')
                ->where('grup', $grupForQuery)
                ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
                ->get();
        }

        $henkatenManPowerIds = $activeManPowerHenkatens
            ->pluck('man_power_id')
            ->merge($activeManPowerHenkatens->pluck('man_power_id_after'))
            ->filter()->unique()->toArray();

        foreach ($manPower as $person) {
            $person->setAttribute('status', in_array($person->id, $henkatenManPowerIds) ? 'Henkaten' : 'NORMAL');
        }

        // === METHOD ===
        // [DIUBAH] Tambahkan filter whereHas untuk line_area
        $methods = Method::with('station')
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
            ->get();
        $henkatenMethodStationIds = $activeMethodHenkatens->pluck('station_id')->unique()->toArray();
        foreach ($methods as $method) {
            $method->setAttribute('status', in_array($method->station_id, $henkatenMethodStationIds) ? 'HENKATEN' : ($method->keterangan ?? 'NORMAL'));
        }

        // === MACHINE ===
        // [DIUBAH] Tambahkan filter whereHas untuk line_area
        $machines = Machine::with('station')
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery)) // <-- Filter Line
            ->get();
        $henkatenMachineStationIds = $machineHenkatens->pluck('station_id')->unique()->toArray();
        foreach ($machines as $machine) {
            $machine->setAttribute('keterangan', in_array($machine->station_id, $henkatenMachineStationIds) ? 'HENKATEN' : ($machine->keterangan ?? 'NORMAL'));
        }

        // === MATERIAL ===
        // [DIUBAH] Ambil station ID berdasarkan line area
        $stationIdsForLine = Station::where('line_area', $lineForQuery)->pluck('id');
        $materials = Material::whereIn('station_id', $stationIdsForLine)->get()->groupBy('station_id'); // <-- Filter Material
        
        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();
        $stationWithMaterialIds = Material::whereIn('station_id', $stationIdsForLine)->pluck('station_id')->unique()->toArray(); // <-- Filter Station
        
        $stations = Station::whereIn('id', $stationWithMaterialIds)->get(); // <-- Station sudah terfilter by line

        $stationStatuses = $stations->map(fn($station) => [
            'id' => $station->id,
            'name' => $station->station_name,
            'status' => in_array($station->id, $activeMaterialStationIds) ? 'HENKATEN' : 'NORMAL',
        ]);

        // === GRUPING & VALIDASI ===
        if (!$grupForQuery || $manPower->isEmpty()) {
            $dataManPowerKosong = true;
            $groupedManPower = collect();
        } else {
            $dataManPowerKosong = false;
            $groupedManPower = $manPower->groupBy('station_id');
        }

        // ======================================================================
        // SECTION: PILIH DASHBOARD BERDASARKAN ROLE USER
        // ======================================================================
        $user = Auth::user();
        $role = $user ? $user->role : null;

        // View berbeda untuk tiap role
        $view = match ($role) {
            'Leader FA' => 'dashboard.roles.leader_fa',
            'Leader SMT' => 'dashboard.roles.leader_smt',
            'Leader PPIC' => 'dashboard.roles.leader_ppic',
            'Leader QC' => 'dashboard.roles.leader_qc',
            'Sect Head Produksi' => 'dashboard.roles.leader_fa', // [DIUBAH] Arahkan ke view yg sama
            'Sect Head PPIC' => 'dashboard.roles.secthead_ppic',
            'Sect Head QC' => 'dashboard.roles.secthead_qc',
            default => 'dashboard.index', // default dashboard
        };

        // [DIUBAH] Tambahkan $lineAreas dan $selectedLineArea ke view
        // INI AKAN MEMPERBAIKI ERROR 'Undefined variable $lineAreas'
        return view($view, [
            'lineAreas' => $lineAreas,                 // <-- [BARU] Ini perbaikan errornya
            'selectedLineArea' => $selectedLineArea,   // <-- [BARU] Ini untuk value dropdown
            'groupedManPower' => $groupedManPower,
            'currentGroup' => $grupForQuery,
            'currentShift' => $shiftNumForQuery,
            'methods' => $methods,
            'machines' => $machines,
            'materials' => $materials,
            'stations' => $stations,
            'stationStatuses' => $stationStatuses,
            'activeManPowerHenkatens' => $activeManPowerHenkatens,
            'activeMethodHenkatens' => $activeMethodHenkatens,
            'machineHenkatens' => $machineHenkatens,
            'materialHenkatens' => $materialHenkatens,
            'dataManPowerKosong' => $dataManPowerKosong,
            'userRole' => $role,
        ]);
    }

    public function setGrup(Request $request)
    {
        $request->validate(['grup' => 'required|string|in:A,B']);
        session(['active_grup' => $request->grup]);
        return response()->json(['status' => 'success', 'grup' => $request->grup]);
    }

    public function resetGrup()
    {
        session()->forget('active_grup');
        return redirect()->route('dashboard');
    }

    // [DIUBAH] Fungsi setLine ini tidak lagi diperlukan jika Anda menggunakan
    // dropdown <form GET>, tapi saya biarkan saja
    public function setLine(Request $request)
    {
        $request->validate([
            'line' => 'required|string', // Validasi bisa lebih longgar
        ]);

        // Simpan line ke session
        session(['active_line' => $request->line]);

        return response()->json([
            'status' => 'success',
            'line' => $request->line
        ]);
    }

}