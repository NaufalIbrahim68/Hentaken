<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManPowerHenkaten extends Model
{
    protected $table = 'man_power_henkaten'; // Sesuaikan nama tabel

    protected $fillable = [
        'man_power_id',
        'station_id',
        'shift',
        'nama',              // nama_before
        'line_area',
        'nama_after',   
        'station_id_after', 
        'effective_date',
        'end_date',
        'keterangan',
        'lampiran',
        'man_power_id_after',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

       public function manPowerBefore(): BelongsTo
    {
        // Relasi ini menghubungkan kolom 'man_power_id' di tabel ini
        // ke 'id' di tabel 'man_power'.
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }

    /**
     * Opsional: Mendapatkan data ManPower pengganti (setelah henkaten).
     */
    public function manPowerAfter(): BelongsTo
    {
        return $this->belongsTo(ManPower::class, 'man_power_id_after');
    }
}