<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // ============================================================
        // ROLE + LINE AREA HANDLING
        // ============================================================
        $user = Auth::user();
        $role = $user ? $user->role : null;

        // Default: ambil semua line_area
        $lineAreas = Station::select('line_area')->distinct()->orderBy('line_area')->pluck('line_area');

        switch ($role) {
            case 'Leader QC':
                $lineAreas = collect(['Incoming', 'Delivery']);
                break;
            case 'Leader PPIC':
                $lineAreas = collect(['Delivery']);
                break;
            case 'Leader FA':
            case 'Leader SMT':
                break;

            case 'Sect Head Produksi':
                $lineAreas = collect(['FA L1','FA L2','FA L3','FA L5','FA L6','SMT L1','SMT L2']);
                break;
            case 'Sect Head QC':
                $lineAreas = collect(['Incoming']);
                break;
            case 'Sect Head PPIC':
                $lineAreas = collect(['Delivery']);
                break;
        }

        $selectedLineArea = request('line_area', $lineAreas->first());
        session(['active_line' => $selectedLineArea]);

        // ============================================================
        // BASE QUERY FUNCTION UNTUK FILTER HENKATEN
        // ============================================================
        $baseHenkatenQuery = function ($query) use ($now) {
            $today = $now->toDateString();
            $currentTime = $now->toTimeString();

            $query->where(function ($q) use ($today, $currentTime) {

                // CASE 1: TANPA JAM
                $q->where(function ($sub) use ($today) {
                    $sub->whereNull('time_start')
                        ->whereNull('time_end')
                        ->whereDate('effective_date', '<=', $today)
                        ->where(function ($w) use ($today) {
                            $w->whereDate('end_date', '>=', $today)
                              ->orWhereNull('end_date');
                        });
                });

                // CASE 2: DENGAN JAM
                $q->orWhere(function ($sub) use ($today, $currentTime) {
                    $sub->whereNotNull('time_start')
                        ->whereNotNull('time_end')
                        ->whereDate('effective_date', '<=', $today)
                        ->where(function ($w) use ($today) {
                            $w->whereDate('end_date', '>=', $today)
                              ->orWhereNull('end_date');
                        })
                        ->where(function ($time) use ($currentTime) {

                            // SHIFT NORMAL (07:00–19:00)
                            $time->where(function ($normal) use ($currentTime) {
                                $normal->whereColumn('time_start', '<', 'time_end')
                                       ->whereTime('time_start', '<=', $currentTime)
                                       ->whereTime('time_end', '>=', $currentTime);
                            });

                            // SHIFT MALAM (19:00–07:00)
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

        // ============================================================
        // HENKATEN QUERY
        // ============================================================

        // MANPOWER: hanya PENDING
        $activeManPowerHenkatens = ManPowerHenkaten::query()
            ->where('status', 'PENDING')
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', function ($q) use ($selectedLineArea) {
                $q->where('line_area', $selectedLineArea);
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
            ->flatMap(fn($row) => [$row->man_power_id, $row->man_power_id_after])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // METHOD HENKATEN
        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->whereIn('status', ['Approved','approved'])
            ->latest('effective_date')
            ->get();

        // MACHINE HENKATEN
        $machineHenkatens = MachineHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->whereIn('status', ['Approved','approved'])
            ->latest('effective_date')
            ->get();

        // MATERIAL HENKATEN
        $materialHenkatens = MaterialHenkaten::with(['station','material'])
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->whereIn('status', ['Approved','approved'])
            ->latest('effective_date')
            ->get();

        // ============================================================
        // MANPOWER DISPLAY
        // ============================================================

        $manPower = collect();
        $dataManPowerKosong = true;
        $groupedManPower = collect();

        if ($currentGroup) {
            $stationsQuery = Station::where('line_area', $selectedLineArea)->pluck('id');

            $allManPower = ManPower::with('station')
                ->where('grup', $currentGroup)
                ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
                ->get();

            $manpowerByStation = $allManPower->groupBy('station_id');
            $henkatenByStation = $activeManPowerHenkatens->groupBy('station_id');

            $stationIds = $manpowerByStation->keys()->merge($henkatenByStation->keys())->unique();

            foreach ($stationIds as $stationId) {
                if ($henkatenByStation->has($stationId)) {

                    $henk = $henkatenByStation[$stationId]->sortByDesc('effective_date')->first();

                    $oldWorker = new ManPower([
                        'id' => $henk->man_power_id,
                        'nama' => $henk->nama,
                        'keterangan' => $henk->keterangan,
                        'grup' => $henk->manPower->grup ?? $currentGroup,
                        'station_id' => $stationId,
                        'status' => 'Henkaten',
                    ]);

                    $oldWorker->setRelation('station', $henk->station ?? Station::find($stationId));
                    $manPower->push($oldWorker);

                } else {
                    $workers = $manpowerByStation->get($stationId, collect());
                    if ($workers->isNotEmpty()) {
                        $worker = $workers->first();
                        $worker->setAttribute('status', $worker->status ?? 'NORMAL');
                        $manPower->push($worker);
                    }
                }
            }

            if ($manPower->isNotEmpty()) {
                $dataManPowerKosong = false;
                $groupedManPower = $manPower->groupBy('station_id');
            }
        }

        // ============================================================
        // METHOD
        // ============================================================

        $methods = Method::with('station')
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->get();

        $henkatenMethodIds = $activeMethodHenkatens->pluck('method_id')->unique()->toArray();

        foreach ($methods as $method) {
            $method->setAttribute('status', in_array($method->id, $henkatenMethodIds)
                ? 'HENKATEN'
                : ($method->keterangan ?? 'NORMAL'));
        }

        // ============================================================
        // MACHINE
        // ============================================================
        $machines = Machine::with('station')
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->get();

        $henkatenMachineStationIds = $machineHenkatens->pluck('station_id')->unique()->toArray();

        foreach ($machines as $machine) {
            $machine->setAttribute('keterangan', in_array($machine->station_id, $henkatenMachineStationIds)
                ? 'HENKATEN'
                : ($machine->keterangan ?? 'NORMAL'));
        }

        // ============================================================
        // MATERIAL
        // ============================================================

        $stationIdsForLine = Station::where('line_area', $selectedLineArea)->pluck('id');

        $materials = Material::with('station')
                        ->whereIn('station_id', $stationIdsForLine)
                        ->get();

        $materialsByStationId = $materials->keyBy('station_id');

        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();

        $stations = Station::whereIn('id', $materialsByStationId->pluck('station_id'))->get();

        $stationStatuses = $stations->map(function ($station) use ($activeMaterialStationIds, $materialsByStationId) {

            $material = $materialsByStationId->get($station->id);

            $stationStatus = in_array($station->id, $activeMaterialStationIds)
                                ? 'HENKATEN'
                                : 'NORMAL';

            return [
                'id' => $station->id,
                'name' => $station->station_name,
                'status' => $stationStatus,
                'material_name' => $material ? $material->material_name : 'No Material Assigned',
                'material_status' => $material ? $material->status : 'INACTIVE',
            ];
        });

        // ============================================================
        // SELECT VIEW BERDASARKAN ROLE
        // ============================================================

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

    // ============================================================
    // SET GRUP
    // ============================================================
    public function setGrup(Request $request)
    {
        $request->validate(['grup' => 'required|string|in:A,B']);
        session(['active_grup' => $request->grup]);
        return response()->json(['status' => 'success', 'grup' => $request->grup]);
    }

    // ============================================================
    // RESET GRUP
    // ============================================================
    public function resetGrup()
    {
        session()->forget('active_grup');
        return redirect()->route('dashboard');
    }

    // ============================================================
    // SET LINE
    // ============================================================
    public function setLine(Request $request)
    {
        $request->validate(['line' => 'required|string']);
        session(['active_line' => $request->line]);

        return response()->json([
            'status' => 'success',
            'line' => $request->line
        ]);
    }
}
