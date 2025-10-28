<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPower extends Model
{
    use HasFactory;

    // Pastikan nama tabel sudah benar
    protected $table = 'man_power';

    /**
     * Kolom yang BOLEH diisi secara massal.
     * Ini adalah kolom asli dari tabel man_power.
     */
    protected $fillable = [
        'station_id',
        'grup', // <-- WAJIB DITAMBAHKAN
        'shift',
        'nama',
        'line_area',
        'status', // Kolom status yang penting
          'time_scheduler_id', 

    ];

    /**
     * Relasi ke model Station.
     */
    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function timeScheduler()
{
    return $this->belongsTo(TimeScheduler::class, 'time_scheduler_id');
}

public function stations()
{
    return $this->belongsToMany(Station::class, 'man_power_stations', 'man_power_id', 'station_id');
}


}
