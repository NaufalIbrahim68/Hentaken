<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- PENTING: Import DB Facade

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Langkah 1: Ubah struktur kolom untuk menambahkan default value
        Schema::table('materials', function (Blueprint $table) {
            // Mengubah kolom 'status' menjadi string dengan panjang 50,
            // dan mengatur nilai default-nya menjadi 'normal'.
            // Method ->change() memerlukan package doctrine/dbal
            $table->string('status', 50)->default('normal')->change();
        });

        // Langkah 2: Update semua baris yang statusnya masih NULL
        DB::table('materials')
            ->whereNull('status')
            ->update(['status' => 'normal']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('materials', function (Blueprint $table) {
            // Mengembalikan kolom seperti semula jika migrasi di-rollback
            $table->string('status', 50)->nullable()->default(null)->change();
        });
    }
};
