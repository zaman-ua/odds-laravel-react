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
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('sport_key', 120)->unique();
            $table->string('group_name', 120);
            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->boolean('active')->default(true);
            $table->boolean('has_outrights')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
