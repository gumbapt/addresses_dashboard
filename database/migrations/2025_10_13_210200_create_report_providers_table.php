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
        Schema::create('report_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade'); // FK → providers (normalizado)
            $table->string('original_name');                // Nome original no JSON
            $table->string('technology', 50);               // Mobile, Fiber, etc.
            $table->integer('total_count');
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('avg_speed', 10, 2)->default(0);
            $table->integer('rank_position')->nullable();   // posição no top_providers
            $table->timestamps();
            
            // Indexes
            $table->index(['report_id', 'provider_id'], 'idx_report_provider');
            $table->index('technology', 'idx_technology');
            $table->index('rank_position', 'idx_rank');
            $table->index('total_count', 'idx_total_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_providers');
    }
};
