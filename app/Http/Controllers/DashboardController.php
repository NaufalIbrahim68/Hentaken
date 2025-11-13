<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->toTimeString();
        $shiftNumForQuery = ($currentTime >= '07:00:00' && $currentTime <= '18:59:59') ? 2 : 1;
        session(['active_shift' => $shiftNumForQuery]);

        $grupForQuery = session('active_grup');

        // =========================================================================
        // LOGIKA DROPDOWN LINE AREA
        // =========================================================================
        $lineAreas = Station::select('line_area')
            ->distinct()
            ->orderBy('line_area', 'asc')
            ->pluck('line_area');

        $selectedLineArea = $request->query('line_area', $lineAreas->first());

        $lineForQuery = $selectedLineArea;
        session(['active_line' => $lineForQuery]);
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
$sameDay->whereDate('effective_date', '<=', $now->toDateString())
    ->whereDate('end_date', '>=', $now->toDateString())
    ->whereTime('time_start', '<=', $now->toTimeString())
    ->whereTime('time_end', '>=', $now->toTimeString());
                    });
                }
            } catch (\Exception $e) {
                // Log error if needed
            }
        };

        // === AMBIL SEMUA DATA HENKATEN ===
        $activeManPowerHenkatens = ManPowerHenkaten::with(['station', 'manPower'])
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
            ->when($grupForQuery, fn($q) => $q->whereHas('manPower', fn($sq) => $sq->where('grup', $grupForQuery)))
            ->whereIn('status', ['Approved', 'approved'])
            ->latest('effective_date')
            ->get();

        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
            ->whereIn('status', ['Approved', 'approved'])
            ->latest('effective_date')
            ->get();

        $machineHenkatens = MachineHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
            ->whereIn('status', ['Approved', 'approved'])
            ->latest('effective_date')
            ->get();

        $materialHenkatens = MaterialHenkaten::with(['station', 'material'])
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
            ->whereIn('status', ['Approved', 'approved'])
            ->latest('effective_date')
            ->get();

        // === DATA MANPOWER ===
        $replacedManPowerIds = $activeManPowerHenkatens->pluck('man_power_id')->toArray();

        $manPowerNormal = collect();
        if ($grupForQuery) {
            $manPowerNormal = ManPower::with('station')
                ->where('grup', $grupForQuery)
                ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
                ->whereNotIn('id', $replacedManPowerIds)
                ->get();
        }

        // Mark all Normal Man Power as 'NORMAL'
        foreach ($manPowerNormal as $person) {
            $person->setAttribute('status', 'NORMAL');
        }

        // Construct data from Approved Henkaten
        $manPowerHenkaten = collect();
        foreach ($activeManPowerHenkatens as $henkatenData) {
            // Old Worker (being replaced)
            $oldWorker = (object) [
                'id' => $henkatenData->man_power_id,
                'nama' => $henkatenData->nama,
                'keterangan' => $henkatenData->keterangan,
                'grup' => $henkatenData->manPower->grup ?? $henkatenData->grup,
                'station_id' => $henkatenData->station_id,
                'station' => $henkatenData->station,
                'status' => 'Henkaten',
            ];
            $manPowerHenkaten->push($oldWorker);

            // New Worker (replacement)
            $newWorker = (object) [
                'id' => $henkatenData->man_power_id_after,
                'nama' => $henkatenData->nama_after,
                'keterangan' => $henkatenData->keterangan,
                'grup' => $henkatenData->manPower->grup ?? $henkatenData->grup,
                'station_id' => $henkatenData->station_id,
                'station' => $henkatenData->station,
                'status' => 'NORMAL',
            ];
            $manPowerHenkaten->push($newWorker);
        }

        // Merge Normal and Henkaten Man Power
        $manPower = $manPowerNormal->merge($manPowerHenkaten);

        // === GROUPING & VALIDATION ===
        if (!$grupForQuery || $manPower->isEmpty()) {
            $dataManPowerKosong = true;
            $groupedManPower = collect();
        } else {
            $dataManPowerKosong = false;
            $groupedManPower = $manPower->groupBy('station_id');
        }

        // === METHOD ===
        $methods = Method::with('station')
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
            ->get();
        $henkatenMethodStationIds = $activeMethodHenkatens->pluck('station_id')->unique()->toArray();
        foreach ($methods as $method) {
            $method->setAttribute('status', in_array($method->station_id, $henkatenMethodStationIds) ? 'HENKATEN' : ($method->keterangan ?? 'NORMAL'));
        }

        // === MACHINE ===
        $machines = Machine::with('station')
            ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
            ->get();
        $henkatenMachineStationIds = $machineHenkatens->pluck('station_id')->unique()->toArray();
        foreach ($machines as $machine) {
            $machine->setAttribute('keterangan', in_array($machine->station_id, $henkatenMachineStationIds) ? 'HENKATEN' : ($machine->keterangan ?? 'NORMAL'));
        }

        // === MATERIAL ===
        $stationIdsForLine = Station::where('line_area', $lineForQuery)->pluck('id');
        $materials = Material::whereIn('station_id', $stationIdsForLine)->get()->groupBy('station_id');

        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();
        $stationWithMaterialIds = Material::whereIn('station_id', $stationIdsForLine)->pluck('station_id')->unique()->toArray();

        $stations = Station::whereIn('id', $stationWithMaterialIds)->get();

        $stationStatuses = $stations->map(fn($station) => [
            'id' => $station->id,
            'name' => $station->station_name,
            'status' => in_array($station->id, $activeMaterialStationIds) ? 'HENKATEN' : 'NORMAL',
        ]);

        // ======================================================================
        // SECTION: SELECT DASHBOARD BASED ON USER ROLE
        // ======================================================================
        $user = Auth::user();
        $role = $user ? $user->role : null;

        // Different view for each role
        $view = match ($role) {
            'Leader FA' => 'dashboard.roles.leader_fa',
            'Leader SMT' => 'dashboard.roles.leader_smt',
            'Leader PPIC' => 'dashboard.roles.leader_ppic',
            'Leader QC' => 'dashboard.roles.leader_qc',
            'Sect Head Produksi' => 'dashboard.roles.leader_fa',
            'Sect Head PPIC' => 'dashboard.roles.secthead_ppic',
            'Sect Head QC' => 'dashboard.roles.secthead_qc',
            default => 'dashboard.index',
        };

        return view($view, [
            'lineAreas' => $lineAreas,
            'selectedLineArea' => $selectedLineArea,
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

    public function setLine(Request $request)
    {
        $request->validate([
            'line' => 'required|string',
        ]);

        session(['active_line' => $request->line]);

        return response()->json([
            'status' => 'success',
            'line' => $request->line
        ]);
    }
}