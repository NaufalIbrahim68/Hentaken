<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MethodHenkaten extends Model
{
    use HasFactory;
protected $table = 'methods_henkaten';
    protected $fillable = [
    'shift',
    'keterangan',
    'keterangan_after',
    'station_id',
    'method_id',
    'methods_name',
    'line_area',
    'effective_date',
    'end_date',
    'lampiran',
    'lampiran_2',
    'lampiran_3',
    'time_start',
    'time_end',
    'serial_number_start',
    'serial_number_end',
    'status',
    'note',
];

    protected $casts = [
        'effective_date' => 'datetime',
        'end_date'       => 'datetime',
        ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function method()
    {
        return $this->belongsTo(Method::class, 'method_id');
    }

    public function methodAfter()
    {
        return $this->belongsTo(Method::class, 'method_id_after');
    }


}
