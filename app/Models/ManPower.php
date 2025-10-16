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
        'shift',
        'nama',
        'line_area',
        'status', // Kolom status yang penting
    ];

    /**
     * Relasi ke model Station.
     */
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}