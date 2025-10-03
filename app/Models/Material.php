<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials'; 

    protected $fillable = [
        'material_name', // 👈 harus sesuai kolom di tabel kamu
        'station_id',
        'keterangan',
        'foto_path',
        'status',
    ];

    // Relasi ke Station
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }
}
