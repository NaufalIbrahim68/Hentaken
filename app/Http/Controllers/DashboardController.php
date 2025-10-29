<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Station;
use App\Models\ManPowerHenkaten;
use App\Models\MethodHenkaten;
use App\Models\MachineHenkaten;
use App\Models\MaterialHenkaten;
use App\Models\TimeScheduler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;


class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now('Asia/Jakarta');

        // ======================================================================
        // SECTION 1: HAPUS SCHEDULER LAMA
        // ======================================================================
        TimeScheduler::where(function ($q) use ($now) {
            $q->whereDate('tanggal_berakhir', '<', $now->toDateString())
                ->orWhere(function ($sub) use ($now) {
                    $sub->whereDate('tanggal_berakhir', '=', $now->toDateString())
                        ->whereTime('waktu_berakhir', '<', $now->toTimeString());
                });
        })->delete();

        // ======================================================================
        // SECTION 3: BACA SESSION
        // ======================================================================
        $grupForQuery      = session('active_grup');
        $shiftNumForQuery  = session('active_shift');
        $activeSchedulerId = session('active_scheduler_id');

        // JIKA SESSION KOSONG, jalankan auto-detect
      if (!$activeSchedulerId) {
 $currentScheduler = TimeScheduler::where(function ($q) use ($now) {
                // PERBAIKAN: Gunakan DATEADD untuk menggabungkan tanggal dan waktu
 $q->whereRaw('(DATEADD(second, DATEDIFF(second, 0, waktu_mulai), CAST(tanggal_mulai AS DATETIME))) <= ?', [$now->toDateTimeString()])
 ->whereRaw('(DATEADD(second, DATEDIFF(second, 0, waktu_berakhir), CAST(tanggal_berakhir AS DATETIME))) >= ?', [$now->toDateTimeString()]);
 })
 ->latest('tanggal_mulai')
 ->first();

            if ($currentScheduler) {
                $activeSchedulerId = $currentScheduler->id;
                $grupForQuery = $currentScheduler->grup;
                $shiftNumForQuery = $currentScheduler->shift;
                session([
                    'active_scheduler_id' => $activeSchedulerId,
                    'active_grup' => $grupForQuery,
                    'active_shift' => $shiftNumForQuery,
                ]);
            }
        }

        // ======================================================================
        // SECTION 4: TENTUKAN ID
        // ======================================================================
        $activeSchedulerIds = $activeSchedulerId ? [$activeSchedulerId] : [];

        // ======================================================================
        // SECTION 5: AMBIL DATA HENKATEN
        // ======================================================================
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
            } catch (\Exception $e) {
                // Abaikan jika tidak bisa cek struktur tabel
            }
        };

        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where(function ($query) use ($baseHenkatenQuery) {
                $baseHenkatenQuery($query);
            })
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where(function ($query) use ($baseHenkatenQuery) {
                $baseHenkatenQuery($query);
            })
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

        $machineHenkatens = ManPowerHenkaten::with('station')
            ->where(function ($query) use ($baseHenkatenQuery) {
                $baseHenkatenQuery($query);
            })
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

        $materialHenkatens = MaterialHenkaten::with('station')
            ->where(function ($query) use ($baseHenkatenQuery) {
                $baseHenkatenQuery($query);
            })
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

        // ======================================================================
        // SECTION 6: DATA MAN POWER
        // ======================================================================
        $manPower = ManPower::with('station', 'timeScheduler')
            ->where('grup', $grupForQuery)
            ->where('shift', $shiftNumForQuery)
            ->when(!empty($activeSchedulerIds), function ($query) use ($activeSchedulerIds) {
                $query->whereIn('time_scheduler_id', $activeSchedulerIds);
            })
            ->get();

        if ($manPower->isEmpty()) {
            $manPower = ManPower::with('station')->where('grup', $grupForQuery)->get();
        }

        $henkatenManPowerIds = $activeManPowerHenkatens
            ->pluck('man_power_id')
            ->merge($activeManPowerHenkatens->pluck('man_power_id_after'))
            ->filter()
            ->unique()
            ->toArray();

        foreach ($manPower as $person) {
            $person->setAttribute('status', in_array($person->id, $henkatenManPowerIds) ? 'Henkaten' : 'NORMAL');
        }

        // ======================================================================
        // SECTION 7: DATA TAMBAHAN
        // ======================================================================
        $methods = Method::with('station')->get();
        $machines = Machine::with('station')->get();
        $materials = Material::all()->groupBy('station_id');
        
        $henkatenMethodStationIds = $activeMethodHenkatens
            ->pluck('station_id')
            ->filter()
            ->unique()
            ->toArray();

        foreach ($methods as $method) {
            $isHenkaten = in_array($method->station_id, $henkatenMethodStationIds);
            $method->setAttribute('status', $isHenkaten ? 'HENKATEN' : ($method->keterangan ?? 'NORMAL'));
        }

        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();
        $stationWithMaterialIds = Material::pluck('station_id')->unique()->toArray();

        // Filter stations berdasarkan data material
        $stations = Station::whereIn('id', $stationWithMaterialIds)->get();

        // Buat status tiap station
        $stationStatuses = $stations->map(function ($station) use ($activeMaterialStationIds) {
            $status = in_array($station->id, $activeMaterialStationIds) ? 'HENKATEN' : 'NORMAL';
            return [
                'id' => $station->id,
                'name' => $station->station_name,
                'status' => $status,
            ];
        });

        // ======================================================================
        // SECTION 8: CEK DATA MANPOWER KOSONG
        // ======================================================================
        if (!$grupForQuery || !$shiftNumForQuery) {
            // Session KOSONG dan auto-detect GAGAL
            $groupedManPower = collect();
            $dataManPowerKosong = true;
        } else if ($manPower->isEmpty()) {
            // Session ADA, tapi data man power-nya KOSONG
            $groupedManPower = collect();
            $dataManPowerKosong = true;
        } else {
            $dataManPowerKosong = false;
            $groupedManPower = $manPower->groupBy('station_id');
        }

        // ======================================================================
        // SECTION 9: RETURN VIEW
        // ======================================================================
        return view('dashboard.index', [
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
        ]);
    }
}