<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('man_power', function (Blueprint $table) {
            // Menambahkan kolom created_at dan updated_at
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('man_power', function (Blueprint $table) {
            // Menghapus kolom jika migration di-rollback
            $table->dropTimestamps();
        });
    }
};
