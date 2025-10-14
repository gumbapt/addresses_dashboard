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
        Schema::create('report_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade'); // FK → cities (normalizado)
            $table->integer('request_count');
            $table->json('zip_codes')->nullable();          // Array de zip codes
            $table->timestamps();
            
            // Indexes
            $table->index(['report_id', 'city_id'], 'idx_report_city');
            $table->index('request_count', 'idx_cities_request_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cities');
    }
};
