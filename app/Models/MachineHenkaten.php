<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineHenkaten extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'machine_henkaten';

    /**
     * Atribut yang harus di-cast.
     * INI ADALAH PERBAIKAN UNTUK ERROR "format() on string".
     *
     * @var array<string, string>
     */
    protected $casts = [
        'effective_date' => 'datetime',
        'end_date'       => 'datetime',
    ];

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'station_id',
        'shift',
        'keterangan',
        'line_area',
        // 'created_at' & 'updated_at' dihapus dari sini karena diurus otomatis
        'effective_date',
        'end_date',
        'lampiran',
        'serial_number_start',
        'serial_number_end',
        'time_start',
        'time_end',
        'machine', // Ini adalah kolom Teks (String) Kategori Henkaten
        'description_before',
        'description_after',
    ];

    /**
     * Relasi ke tabel Station.
     */
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }
}

