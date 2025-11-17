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

$currentGroup = $request->input('group', session('active_grup'));

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
    $today = $now->toDateString();
    $currentTime = $now->toTimeString();

    $query->where(function ($q) use ($today, $currentTime) {

        // ============================================================
        // CASE 1: TANPA JAM → FULL DAY
        // ============================================================
        $q->where(function ($sub) use ($today) {
            $sub->whereNull('time_start')
                ->whereNull('time_end')
                ->whereDate('effective_date', '<=', $today)
                ->where(function ($w) use ($today) {
                    $w->whereDate('end_date', '>=', $today)
                      ->orWhereNull('end_date');
                });
        });

        // ============================================================
        // CASE 2: ADA JAM → PER SHIFT
        // ============================================================
        $q->orWhere(function ($sub) use ($today, $currentTime) {
            $sub->whereNotNull('time_start')
                ->whereNotNull('time_end')
                ->whereDate('effective_date', '<=', $today)
                ->where(function ($w) use ($today) {
                    $w->whereDate('end_date', '>=', $today)
                      ->orWhereNull('end_date');
                })
                ->where(function ($time) use ($currentTime) {

                    // CASE 2A: SHIFT NORMAL (Ex: 07:00–19:00)
                    $time->where(function ($normal) use ($currentTime) {
                        $normal->whereColumn('time_start', '<', 'time_end')
                               ->whereTime('time_start', '<=', $currentTime)
                               ->whereTime('time_end', '>=', $currentTime);
                    });

                    // CASE 2B: SHIFT MALAM (Ex: 19:00–07:00)
                    $time->orWhere(function ($night) use ($currentTime) {
                        $night->whereColumn('time_start', '>', 'time_end')
                              ->where(function ($n) use ($currentTime) {
                                  $n->whereTime('time_start', '<=', $currentTime)
                                    ->orWhereTime('time_end', '>=', $currentTime);
                              });
                    });
                });
        });

    });
};



        // === AMBIL SEMUA DATA HENKATEN ===
       $activeManPowerHenkatens = ManPowerHenkaten::query()
    ->where('status', 'PENDING')
    ->where('shift', $shiftNumForQuery)
    ->whereHas('station', function ($q) use ($lineForQuery) {
        $q->where('line_area', $lineForQuery);
    })
    ->when($currentGroup, function ($q) use ($currentGroup) {
        $q->whereHas('manPower', function ($mp) use ($currentGroup) {
            $mp->where('grup', $currentGroup);
        });
    })
    ->where(function ($q) {
        $q->where(function ($dateQ) {
            $dateQ->whereDate('effective_date', '<=', today())
                  ->whereNull('end_date');
        })->orWhere(function ($dateQ) {
            $dateQ->whereDate('effective_date', '<=', today())
                  ->whereDate('end_date', '>=', today());
        });
    })
    ->get();

$henkatenIds = $activeManPowerHenkatens
    ->flatMap(function ($row) {
        return [
            $row->man_power_id,        // Old worker
            $row->man_power_id_after,  // New worker
        ];
    })
    ->filter()   // buang null
    ->unique()   // hilangkan duplikat
    ->values()   // reset index
    ->toArray();




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
$manPower = collect();
$dataManPowerKosong = true;
$groupedManPower = collect();

if ($currentGroup) {
    // Ambil semua stations yang relevan (line + grup)
    $stationsQuery = Station::where('line_area', $lineForQuery)->pluck('id');

    // Ambil semua manpower di line+grup (bisa >1 per station)
    $allManPower = ManPower::with('station')
    ->where('grup', $currentGroup)
        ->whereHas('station', fn($q) => $q->where('line_area', $lineForQuery))
        ->get();

    // Buat index manpower by station id (array of workers per station)
    $manpowerByStation = $allManPower->groupBy('station_id');

    // Index henkaten by station for quick lookup (we assume at most one active henkaten per station)
    $henkatenByStation = $activeManPowerHenkatens->groupBy('station_id');

    // For each station that has any manpower (or henkaten), determine displayed worker
    $stationIds = $manpowerByStation->keys()->merge($henkatenByStation->keys())->unique();

    foreach ($stationIds as $stationId) {
        // If there is active henkaten for this station -> show OLD WORKER (man_power_id) as Henkaten
        if ($henkatenByStation->has($stationId)) {
            // if multiple henkaten, pick the latest by effective_date
            $henk = $henkatenByStation[$stationId]->sortByDesc('effective_date')->first();

            // Create a temporary ManPower-like object for OLD worker (keep nama lama)
            $oldWorker = new ManPower([
                'id' => $henk->man_power_id,
                'nama' => $henk->nama,
                'keterangan' => $henk->keterangan,
                'grup' => $henk->manPower->grup ?? $henk->grup ?? $currentGroup,
                'station_id' => $stationId,
                'status' => 'Henkaten',
            ]);
            $oldWorker->setRelation('station', $henk->station ?? Station::find($stationId));
            $manPower->push($oldWorker);
        } else {
            // No henkaten -> take first normal worker in that station (or all if you prefer)
            $workers = $manpowerByStation->get($stationId, collect());
            if ($workers->isNotEmpty()) {
                // choose display strategy: if multiple, pick the first (you can choose ordering)
                $worker = $workers->first();
                $worker->setAttribute('status', $worker->status ?? 'NORMAL');
                $manPower->push($worker);
            }
        }
    }

    if ($manPower->isNotEmpty()) {
        $dataManPowerKosong = false;
        $groupedManPower = $manPower->groupBy('station_id');
    } else {
        $dataManPowerKosong = true;
        $groupedManPower = collect();
    }
} else {
    $dataManPowerKosong = true;
    $groupedManPower = collect();
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
            'currentGroup' => $currentGroup,
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
             'henkatenIds' => $henkatenIds, 
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