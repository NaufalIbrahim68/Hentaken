<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Troubleshooting extends Model
{
    use HasFactory;

    protected $table = 'troubleshooting';

    protected $fillable = [
        'nama',
        'grup',
        'status',
    ];

    // Jika ingin relasi ke man_power (opsional)
    public function manPowers()
    {
        return $this->hasMany(ManPower::class, 'troubleshooting_id', 'id');
    }
}
