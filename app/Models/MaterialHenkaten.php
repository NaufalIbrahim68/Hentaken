<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialHenkaten extends Model
{
    use HasFactory;

    protected $table = 'material_henkaten';

    protected $fillable = [
        'station_id',
        'shift',
        'keterangan',
        'line_area',
        'effective_date',
        'end_date',
        'lampiran',
        'serial_number_start',
        'serial_number_end',
        'time_start',
        'time_end',
        'material_name',
        'material_after',
        'material_id',          // ✅ tambahkan ini
        'material_id_after',    // ✅ tambahkan ini
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

    public function materialAfter()
    {
        return $this->belongsTo(Material::class, 'material_id_after'); // ✅ material sesudah
    }
}
