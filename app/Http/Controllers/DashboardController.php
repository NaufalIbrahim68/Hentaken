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
        // SECTION 1: AMBIL INPUT DARI FORM TIME SCHEDULER
        // ======================================================================
        $now = Carbon::now();

        $tanggalMulai = $request->query('tanggal_mulai');
        $tanggalBerakhir = $request->query('tanggal_berakhir');
        $waktuMulai = $request->query('waktu_mulai');
        $waktuBerakhir = $request->query('waktu_berakhir');
        $inputShiftNum = $request->query('shift'); // 1 atau 2
        $inputGrup = $request->query('grup'); // A atau B

        $shiftDisplay_1 = 'Shift 1';
        $shiftDisplay_2 = 'Shift 2';

        $grupForQuery = null;
        $shiftNumForQuery = null;
        $currentShift = null;

        // Jika user mengisi form Time Scheduler
        if ($inputShiftNum && $inputGrup) {
            $grupForQuery = $inputGrup;
            $shiftNumForQuery = $inputShiftNum;
            $currentShift = ($inputShiftNum == '1') ? $shiftDisplay_1 : $shiftDisplay_2;

            // ======================================================================
            // ⏳ Tambahkan Bagian Simpan Data ke Tabel TimeScheduler
            // ======================================================================
            $existingScheduler = TimeScheduler::where('grup', $grupForQuery)
                ->where('shift', $shiftNumForQuery)
                ->whereDate('tanggal_mulai', $tanggalMulai)
                ->whereDate('tanggal_berakhir', $tanggalBerakhir)
                ->whereTime('waktu_mulai', $waktuMulai)
                ->whereTime('waktu_berakhir', $waktuBerakhir)
                ->first();

            if (!$existingScheduler) {
                TimeScheduler::create([
                    'grup' => $grupForQuery,
                    'shift' => $shiftNumForQuery,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_berakhir' => $tanggalBerakhir,
                    'waktu_mulai' => $waktuMulai,
                    'waktu_berakhir' => $waktuBerakhir,
                ]);
            }
        } else {
            // Jika belum ada input form, pakai logika waktu default
            $time = $now->format('H:i');
            if ($time >= '07:00' && $time < '19:00') {
                $grupForQuery = 'B';
                $shiftNumForQuery = '1';
                $currentShift = $shiftDisplay_1;
            } else {
                $grupForQuery = 'A';
                $shiftNumForQuery = '2';
                $currentShift = $shiftDisplay_2;
            }
        }

        // ===================================================================
        // SECTION 2: FILTER TIME SCHEDULER BERDASARKAN INPUT
        // ===================================================================
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

        $activeSchedulerIds = $activeSchedulers;

        if ($tanggalMulai && $tanggalBerakhir && $waktuMulai && $waktuBerakhir) {
            $activeSchedulers = TimeScheduler::where('grup', $grupForQuery)
                ->where('shift', $shiftNumForQuery)
                ->where(function ($q) use ($tanggalMulai, $tanggalBerakhir, $waktuMulai, $waktuBerakhir) {
                    $q->whereDate('tanggal_mulai', '<=', $tanggalBerakhir)
                        ->whereDate('tanggal_berakhir', '>=', $tanggalMulai)
                        ->whereTime('waktu_mulai', '<=', $waktuBerakhir)
                        ->whereTime('waktu_berakhir', '>=', $waktuMulai);
                })
                ->pluck('id')
                ->toArray();

            $activeSchedulerIds = $activeSchedulers;
        }

        // ===================================================================
        // SECTION 3: DATA HENKATEN (TIDAK DIUBAH)
        // ===================================================================
        $baseHenkatenQuery = function ($query) use ($now) {
            $query->where('effective_date', '<=', $now)
                ->where(function ($subQuery) use ($now) {
                    $subQuery->where('end_date', '>=', $now)
                        ->orWhereNull('end_date');
                });
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
            ->orderBy('effective_date', 'desc')
            ->get();

        // ===================================================================
        // SECTION 4: FILTER MANPOWER BERDASARKAN SCHEDULER
        // ===================================================================
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

        // Tandai status manpower berdasarkan henkaten
        $henkatenManPowerIds = $activeManPowerHenkatens
            ->pluck('man_power_id')
            ->merge($activeManPowerHenkatens->pluck('man_power_id_after'))
            ->filter()
            ->unique()
            ->toArray();

        foreach ($manPower as $person) {
            if (in_array($person->id, $henkatenManPowerIds)) {
                $person->setAttribute('status', 'Henkaten');
            } else {
                $person->setAttribute('status', 'NORMAL');
            }
        }

        // ===================================================================
        // SECTION 5 & 6: (TIDAK DIUBAH)
        // ===================================================================
        $groupedManPower = $manPower->groupBy('station_id');
        $methods = Method::with('station')->paginate(5);
        $machines = Machine::with('station')->get();
        $materials = Material::all();
        $stations = Station::all();

        $stationStatuses = $stations->map(function ($station) {
            $material = $station->material;
            return [
                'id' => $station->id,
                'name' => $station->station_name,
                'status' => 'NORMAL',
                'material_name' => $material ? $material->material_name : null,
                'is_henkaten' => $material ? $material->is_henkaten : false,
            ];
        });

        return view('dashboard.index', compact(
            'groupedManPower',
            'currentShift',
            'methods',
            'machines',
            'materials',
            'stations',
            'stationStatuses',
            'activeManPowerHenkatens',
            'activeMethodHenkatens',
            'machineHenkatens',
            'materialHenkatens'
        ));
    }
}
