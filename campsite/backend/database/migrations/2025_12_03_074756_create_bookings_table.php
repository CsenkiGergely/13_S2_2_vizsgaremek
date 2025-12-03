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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('camping_id')->constrained('campings')->onDelete('cascade');
            $table->unsignedBigInteger('camping_spot_id');
            $table->foreign('camping_spot_id')->references('spot_id')->on('camping_spots')->onDelete('cascade');
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->string('status', 30)->nullable();
            $table->string('qr_code', 255)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('camping_id');
            $table->index(['camping_spot_id', 'arrival_date', 'departure_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
