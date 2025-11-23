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
        Schema::create('report_state_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->string('original_name'); // Nome original do provider
            $table->integer('request_count'); // Requests REAIS deste provider neste estado
            $table->decimal('success_rate', 5, 2)->nullable()->default(0);
            $table->decimal('avg_speed', 10, 2)->nullable()->default(0);
            $table->timestamps();
            
            // Indexes para performance
            $table->index(['report_id', 'state_id', 'provider_id'], 'idx_report_state_provider');
            $table->index(['state_id', 'provider_id'], 'idx_state_provider');
            $table->index('request_count', 'idx_request_count');
            
            // Evitar duplicatas
            $table->unique(['report_id', 'state_id', 'provider_id'], 'unique_report_state_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_state_providers');
    }
};

