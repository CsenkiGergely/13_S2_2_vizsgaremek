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
        Schema::create('user_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('place_of_birth', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('citizenship', 100)->nullable();
            $table->string('mothers_birth_name', 100)->nullable();
            $table->string('id_card_number', 30)->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->string('visa', 50)->nullable();
            $table->string('resident_permit_number', 50)->nullable();
            $table->date('date_of_entry')->nullable();
            $table->string('place_of_entry', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_guests');
    }
};
