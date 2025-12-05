<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Import Model User

class MaterialHenkaten extends Model
{
    use HasFactory;

    protected $table = 'material_henkaten';

    protected $fillable = [
        'user_id', 
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
         'lampiran_2',
    'lampiran_3',
        'serial_number_start',
        'serial_number_end',
        'description_before', 
        'description_after',
        'status',
        'note',
        
    ];

    protected $casts = [
        'effective_date' => 'datetime',
        'end_date' => 'datetime',
    ];


    public function user() // <-- TAMBAHAN: Relasi ke User
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function material()
    {
        // ASUMSI: Anda memiliki Model Material
        return $this->belongsTo(Material::class, 'material_id'); 
    }
}