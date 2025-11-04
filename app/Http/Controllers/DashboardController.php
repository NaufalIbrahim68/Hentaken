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
    public function index() : View
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->toTimeString();
        $shiftNumForQuery = ($currentTime >= '07:00:00' && $currentTime <= '18:59:59') ? 2 : 1;
        session(['active_shift' => $shiftNumForQuery]);

        $grupForQuery = session('active_grup');
        $lineForQuery = session('active_line', 'LINE 5'); // default LINE 5
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
        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')->get();

        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')->get();

        $machineHenkatens = MachineHenkaten::with('station')
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')->get();

        $materialHenkatens = MaterialHenkaten::with(['station', 'material'])
            ->where(fn($q) => $baseHenkatenQuery($q))
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')->get();

        // === DATA MANPOWER ===
        $manPower = collect();
        if ($grupForQuery) {
            $manPower = ManPower::with('station')
                ->where('grup', $grupForQuery)->get();
        }

        $henkatenManPowerIds = $activeManPowerHenkatens
            ->pluck('man_power_id')
            ->merge($activeManPowerHenkatens->pluck('man_power_id_after'))
            ->filter()->unique()->toArray();

        foreach ($manPower as $person) {
            $person->setAttribute('status', in_array($person->id, $henkatenManPowerIds) ? 'Henkaten' : 'NORMAL');
        }

        // === METHOD ===
        $methods = Method::with('station')->get();
        $henkatenMethodStationIds = $activeMethodHenkatens->pluck('station_id')->unique()->toArray();
        foreach ($methods as $method) {
            $method->setAttribute('status', in_array($method->station_id, $henkatenMethodStationIds) ? 'HENKATEN' : ($method->keterangan ?? 'NORMAL'));
        }

        // === MACHINE ===
        $machines = Machine::with('station')->get();
        $henkatenMachineStationIds = $machineHenkatens->pluck('station_id')->unique()->toArray();
        foreach ($machines as $machine) {
            $machine->setAttribute('keterangan', in_array($machine->station_id, $henkatenMachineStationIds) ? 'HENKATEN' : ($machine->keterangan ?? 'NORMAL'));
        }

        // === MATERIAL ===
        $materials = Material::all()->groupBy('station_id');
        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();
        $stationWithMaterialIds = Material::pluck('station_id')->unique()->toArray();
        $stations = Station::whereIn('id', $stationWithMaterialIds)->get();

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
            'Sect Head Produksi' => 'dashboard.roles.secthead_produksi',
            'Sect Head PPIC' => 'dashboard.roles.secthead_ppic',
            'Sect Head QC' => 'dashboard.roles.secthead_qc',
            default => 'dashboard.index', // default dashboard
        };

        return view($view, [
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
        'line' => 'required|string|in:LINE 1,LINE 2,LINE 3,LINE 4,LINE 5,LINE 6',
    ]);

    // Simpan line ke session
    session(['active_line' => $request->line]);

    return response()->json([
        'status' => 'success',
        'line' => $request->line
    ]);
}

}
