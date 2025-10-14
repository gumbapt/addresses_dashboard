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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->date('report_date');                    // 2025-10-11
            $table->timestamp('report_period_start');
            $table->timestamp('report_period_end');
            $table->timestamp('generated_at');
            $table->integer('total_processing_time')->default(0);
            $table->string('data_version', 20);             // 2.0.0
            $table->json('raw_data');                       // JSON completo original
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['domain_id', 'report_date'], 'idx_domain_date');
            $table->index('status', 'idx_status');
            $table->index('generated_at', 'idx_generated_at');
            $table->index('report_date', 'idx_report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
