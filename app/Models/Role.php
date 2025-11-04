<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    // Nama tabel di database (jika bukan plural otomatis)
    protected $table = 'roles';

    // Kolom yang bisa diisi massal (mass assignable)
    protected $fillable = [
        'name',
        'description',
    ];

    // Jika kamu punya relasi, contoh:
    // public function users() {
    //     return $this->hasMany(User::class);
    // }
}
