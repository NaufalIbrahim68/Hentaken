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
        'name',
        'station_id',
        'shift',
        'active_from',
        'active_to',
    ];
}
