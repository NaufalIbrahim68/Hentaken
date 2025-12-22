<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 


class ManPower extends Model
{
    use HasFactory;

    // Pastikan nama tabel sudah benar
    protected $table = 'man_power';



    /**
     * Kolom yang BOLEH diisi secara massal.
     * Ini adalah kolom asli dari tabel man_power.
     */
    protected $fillable = [
        'station_id',
        'grup', 
        'shift',
        'nama',
        'line_area',
        'status',
          'waktu_mulai',
          'tanggal_mulai',

    ];


     public function stations(): BelongsToMany
    {
        // Sesuaikan nama-nama ini jika perlu:
        // 1. 'man_power_station' -> Nama tabel pivot Anda
        // 2. 'man_power_id'    -> Foreign key untuk ManPower di tabel pivot
        // 3. 'station_id'       -> Foreign key untuk Station di tabel pivot
        return $this->belongsToMany(Station::class, 'man_power_many_stations', 'man_power_id', 'station_id')
    ->using(ManPowerManyStation::class)
    ->withPivot(['status'])
    ->withTimestamps();
    }

 /**
     * Relasi ke model Station (SINGULAR).
     * Ini mungkin relasi lama atau untuk 'station utama'.
     * Biarkan saja apa adanya.
     */
    public function station(): BelongsTo // <-- UBAH TIPE RETURN JADI BelongsTo
    {
    return $this->belongsTo(Station::class, 'station_id', 'id');
    }


    



public function troubleshooting()
{
    return $this->belongsTo(Troubleshooting::class, 'troubleshooting_id', 'id');
}

public function henkatens()
{
    return $this->hasMany(ManPowerHenkaten::class, 'man_power_id');
}

public function getCurrentNameAttribute()
{
    $today = now()->toDateString();

    $henkaten = $this->henkatens()
                     ->where('effective_date', '<=', $today)
                     ->orderByDesc('effective_date')
                     ->first();

    return $henkaten ? $henkaten->nama_after : $this->nama;
}



}
