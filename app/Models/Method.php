<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Method extends Model
{
    use HasFactory;

    protected $table = 'methods'; // nama tabel di SQL Server

    protected $fillable = [
        'name',
        'station_id',
        'shift',
        'active_from',
        'active_to',
    ];
}
