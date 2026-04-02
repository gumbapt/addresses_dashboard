<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 16);
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('trigger_type', 16);
            $table->foreignId('requested_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedInteger('reports_count_at_backup')->default(0);
            $table->string('status', 24)->default('pending');
            $table->text('error_message')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['scope', 'status']);
            $table->index('created_at');
        });

        Schema::create('backup_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_id')->constrained('backups')->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path', 2048);
            $table->string('original_filename', 255);
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });

        Schema::create('backup_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('periodic_enabled')->default(false);
            $table->unsignedInteger('interval_hours')->default(24);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('backup_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action', 64);
            $table->foreignId('backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_audit_logs');
        Schema::dropIfExists('backup_configs');
        Schema::dropIfExists('backup_files');
        Schema::dropIfExists('backups');
    }
};
