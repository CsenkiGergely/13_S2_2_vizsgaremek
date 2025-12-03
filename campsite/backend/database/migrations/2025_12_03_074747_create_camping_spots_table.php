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
        Schema::create('camping_spots', function (Blueprint $table) {
            $table->id('spot_id');
            $table->foreignId('camping_id')->constrained('campings')->onDelete('cascade');
            $table->string('name', 100)->nullable();
            $table->string('type', 50);
            $table->integer('capacity');
            $table->integer('price_per_night');
            $table->boolean('is_available')->default(true);
            $table->text('description')->nullable();
            $table->integer('row')->nullable();
            $table->integer('column')->nullable();
            $table->float('rating')->nullable();
            $table->timestamps();

            $table->index('camping_id');
            $table->unique(['camping_id', 'row', 'column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camping_spots');
    }
};
