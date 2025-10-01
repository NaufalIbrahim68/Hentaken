<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPowerHenkaten extends Model
{
    use HasFactory;

    // Tetapkan nama tabel secara eksplisit
    protected $table = 'man_power_henkaten';

    protected $fillable = [
        'man_power_id',
        'station_id',
        'shift',
        'nama',
        'line_area',
        'man_power_id_after',
        'station_id_after',
        'effective_date',
        'end_date',
        'keterangan',
    ];

    // Jika ada relasi
    public function manPower()
    {
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

  
}