<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupConfig extends Model
{
    protected $fillable = [
        'periodic_enabled',
        'interval_hours',
        'last_run_at',
        'next_run_at',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'periodic_enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
