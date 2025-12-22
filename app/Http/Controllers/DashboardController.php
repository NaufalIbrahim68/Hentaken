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
    
        // ============================================================
        // ROLE + LINE AREA HANDLING
        // ============================================================
        $user = Auth::user();
        $role = $user ? $user->role : null;
    
        // Untuk Leader QC, Leader PPIC, dan Sect Head QC, Sect Head PPIC set grup otomatis ke A
        $isAutoGroupA = in_array($role, ['Leader QC', 'Leader PPIC', 'Sect Head QC', 'Sect Head PPIC']);
        if ($isAutoGroupA) {
            session(['active_grup' => 'A']);
            $currentGroup = 'A';
        } else {
            $currentGroup = $request->input('group', session('active_grup'));
        }
    
        // Default: ambil semua line_area
        $lineAreas = Station::select('line_area')->distinct()->orderBy('line_area')->pluck('line_area');
    
        // Admin bisa akses semua line_area
        if ($role === 'Admin') {
            // Admin akses semua
        } else {
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
        }
    
        $selectedLineArea = request('line_area', $lineAreas->first());
        session(['active_line' => $selectedLineArea]);
    
        // ============================================================
        // BASE QUERY FUNCTION UNTUK FILTER HENKATEN
        // ============================================================
        $baseHenkatenQuery = function ($query) use ($now) {
            $today = $now->toDateString();
            $currentTime = $now->toTimeString();
            $currentDateTime = $now;
    
            $query->where(function ($q) use ($today, $currentTime, $currentDateTime) {
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
                $q->orWhere(function ($sub) use ($today, $currentTime, $currentDateTime) {
                    $sub->whereNotNull('time_start')
                        ->whereNotNull('time_end')
                        ->where(function ($dateRange) use ($currentDateTime) {
                            $dateRange->where(function ($singleDay) use ($currentDateTime) {
                                $singleDay->whereColumn('effective_date', '=', 'end_date')
                                          ->whereDate('effective_date', '=', $currentDateTime->toDateString())
                                          ->whereTime('time_start', '<=', $currentDateTime->toTimeString())
                                          ->whereTime('time_end', '>=', $currentDateTime->toTimeString());
                            })
                            ->orWhere(function ($multiDay) use ($currentDateTime) {
                                $multiDay->whereColumn('effective_date', '!=', 'end_date')
                                         ->where(function ($range) use ($currentDateTime) {
                                             $range->where(function ($startCheck) use ($currentDateTime) {
                                                 $startCheck->whereDate('effective_date', '=', $currentDateTime->toDateString())
                                                           ->whereTime('time_start', '<=', $currentDateTime->toTimeString());
                                             })
                                             ->orWhere(function ($middleCheck) use ($currentDateTime) {
                                                 $middleCheck->whereDate('effective_date', '<', $currentDateTime->toDateString())
                                                            ->whereDate('end_date', '>', $currentDateTime->toDateString());
                                             })
                                             ->orWhere(function ($endCheck) use ($currentDateTime) {
                                                 $endCheck->whereDate('end_date', '=', $currentDateTime->toDateString())
                                                         ->whereTime('time_end', '>=', $currentDateTime->toTimeString());
                                             });
                                         });
                            });
                        });
                });
    
                // CASE 3: SHIFT MALAM
                $q->orWhere(function ($sub) use ($today, $currentTime) {
                    $sub->whereNotNull('time_start')
                        ->whereNotNull('time_end')
                        ->whereColumn('time_start', '>', 'time_end')
                        ->whereDate('effective_date', '<=', $today)
                        ->where(function ($w) use ($today) {
                            $w->whereDate('end_date', '>=', $today)
                              ->orWhereNull('end_date');
                        })
                        ->where(function ($time) use ($currentTime) {
                            $time->whereTime('time_start', '<=', $currentTime)
                                  ->orWhereTime('time_end', '>=', $currentTime);
                        });
                });
            });
        };
    
        // ============================================================
        // HENKATEN QUERY
        // ============================================================
        $activeManPowerHenkatens = ManPowerHenkaten::query()
            ->where('status', 'PENDING')
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', function ($q) use ($selectedLineArea) {
                $q->where('line_area', $selectedLineArea);
            })
            ->when($currentGroup, function ($q) use ($currentGroup) {
                // Filter by grup dari kolom 'grup' di tabel man_power_henkatens
                $q->where('grup', $currentGroup);
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
            ->where('status', 'PENDING')
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->where($baseHenkatenQuery)
            ->latest('effective_date')
            ->get();
    
        // MACHINE HENKATEN
        $machineHenkatens = MachineHenkaten::with('station')
            ->where('status', 'PENDING')
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->where($baseHenkatenQuery)
            ->latest('effective_date')
            ->get();
    
        // MATERIAL HENKATEN
        $materialHenkatens = MaterialHenkaten::with(['station','material'])
            ->where('status', 'PENDING')
            ->where('shift', $shiftNumForQuery)
            ->whereHas('station', fn($q) => $q->where('line_area', $selectedLineArea))
            ->where($baseHenkatenQuery)
            ->latest('effective_date')
            ->get();
    
        // ============================================================
        // MANPOWER DISPLAY
        // ============================================================
        $manPower = collect();
        $dataManPowerKosong = true;
        $groupedManPower = collect();
    
        // Selalu jalankan jika grup sudah di-set (termasuk auto-set untuk Leader QC/PPIC)
        if ($currentGroup) {
            $allManPower = ManPower::with('station')
                ->where('grup', $currentGroup)
                ->where('is_main_operator', 1)
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
                        'grup' => $henk->grup ?? $currentGroup,
                        'station_id' => $stationId,
                        'status' => 'Henkaten',
                    ]);
    
                    $oldWorker->setRelation('station', $henk->station ?? Station::find($stationId));
                    $manPower->push($oldWorker);
                } else {
                    $workers = $manpowerByStation->get($stationId, collect());
                    if ($workers->isNotEmpty()) {
                        $worker = $workers->first();
                        $worker->setAttribute('status', 'NORMAL');
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
    
        $henkatenStationIds = $activeMethodHenkatens->pluck('station_id')->unique()->toArray();
    
        foreach ($methods as $method) {
            $method->setAttribute(
                'status',
                in_array($method->station_id, $henkatenStationIds) ? 'HENKATEN' : ($method->keterangan ?? 'NORMAL')
            );
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
            'Admin' => 'dashboard.index',
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
            'isAutoGroupA' => $isAutoGroupA, // Tambahkan ini untuk view
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