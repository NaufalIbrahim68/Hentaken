<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ManPowerManyStation extends Pivot
{
    protected $table = 'man_power_many_stations'; // tabel SQL Server

    protected $fillable = [
        'man_power_id',
        'station_id',
        'status'
    ];

    // Relasi ke ManPower
    public function manPower()
    {
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }

    // Relasi ke Station
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }
}
