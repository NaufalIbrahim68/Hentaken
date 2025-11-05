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
          'waktu_mulai',
          'tanggal_mulai',

    ];

    /**
     * Relasi ke model Station.
     */
public function station()
{
    return $this->belongsTo(Station::class, 'station_id', 'id');
}

    



public function troubleshooting()
{
    return $this->belongsTo(Troubleshooting::class, 'troubleshooting_id', 'id');
}


}
