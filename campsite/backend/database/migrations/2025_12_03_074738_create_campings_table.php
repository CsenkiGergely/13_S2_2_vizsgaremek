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
        Schema::create('campings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('camping_name', 200);
            $table->string('owner_first_name', 100)->nullable();
            $table->string('owner_last_name', 100)->nullable();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('tax_id', 15)->nullable();
            $table->string('billing_address', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campings');
    }
};
