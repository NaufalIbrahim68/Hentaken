<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Tambahkan ini

class MachineHenkaten extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'machine_henkaten';
    
    // Asumsi: Primary key tetap 'id'
    // protected $primaryKey = 'id'; 

    /**
     * Atribut yang harus di-cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'effective_date' => 'datetime',
        'end_date'       => 'datetime',
        // 'time_start' dan 'time_end' biasanya dibiarkan sebagai string jika hanya menyimpan H:i
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
        'effective_date',
        'end_date',
        'lampiran',
        'serial_number_start',
        'serial_number_end',
        'time_start',
        'time_end',
        'machine', 
        'description_before',
        'description_after',
        'status',
        'note',
        // 🟢 PERBAIKAN 1: Tambahkan kolom foreign key yang benar
        'id_machines',
    ];

    // --------------------------------------------------------
    // RELATIONS
    // --------------------------------------------------------

    /**
     * Relasi ke tabel Station.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }

    /**
     * 🟢 PERBAIKAN 2: Relasi ke tabel Machine.
     * Menghubungkan log Henkaten ke Machine Master.
     */
    public function machine(): BelongsTo
    {
        // Foreign Key: id_machines (di tabel ini), Owner Key: id (di tabel machines)
        return $this->belongsTo(Machine::class, 'id_machines', 'id');
    }
}