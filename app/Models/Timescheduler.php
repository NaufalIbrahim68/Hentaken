<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeScheduler extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'time_scheduler';

    // Kolom yang bisa diisi (fillable)
    protected $fillable = [
        'tanggal_mulai',
        'waktu_mulai',
        'tanggal_berakhir',
        'waktu_berakhir',
        'shift',
        'grup',
    ];

    // Format tanggal otomatis oleh Laravel
    protected $dates = [
        'tanggal_mulai',
        'tanggal_berakhir',
        'created_at',
        'updated_at',
    ];

    // Relasi ke model ManPower (One-to-Many)
    public function manPowers()
    {
        return $this->hasMany(ManPower::class, 'time_scheduler_id');
    }

    // Accessor opsional untuk format tanggal (jika ingin ditampilkan rapi di blade)
    public function getTanggalMulaiFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->tanggal_mulai)->format('d-m-Y');
    }

    public function getTanggalBerakhirFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->tanggal_berakhir)->format('d-m-Y');
    }
}
