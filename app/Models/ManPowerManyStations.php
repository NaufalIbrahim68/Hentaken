<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManPowerManyStations extends Model
{
    protected $table = 'man_power_many_stations';

    protected $fillable = [
        'man_power_id',
        'station_id',
        'status',
    ];

    public function manPower()
    {
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }
}
