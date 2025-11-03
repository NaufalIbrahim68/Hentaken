<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineHenkaten extends Model
{
    use HasFactory;

    protected $table = 'machine_henkaten';

    protected $fillable = [
        'station_id',
        'shift',
        'keterangan',
        'line_area',
        'created_at',
        'effective_date',
        'end_date',
        'updated_at',
        'lampiran',
        'serial_number_start',
        'serial_number_end',
        'time_start',
        'time_end',
        'machine',
        'description_before',
        'description_after',
    ];

    // Jika ingin relasi ke tabel Station
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }
}
