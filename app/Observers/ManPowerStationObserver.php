<?php

namespace App\Observers;

use App\Models\ManPowerStation;
use App\Models\MasterDataLog;
use App\Models\ManPower;
use App\Models\Station;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // <-- 1. PASTIKAN INI ADA

class ManPowerStationObserver
{
    /**
     * Handle the ManPowerStation "created" event (saat attach).
     */
    public function created($pivot): void
    {
        Log::info('OBSERVER "CREATED" DIPANGGIL!', ['pivot_data' => $pivot->toArray()]);

        try {
            $manPower = ManPower::find($pivot->man_power_id);
            $station = Station::find($pivot->station_id);

            if (!$manPower || !$station) return;

            MasterDataLog::create([
                'user_id' => Auth::id(),
                'loggable_type' => ManPower::class,
                'loggable_id' => $pivot->man_power_id,
                'action' => 'updated',
                'details' => [
                    'message' => "Stasiun '{$station->station_name}' DITAMBAHKAN ke '{$manPower->nama}'.",
                    'nama' => $manPower->nama,
                    'grup' => $manPower->grup,
                    'line_area' => $manPower->line_area,
                    'status' => $pivot->status ?? 'PENDING',
                    'single_station' => $station->station_name,
                    'station_action' => 'Added',
                    'changes' => [
                        'station_added' => [
                            'old' => '-',
                            'new' => $station->station_name
                        ]
                    ]
                ],
            ]);
        
        } catch (\Exception $e) {
            Log::error('Observer "created" GAGAL: ' . $e->getMessage());
        }
    }

    /**
     * Handle the ManPowerStation "updated" event.
     */
    public function updated($pivot): void
    {
        Log::info('OBSERVER "UPDATED" DIPANGGIL!', ['pivot_data' => $pivot->toArray()]);

        if (!$pivot->isDirty('status')) return;

        try {
            $manPower = ManPower::find($pivot->man_power_id);
            $station = Station::find($pivot->station_id);

            if (!$manPower || !$station) return;

            $oldStatus = $pivot->getOriginal('status');
            $newStatus = $pivot->status;

            MasterDataLog::create([
                'user_id' => Auth::id(),
                'loggable_type' => ManPower::class,
                'loggable_id' => $pivot->man_power_id,
                'action' => 'updated',
                'details' => [
                    'message' => "Status stasiun '{$station->station_name}' berubah menjadi {$newStatus}.",
                    'nama' => $manPower->nama,
                    'grup' => $manPower->grup,
                    'line_area' => $manPower->line_area,
                    'status' => $newStatus,
                    'single_station' => $station->station_name,
                    'station_action' => 'Status Updated',
                    'changes' => [
                        "status_{$station->station_name}" => [
                            'old' => $oldStatus,
                            'new' => $newStatus
                        ]
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Observer "updated" GAGAL: ' . $e->getMessage());
        }
    }

    /**
     * Handle the ManPowerStation "deleted" event (saat detach).
     */
    public function deleted($pivot): void
    {
        Log::info('OBSERVER "DELETED" DIPANGGIL!', ['pivot_data' => $pivot->toArray()]);
        
        try {
            $manPower = ManPower::find($pivot->man_power_id);
            $station = Station::find($pivot->station_id);

            if (!$manPower || !$station) return;

            MasterDataLog::create([
                'user_id' => Auth::id(),
                'loggable_type' => ManPower::class,
                'loggable_id' => $pivot->man_power_id,
                'action' => 'updated',
                'details' => [
                    'message' => "Stasiun '{$station->station_name}' DIHAPUS.",
                    'nama' => $manPower->nama,
                    'grup' => $manPower->grup,
                    'line_area' => $manPower->line_area,
                    'status' => $pivot->status ?? '-',
                    'single_station' => $station->station_name,
                    'station_action' => 'Removed',
                    'changes' => [
                        'station_removed' => [
                            'old' => $station->station_name,
                            'new' => '-'
                        ]
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Observer "deleted" GAGAL: ' . $e->getMessage());
        }
    }
}
