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
        Schema::create('report_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->integer('total_requests');
            $table->decimal('success_rate', 5, 2);
            $table->integer('failed_requests');
            $table->decimal('avg_requests_per_hour', 10, 2);
            $table->integer('unique_providers')->default(0);
            $table->integer('unique_states')->default(0);
            $table->integer('unique_zip_codes')->default(0);
            $table->timestamps();
            
            // Unique constraint - one summary per report
            $table->unique('report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_summaries');
    }
};
