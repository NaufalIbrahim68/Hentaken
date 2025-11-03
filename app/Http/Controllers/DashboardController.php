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
use App\Models\MachineHenkaten; // Pastikan ini di-use
use App\Models\MaterialHenkaten;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Ganti seluruh fungsi index() Anda dengan ini.
     */
    public function index()
    {
        $now = Carbon::now('Asia/Jakarta');

        // ======================================================================
        // SECTION 1: TENTUKAN SHIFT SAAT INI
        // ======================================================================
        $currentTime = $now->toTimeString(); // Format 'HH:MM:SS'
        $shiftNumForQuery = 1; // Default Shift 1 (Malam: 19:00 - 06:59:59)

        // Cek apakah masuk jam Shift 2 (Siang: 07:00:00 - 18:59:59)
        if ($currentTime >= '07:00:00' && $currentTime <= '18:59:59') {
            $shiftNumForQuery = 2;
        }
        
        // Simpan shift aktif ke session untuk konsistensi (opsional, tapi bagus)
        session(['active_shift' => $shiftNumForQuery]);


        // ======================================================================
        // SECTION 2: BACA SESSION GRUP
        // ======================================================================
        $grupForQuery = session('active_grup');
        

        // ======================================================================
        // SECTION 3: AMBIL DATA HENKATEN
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

        // **PERBAIKAN BUG:** Menggunakan model MachineHenkaten
        $machineHenkatens = MachineHenkaten::with('station') // <-- DIPERBAIKI
            ->where(function ($query) use ($baseHenkatenQuery) {
                $baseHenkatenQuery($query);
            })
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();
            
        $materialHenkatens = MaterialHenkaten::with(['station', 'material'])
            ->where(function ($query) use ($baseHenkatenQuery) {
                $baseHenkatenQuery($query);
            })
            ->where('shift', $shiftNumForQuery)
            ->latest('effective_date')
            ->get();


        // ======================================================================
        // SECTION 4: DATA MAN POWER
        // ======================================================================
        
        $manPower = collect();
        if ($grupForQuery) {
            $manPower = ManPower::with('station')
                ->where('grup', $grupForQuery)
                ->get();
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
        // SECTION 5: DATA TAMBAHAN (METHOD, MACHINE, MATERIAL)
        // ======================================================================
        
        // --- 5.A: Data Method ---
        $methods = Method::with('station')->get();
        $henkatenMethodStationIds = $activeMethodHenkatens
            ->pluck('station_id')
            ->filter()
            ->unique()
            ->toArray();

        foreach ($methods as $method) {
            $isHenkaten = in_array($method->station_id, $henkatenMethodStationIds);
            $method->setAttribute('status', $isHenkaten ? 'HENKATEN' : ($method->keterangan ?? 'NORMAL'));
        }

        // --- 5.B: Data Machine (LOGIKA BARU DITAMBAHKAN) ---
        $machines = Machine::with('station')->get();
        
        // Ambil semua station_id unik dari machine henkaten yang aktif
        $henkatenMachineStationIds = $machineHenkatens
            ->pluck('station_id')
            ->filter()
            ->unique()
            ->toArray();

        // Loop data master machine dan set status 'keterangan'
        foreach ($machines as $machine) {
            $isHenkaten = in_array($machine->station_id, $henkatenMachineStationIds);
            // Kita set 'keterangan' karena blade $mc->keterangan
            $machine->setAttribute('keterangan', $isHenkaten ? 'HENKATEN' : ($machine->keterangan ?? 'NORMAL'));
        }


        // --- 5.C: Data Material ---
        $materials = Material::all()->groupBy('station_id');
        $activeMaterialStationIds = $materialHenkatens->pluck('station_id')->unique()->toArray();
        $stationWithMaterialIds = Material::pluck('station_id')->unique()->toArray();

        $stations = Station::whereIn('id', $stationWithMaterialIds)->get();

        $stationStatuses = $stations->map(function ($station) use ($activeMaterialStationIds) {
            $status = in_array($station->id, $activeMaterialStationIds) ? 'HENKATEN' : 'NORMAL';
            return [
                'id' => $station->id,
                'name' => $station->station_name,
                'status' => $status,
            ];
        });

        // ======================================================================
        // SECTION 6: CEK DATA MANPOWER KOSONG
        // ======================================================================
        
        if (!$grupForQuery) {
            $groupedManPower = collect();
            $dataManPowerKosong = true;
        } else if ($manPower->isEmpty()) {
            $groupedManPower = collect();
            $dataManPowerKosong = true;
        } else {
            $dataManPowerKosong = false;
            $groupedManPower = $manPower->groupBy('station_id');
        }

        return view('dashboard.index', [
            'groupedManPower' => $groupedManPower,
            'currentGroup' => $grupForQuery,
            'currentShift' => $shiftNumForQuery,
            'methods' => $methods,
            'machines' => $machines, // Variabel $machines sekarang sudah punya status 'keterangan'
            'materials' => $materials,
            'stations' => $stations,
            'stationStatuses' => $stationStatuses,
            'activeManPowerHenkatens' => $activeManPowerHenkatens,
            'activeMethodHenkatens' => $activeMethodHenkatens,
            'machineHenkatens' => $machineHenkatens, // Variabel ini sekarang berisi data MachineHenkaten
            'materialHenkatens' => $materialHenkatens,
            'dataManPowerKosong' => $dataManPowerKosong,
        ]);
    }

    // ... (fungsi setGrup dan resetGrup Anda tetap sama) ...
    // ... (pastikan Anda tidak menghapusnya) ...

    public function setGrup(Request $request)
    {
        $request->validate([
            'grup' => 'required|string|in:A,B',
        ]);

        // Simpan grup ke session
        session(['active_grup' => $request->grup]);

        return response()->json([
            'status' => 'success',
            'grup' => $request->grup
        ]);
    }

    public function resetGrup()
    {
        // Hapus 'active_grup' dari session
        session()->forget('active_grup');

        // Kembali ke halaman dashboard
        return redirect()->route('dashboard');
    }

}