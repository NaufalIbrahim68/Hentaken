<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('man_power_henkaten', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('nama_after'); 
            // 'after' bisa diganti sesuai posisi kolom yang diinginkan
        });
    }

    public function down(): void
    {
        Schema::table('man_power_henkaten', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
