<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\ManPower;
use App\Models\Station;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManPowerStation extends Pivot
{
    protected $table = 'man_power_stations';

    public $timestamps = false;

    public function manPower(): BelongsTo
    {
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }
}