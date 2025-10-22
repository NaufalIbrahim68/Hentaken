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
use App\Models\MachineHenkaten;  // Diasumsikan Anda memiliki model ini
use App\Models\MaterialHenkaten; // Diambil dari konteks sebelumnya
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan data dashboard utama.
     */
    public function index()
    {
        // ================= 1. SETUP WAKTU & SHIFT =================
        $now = Carbon::now();
        $time = $now->format('H:i');

        // TENTUKAN NILAI SHIFT SESUAI DATABASE ANDA
        $shiftA_Value = 'Shift A';
        $shiftB_Value = 'Shift B';

        // Shift B: 07:00 - 19:00
        if ($time >= '07:00' && $time < '19:00') {
            $currentShift = $shiftB_Value;
        } else {
            // Waktu di luar itu dianggap Shift A
            $currentShift = $shiftA_Value;
        }

        // ================= 2. PENGAMBILAN DATA HENKATEN AKTIF =================
        
        // Kueri dasar untuk menemukan henkaten yang 'sedang aktif'
        // (dimulai di masa lalu/sekarang, DAN belum berakhir / tidak punya tanggal akhir)
        $baseHenkatenQuery = function ($query) use ($now) {
            $query->where('effective_date', '<=', $now)
                  ->where(function ($subQuery) use ($now) {
                      $subQuery->where('end_date', '>=', $now)
                               ->orWhereNull('end_date');
                  });
        };

        // Ambil Henkaten Man Power yang aktif
        $activeManPowerHenkatens = ManPowerHenkaten::with('station')
            ->where($baseHenkatenQuery)
            ->latest('effective_date')
            ->get();

        // Ambil Henkaten Method yang aktif
        $activeMethodHenkatens = MethodHenkaten::with('station')
            ->where($baseHenkatenQuery)
            ->latest('effective_date')
            ->get();
            
        // Ambil Henkaten Machine yang aktif (MEMPERBAIKI LOGIKA)
        // Asumsi nama model adalah 'MachineHenkaten'
        $machineHenkatens = $activeManPowerHenkatens;

        // Ambil Henkaten Material yang aktif (MEMPERBAIKI ERROR)
        $materialHenkatens = MaterialHenkaten::with('station') // <-- BUG FIX: Menggunakan Model
            ->where($baseHenkatenQuery) // <-- BUG FIX: Menggunakan $now (via closure)
            ->orderBy('effective_date', 'desc')
            ->get();


        // ================= 3. PENGAMBILAN DATA MASTER =================
        $manPower = ManPower::with('station')->get();
        $methods = Method::with('station')->paginate(5);
        $machines = Machine::with('station')->get();
        $materials = Material::all();
        $stations = Station::all();


        // ================= 4. SINKRONISASI DATA MAN POWER =================
        // Dapatkan semua ID Man Power yang terlibat di henkaten (sebelum & sesudah)
        $henkatenManPowerIds = $activeManPowerHenkatens
            ->pluck('man_power_id')
            ->merge($activeManPowerHenkatens->pluck('man_power_id_after'))
            ->filter() // Hapus nilai null/kosong
            ->unique() // Ambil ID unik
            ->toArray();

        // Tandai status Man Power berdasarkan henkaten
        foreach ($manPower as $person) {
            if (in_array($person->id, $henkatenManPowerIds)) {
                $person->setAttribute('status', 'Henkaten');
            } else {
                $person->setAttribute('status', 'NORMAL');
            }
        }

        // ================= 5. PERSIAPAN DATA UNTUK VIEW =================
        $groupedManPower = $manPower->groupBy('station_id');

        // Siapkan status default untuk setiap stasiun
       $stationStatuses = $stations->map(function ($station) {
    // Ambil material terkait station (sesuaikan dengan relasi Anda)
    $material = $station->material; // atau $station->materials()->first() jika many-to-many
    
    return [
        'id'            => $station->id,
        'name'          => $station->station_name,
        'status'        => 'NORMAL', // default
        'material_name' => $material ? $material->material_name : null,
        'is_henkaten'   => $material ? $material->is_henkaten : false, // atau field lain yang menandakan henkaten
    ];
});
        
        // ================= 6. KIRIM SEMUA DATA KE VIEW =================
        
        // BUG FIX: Menghapus return view() yang salah tempat 
        // dan menggabungkan semua variabel di sini.
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