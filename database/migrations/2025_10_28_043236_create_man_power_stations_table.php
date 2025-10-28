<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('man_power_stations', function (Blueprint $table) {
            $table->id();

            // ✅ Samakan tipe data dengan tabel man_power dan stations
            $table->integer('man_power_id');
            $table->integer('station_id');

            $table->timestamps();

            // ✅ Tambahkan foreign key dengan tipe yang sama
            $table->foreign('man_power_id')
                ->references('id')
                ->on('man_power')
                ->onDelete('cascade');

            $table->foreign('station_id')
                ->references('id')
                ->on('stations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('man_power_stations');
    }
};
