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
        Schema::create('report_zip_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('zip_code_id')->constrained('zip_codes')->onDelete('cascade'); // FK → zip_codes (normalizado)
            $table->integer('request_count');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['report_id', 'zip_code_id'], 'idx_report_zip');
            $table->index('request_count', 'idx_zips_request_count');
            $table->index('percentage', 'idx_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_zip_codes');
    }
};
