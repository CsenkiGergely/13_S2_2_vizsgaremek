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
        Schema::create('camping_photos', function (Blueprint $table) {
            $table->id('photo_id');
            $table->foreignId('camping_id')->constrained('campings')->onDelete('cascade');
            $table->string('photo_url', 255);
            $table->string('caption', 255)->nullable();
            $table->date('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camping_photos');
    }
};
