<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPower extends Model
{
    use HasFactory;

    protected $table = 'man_power'; // nama tabel di SQL Server

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    protected $fillable = [
        'id',
        'nama',
        'station_id',
        'shift',
        'line_area',
        'created_at',
        'updated_at',
    ];
}
