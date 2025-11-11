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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Siapa yang melakukan
            $table->string('loggable_type'); // Nama Model (cth: App\Models\ManPower)
            $table->unsignedBigInteger('loggable_id'); // ID dari model tsb (cth: 10)
            $table->string('action'); // cth: 'created', 'updated', 'deleted'
            $table->json('details')->nullable(); // Untuk menyimpan data lama vs data baru
            $table->timestamps();

            // Index untuk mempercepat query
            $table->index(['loggable_type', 'loggable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};