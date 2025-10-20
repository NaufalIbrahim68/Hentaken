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
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Tentukan Shift Secara Dinamis berdasarkan waktu saat ini
        $now = Carbon::now();
        $time = $now->format('H:i');

        // TENTUKAN NILAI SHIFT SESUAI DATABASE ANDA
        $shiftA_Value = 'Shift A';
        $shiftB_Value = 'Shift B';

        // Shift B: 07:00 - 19:00
        if ($time >= '07:00' && $time < '19:00') {
            $currentShift = $shiftB_Value;
        }
        // Waktu di luar itu dianggap Shift A
        else {
            $currentShift = $shiftA_Value;
        }

        // ================= SUMBER DATA HENKATEN =================
        // Ambil Henkaten Man Power yang aktif (casting tanggal ditangani oleh Model)
        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                      ->orWhereNull('end_date');
            })
            ->latest('effective_date')
            ->get();

        // Ambil Henkaten Method yang aktif dari tabelnya sendiri (casting tanggal ditangani oleh Model)
        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                      ->orWhereNull('end_date');
            })
            ->latest('effective_date')
            ->get();
            
        // DIUBAH: Nama variabel disesuaikan dengan yang ada di view Blade
        $machineHenkatens = $activeManPowerHenkatens;
        $materialHenkatens = $activeManPowerHenkatens;


        // ================= SINKRONISASI DATA MAN POWER =================
        $manPower = ManPower::with('station')->get();

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

        // ================= PERSIAPAN DATA UNTUK VIEW =================
        $groupedManPower = $manPower->groupBy('station_id');
        $methods = Method::with('station')->paginate(5);
        $machines = Machine::with('station')->get();
        $materials = Material::all();
        $stations = Station::all();

        $stationStatuses = $stations->map(function ($station) {
            return [
                'id'     => $station->id,
                'name'   => $station->station_name,
                'status' => 'NORMAL', // default
            ];
        });

        // Kirim semua variabel ke view di akhir
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
            'machineHenkatens', // DIUBAH
            'materialHenkatens' // DIUBAH
        ));
    }
}

