<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('man_power_henkaten', function (Blueprint $table) {
            $table->dropColumn('station_id_after'); // ✅ hapus kolom ini
        });
    }

    public function down(): void
    {
        Schema::table('man_power_henkaten', function (Blueprint $table) {
            $table->unsignedBigInteger('station_id_after')->nullable(); // rollback kalau perlu
        });
    }
};
