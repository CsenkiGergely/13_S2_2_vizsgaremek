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
        Schema::create('camping_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camping_id')->constrained('campings')->onDelete('cascade');
            $table->string('tag', 50);
            $table->timestamps();

            $table->unique(['camping_id', 'tag']);
            $table->index('camping_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camping_tags');
    }
};
