<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- 1. IMPORT CLASS INI

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials';

    protected $fillable = [
        'material_name',
        'station_id',
        'keterangan',
        'foto_path',
        'status',
    ];

    /**
     * Mendefinisikan relasi ke model Station.
     * Sebuah Material "dimiliki oleh" (belongs to) satu Station.
     */
    public function station(): BelongsTo // <-- 2. TAMBAHKAN RETURN TYPE `: BelongsTo`
    {
        // 'station' adalah NAMA RELASI yang akan kita panggil
        return $this->belongsTo(Station::class, 'station_id');
    }
}