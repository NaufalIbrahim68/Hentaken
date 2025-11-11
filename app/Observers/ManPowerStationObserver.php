<?php

namespace App\Observers;

use App\Models\ManPowerStation;
use App\Models\ActivityLog;
use App\Models\ManPower;
use App\Models\Station;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // <-- 1. PASTIKAN INI ADA

class ManPowerStationObserver
{
    /**
     * Handle the ManPowerStation "created" event (saat attach).
     */
    public function created(ManPowerStation $pivot): void
    {
        // ==========================================================
        // 2. INI ADALAH TES KITA
        // ==========================================================
        Log::info('OBSERVER "CREATED" DIPANGGIL!', ['pivot_data' => $pivot->toArray()]);

        try {
            $manPower = $pivot->manPower()->first();
            $station = $pivot->station()->first();

            // Jika $manPower atau $station tidak ditemukan, buat log error
            if (!$manPower || !$station) {
                Log::error('Observer GAGAL: ManPower or Station not found.', [
                    'man_power_id' => $pivot->man_power_id,
                    'station_id' => $pivot->station_id,
                ]);
                return;
            }

            ActivityLog::create([
                'user_id' => Auth::id(), // Auth::id() BISA JADI NULL jika user tidak login
                'loggable_type' => ManPower::class,
                'loggable_id' => $pivot->man_power_id,
                'action' => 'updated',
                'details' => [
                    'message' => "Stasiun '{$station->station_name}' DITAMBAHKAN ke '{$manPower->nama}'.",
                    'changes' => [
                        'station_added' => "{$station->station_name} (ID: {$pivot->station_id})"
                    ]
                ],
            ]);
        
        } catch (\Exception $e) {
            // ==========================================================
            // 3. JIKA ADA ERROR, KITA TANGKAP DAN CATAT
            // ==========================================================
            Log::error('Observer "created" GAGAL MENYIMPAN ActivityLog: ' . $e->getMessage());
        }
    }

    /**
     * Handle the ManPowerStation "deleted" event (saat detach).
     */
    public function deleted(ManPowerStation $pivot): void
    {
        // ==========================================================
        // 2. INI ADALAH TES KITA
        // ==========================================================
        Log::info('OBSERVER "DELETED" DIPANGGIL!', ['pivot_data' => $pivot->toArray()]);
        
        try {
            // Saat 'deleted', data relasi mungkin sudah hilang
            // Jadi kita ambil manual
            $manPower = ManPower::find($pivot->man_power_id);
            $station = Station::find($pivot->station_id);

            if (!$manPower || !$station) {
                Log::error('Observer GAGAL: ManPower or Station not found.', [
                    'man_power_id' => $pivot->man_power_id,
                    'station_id' => $pivot->station_id,
                ]);
                return;
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'loggable_type' => ManPower::class,
                'loggable_id' => $pivot->man_power_id,
                'action' => 'updated',
                'details' => [
                    'message' => "Stasiun '{$station->station_name}' DIHAPUS dari '{$manPower->nama}'.",
                    'changes' => [
                        'station_removed' => "{$station->station_name} (ID: {$pivot->station_id})"
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            // ==========================================================
            // 3. JIKA ADA ERROR, KITA TANGKAP DAN CATAT
            // ==========================================================
            Log::error('Observer "deleted" GAGAL MENYIMPAN ActivityLog: ' . $e->getMessage());
        }
    }
}