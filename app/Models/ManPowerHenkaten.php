<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManPowerHenkaten extends Model
{
    use HasFactory;

    /**
     * Pastikan nama tabel menggunakan schema 'dbo.'
     */
    protected $table = 'dbo.man_power_henkaten';

    /**
     * Nonaktifkan timestamps otomatis.
     * Kita kelola 'created_at' & 'updated_at' secara manual.
     */
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi (mass assignment).
     */
     protected $fillable = [
        'man_power_id',
        'station_id',
        'shift',
        'nama',                  
        'nama_after', 
        'station_id_after', 
        'line_area',
        'effective_date',
        'end_date',
        'keterangan',
        'lampiran',
        'lampiran_2',      
        'lampiran_3',      
        'man_power_id_after',
        'serial_number_start',
        'serial_number_end',
        'time_start',
        'time_end',
        'grup', 
        'status',
        'note',
        'created_at',
        'updated_at', 
    ];

    /**
     * Casting tipe data untuk tanggal dan waktu.
     */
    protected $casts = [
        'effective_date' => 'datetime:Y-m-d',
        'end_date' => 'datetime:Y-m-d',
        'time_start' => 'datetime:H:i',
        'time_end' => 'datetime:H:i',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Relasi ke ManPower sebelum henkaten (yang digantikan).
     */
    public function manPowerBefore(): BelongsTo
    {
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }

    /**
     * Relasi ke ManPower setelah henkaten (yang menggantikan).
     */
    public function manPowerAfter(): BelongsTo
    {
        return $this->belongsTo(ManPower::class, 'man_power_id_after');
    }

    /**
     * Relasi ke Station sebelum henkaten.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    /**
     * Alias relasi ke ManPower (umum).
     */
    public function manPower(): BelongsTo
    {
        return $this->belongsTo(ManPower::class, 'man_power_id');
    }
}
