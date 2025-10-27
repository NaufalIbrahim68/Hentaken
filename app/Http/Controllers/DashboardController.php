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

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ======================================================================
        // SECTION 1: HAPUS SCHEDULER YANG SUDAH LEWAT
        // ======================================================================
        $now = Carbon::now();

        TimeScheduler::where(function ($q) use ($now) {
            $q->whereDate('tanggal_berakhir', '<', $now->toDateString())
              ->orWhere(function ($sub) use ($now) {
                  $sub->whereDate('tanggal_berakhir', '=', $now->toDateString())
                      ->whereTime('waktu_berakhir', '<', $now->toTimeString());
              });
        })->delete();

        // ======================================================================
        // SECTION 2: INPUT TIME SCHEDULER DARI FORM
        // ======================================================================
        $tanggalMulai   = $request->query('tanggal_mulai');
        $tanggalBerakhir = $request->query('tanggal_berakhir');
        $waktuMulai     = $request->query('waktu_mulai');
        $waktuBerakhir  = $request->query('waktu_berakhir');
        $inputShiftNum  = $request->query('shift');
        $inputGrup      = $request->query('grup');

        // Jika user mengisi form scheduler, simpan atau gunakan yang sudah ada
        if ($inputShiftNum && $inputGrup) {
            $existingScheduler = TimeScheduler::where('grup', $inputGrup)
                ->where('shift', $inputShiftNum)
                ->whereDate('tanggal_mulai', $tanggalMulai)
                ->whereDate('tanggal_berakhir', $tanggalBerakhir)
                ->whereTime('waktu_mulai', $waktuMulai)
                ->whereTime('waktu_berakhir', $waktuBerakhir)
                ->first();

            if (!$existingScheduler) {
                $existingScheduler = TimeScheduler::create([
                    'grup' => $inputGrup,
                    'shift' => $inputShiftNum,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_berakhir' => $tanggalBerakhir,
                    'waktu_mulai' => $waktuMulai,
                    'waktu_berakhir' => $waktuBerakhir,
                ]);
            }

            // Simpan ke session
            session([
                'active_scheduler_id' => $existingScheduler->id,
                'active_grup' => $inputGrup,
                'active_shift' => $inputShiftNum,
            ]);
        }

        // ======================================================================
        // SECTION 3: CEK SCHEDULER AKTIF (PASTIKAN PAKAI YANG DARI DB)
        // ======================================================================
        $activeSchedulerId = session('active_scheduler_id');
        $grupForQuery      = session('active_grup');
        $shiftNumForQuery  = session('active_shift');

        if (!$activeSchedulerId) {
            // 🔍 Cari scheduler yang aktif berdasarkan waktu sekarang
            $currentScheduler = TimeScheduler::where(function ($q) use ($now) {
                    $q->whereDate('tanggal_mulai', '<=', $now->toDateString())
                      ->whereDate('tanggal_berakhir', '>=', $now->toDateString())
                      ->whereTime('waktu_mulai', '<=', $now->toTimeString())
                      ->whereTime('waktu_berakhir', '>=', $now->toTimeString());
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
            } else {
                // fallback ke default jika belum ada jadwal aktif
                $grupForQuery = 'A';
                $shiftNumForQuery = '1';
            }
        }

        // ======================================================================
        // SECTION 4: AMBIL ID SCHEDULER AKTIF
        // ======================================================================
        $activeSchedulers = TimeScheduler::where('grup', $grupForQuery)
            ->where('shift', $shiftNumForQuery)
            ->where(function ($q) use ($now) {
                $q->whereDate('tanggal_mulai', '<=', $now->toDateString())
                  ->whereDate('tanggal_berakhir', '>=', $now->toDateString())
                  ->whereTime('waktu_mulai', '<=', $now->toTimeString())
                  ->whereTime('waktu_berakhir', '>=', $now->toTimeString());
            })
            ->pluck('id')
            ->toArray();

        $activeSchedulerIds = !empty($activeSchedulers) ? $activeSchedulers : [$activeSchedulerId];

        // ======================================================================
        // SECTION 5: AMBIL DATA HENKATEN
        // ======================================================================
        $baseHenkatenQuery = function ($query) use ($now) {
            $query->whereDate('effective_date', '<=', $now->toDateString())
                  ->whereDate('end_date', '>=', $now->toDateString());
        };

        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where($baseHenkatenQuery)
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where($baseHenkatenQuery)
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

       $machineHenkatens = ManPowerHenkaten::with('station') 
            ->where($baseHenkatenQuery)
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

            
        $materialHenkatens = MaterialHenkaten::with('station')
            ->where($baseHenkatenQuery)
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();

        // ======================================================================
        // SECTION 6: DATA MAN POWER BERDASARKAN SCHEDULER
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

        // Tandai status Henkaten
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
        $groupedManPower = $manPower->groupBy('station_id');
        $methods = Method::with('station')->paginate(5);
        $machines = Machine::with('station')->get();
        $materials = Material::all();
        $stations = Station::all();

        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();

        $stationStatuses = $stations->map(function ($station) use ($activeMaterialStationIds) {
            $status = in_array($station->id, $activeMaterialStationIds) ? 'HENKATEN' : 'NORMAL';
            return [
                'id' => $station->id,
                'name' => $station->station_name,
                'status' => $status,
            ];
        });

        // ======================================================================
        // SECTION 8: VALIDASI SCHEDULER AKTIF
        // ======================================================================
        $timeSchedulerAktif = TimeScheduler::whereIn('id', $activeSchedulerIds)
            ->where(function ($q) use ($now) {
                $q->whereDate('tanggal_berakhir', '>=', $now->toDateString())
                  ->whereTime('waktu_berakhir', '>=', $now->toTimeString());
            })
            ->exists();

        if (!$timeSchedulerAktif) {
            session()->forget(['active_scheduler_id', 'active_grup', 'active_shift']);
            $groupedManPower = collect();
            $dataManPowerKosong = true;
        } else {
            $dataManPowerKosong = false;
        }

        // ======================================================================
        // SECTION 9: RETURN VIEW
        // ======================================================================
        return view('dashboard.index', [
            'groupedManPower' => $groupedManPower,
            'currentGroup' => $grupForQuery,       // ✅ untuk filter henkaten di Blade
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
