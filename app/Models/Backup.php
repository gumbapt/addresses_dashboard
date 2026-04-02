<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backup extends Model
{
    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_DOMAIN = 'domain';

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_AUTOMATIC = 'automatic';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'scope',
        'domain_id',
        'started_at',
        'completed_at',
        'trigger_type',
        'requested_by_admin_id',
        'reports_count_at_backup',
        'status',
        'error_message',
        'notes',
        'size_bytes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(BackupFile::class);
    }
}
