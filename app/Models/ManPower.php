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
    'man_power_id',
    'man_power_id_after',
    'shift',
    'nama',
    'nama_after',
    'keterangan',
    'line_area',
    'station_id', 
    'effective_date',
    'end_date',
    'lampiran',
];

}
