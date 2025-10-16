<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPower;
use App\Models\Method;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Station;
use App\Models\ManPowerHenkaten;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Tentukan Shift Secara Dinamis berdasarkan waktu saat ini
        $now = Carbon::now();
        $time = $now->format('H:i');
        $currentShift = 'Istirahat'; // Nilai default untuk jam istirahat/pergantian shift

        // Shift B (sebelumnya Shift 2): dimulai dari pukul 07.00 sampai 19.00
        if ($time >= '07:00' && $time <= '19:00') {
            $currentShift = 'Shift B';
        }
        // Shift A (sebelumnya Shift 1): dimulai dari pukul 19.30 sampai 06.30
        else if ($time >= '19:30' || $time <= '06:30') {
            $currentShift = 'Shift A';
        }

        // ================= SUMBER DATA HENKATEN =================
        // Ambil Henkaten yang belum berakhir, tanpa peduli kapan mulainya
        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                    ->orWhereNull('end_date');
            })
            ->latest('effective_date')
            ->get();

        // ================= PENGGUNAAN DATA HENKATEN UNTUK SEMUA SEKSI =================
        $methodHenkatens   = $activeManPowerHenkatens;
        $machineHenkatens  = $activeManPowerHenkatens;
        $materialHenkatens = $activeManPowerHenkatens;

        // ================= PENGAMBILAN & SINKRONISASI DATA MAN POWER =================
        // Ambil semua Man Power HANYA SEKALI
        $manPower = ManPower::with('station')->get();

        // Buat daftar ID Man Power yang sedang dalam proses Henkaten
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

        // ================= PERSIAPAN DATA UNTUK VIEW (SETELAH LOOP) =================
        // DIPINDAHKAN KELUAR DARI LOOP: Lakukan grouping SETELAH semua status diperbarui
        $groupedManPower = $manPower->groupBy('station_id');

        // DIPINDAHKAN KELUAR DARI LOOP: Ambil data lain hanya sekali
        $methods = Method::with('station')->paginate(5);
        $machines = Machine::with('station')->get();
        $materials = Material::all();
        $stations  = Station::all();

        $stationStatuses = $stations->map(function ($station) {
            return [
                'id'     => $station->id,
                'name'   => $station->station_name,
                'status' => 'NORMAL', // default
            ];
        });

        // DIPINDAHKAN KELUAR DARI LOOP: Kirim semua variabel ke view di akhir
        return view('dashboard.index', compact(
            'groupedManPower',
            'currentShift',
            'methods',
            'machines',
            'materials',
            'stations',
            'stationStatuses',
            'activeManPowerHenkatens',
            'methodHenkatens',
            'machineHenkatens',
            'materialHenkatens'
        ));
    }
}

