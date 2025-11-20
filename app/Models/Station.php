<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $table = 'stations'; // sesuai tabel SQL Server

    protected $fillable = [
        'station_name',
         'station_code',
    ];

    public function materials()
    {
        return $this->hasMany(Material::class, 'station_id');
    }

    public function manPowers()
{
    return $this->belongsToMany(
        ManPower::class,
        'man_power_many_stations',
        'station_id',
        'man_power_id'
    );
}

}
