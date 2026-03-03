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
        Schema::create('entrance_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camping_id')->constrained('campings')->onDelete('cascade');
            $table->string('name', 100)->nullable();
            $table->string('auth_token', 16)->nullable()->unique();
            $table->dateTime('timestamp');
            $table->integer('gate_id')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->timestamps();

            $table->index(['camping_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrance_gates');
    }
};
