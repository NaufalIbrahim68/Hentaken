<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManPowerHenkaten extends Model
{
    protected $table = 'man_power_henkaten'; // Sesuaikan nama tabel

    protected $fillable = [
        'man_power_id',
        'station_id',
        'shift',
        'nama',              // nama_before
        'line_area',
        'nama_after',   
        'station_id_after',
        'effective_date',
        'end_date',
        'keterangan',
        'lampiran',
        'man_power_id_after',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
    ];
}