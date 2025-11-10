<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialHenkaten extends Model
{
    use HasFactory;

    protected $table = 'material_henkaten';

   protected $fillable = [
        'shift',
        'line_area',
        'station_id',
        'material_id', 
        'keterangan',
        'effective_date',
        'end_date',
        'time_start',
        'time_end',
        'lampiran',
        'serial_number_start',
        'serial_number_end',
        'description_before', 
        'description_after',
        'status',
        'notes'
    ];

    protected $casts = [
        'effective_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    // ================= RELASI =================

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id'); // ✅ material sebelum
    }

    
}
