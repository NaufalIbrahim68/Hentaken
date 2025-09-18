<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $table = 'machines'; // nama tabel di SQL Server

    protected $fillable = [
        'name',
        'station_id',
        'shift',
        'active_from',
        'active_to',
    ];
}
