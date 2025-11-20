<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_henkaten', function (Blueprint $table) {
            // Tentukan ID user default untuk baris yang sudah ada. 
            // Ganti 1 dengan ID user yang valid (misalnya, ID user Admin).
            $table->foreignId('user_id')->default(1)->constrained('users')->after('line_area'); 
        });

        // Hapus default value setelah kolom ditambahkan
        Schema::table('material_henkaten', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(null)->change();
        });
        
        // Catatan: Jika Anda ingin kolom tetap NOT NULL, hapus langkah kedua di atas.
        // Jika Anda ingin kolom menjadi NOT NULL, pastikan semua baris sudah terisi ID user yang benar.

    }

    public function down(): void
    {
        Schema::table('material_henkaten', function (Blueprint $table) {
            $table->dropForeign(['user_id']); 
            $table->dropColumn('user_id');
        });
    }
};