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
        Schema::create('admin_domain_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->foreignId('domain_group_id')->constrained('domain_groups')->onDelete('cascade');
            $table->dateTime('assigned_at');
            $table->foreignId('assigned_by')->constrained('admins')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Evitar duplicatas: um admin não pode ter o mesmo grupo duas vezes
            $table->unique(['admin_id', 'domain_group_id'], 'unique_admin_domain_group');
            
            // Indexes para performance
            $table->index(['admin_id', 'is_active'], 'idx_admin_active');
            $table->index(['domain_group_id', 'is_active'], 'idx_domain_group_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_domain_groups');
    }
};

