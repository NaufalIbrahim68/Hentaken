<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'machines';

    /**
     * Kolom-kolom yang dapat diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'station_id',
        'deskripsi',
        'keterangan',
        'foto_path',
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model Station.
     * Sebuah mesin (Machine) hanya dimiliki oleh satu stasiun (Station).
     */
    public function station(): BelongsTo
    {
        // Relasi ini menghubungkan 'station_id' di tabel 'machines'
        // dengan 'id' di tabel 'stations'.
        return $this->belongsTo(Station::class, 'station_id');
    }
}