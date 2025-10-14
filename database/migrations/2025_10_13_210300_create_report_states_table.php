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
        Schema::create('report_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade'); // FK → states (normalizado)
            $table->integer('request_count');
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('avg_speed', 10, 2)->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['report_id', 'state_id'], 'idx_report_state');
            $table->index('request_count', 'idx_states_request_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_states');
    }
};
