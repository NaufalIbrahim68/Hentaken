<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManPowerHenkaten extends Model
{
    use HasFactory;
    
    protected $table = 'man_power_henkaten'; // Sesuaikan nama tabel

    protected $fillable = [
        'man_power_id',
        'station_id',
        'shift',
        'nama',                  // nama_before
        'nama_after',   
        'station_id_after', 
        'line_area',
        'effective_date',
        'end_date',
        'keterangan',
        'lampiran',
        'man_power_id_after',
        'serial_number_start',
        'serial_number_end',
        'time_start',
        'time_end',
        'grup', 
        'status',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'time_start' => 'datetime:H:i',
        'time_end' => 'datetime:H:i',
    ];

    /**
     * Relasi ke ManPower sebelum henkaten (pekerja yang diganti).
     */
    public function manPowerBefore(): BelongsTo
    {
        // Relasi ini menghubungkan kolom 'man_power_id' di tabel ini
        // ke 'id' di tabel 'man_power'.
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }

    /**
     * Relasi ke ManPower setelah henkaten (pekerja pengganti).
     */
    public function manPowerAfter(): BelongsTo
    {
        return $this->belongsTo(ManPower::class, 'man_power_id_after');
    }

    /**
     * Relasi ke Station sebelum henkaten (station awal).
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function manPower()
{
    return $this->belongsTo(ManPower::class, 'man_power_id');
}

}
