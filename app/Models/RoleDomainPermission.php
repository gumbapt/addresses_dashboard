<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleDomainPermission extends Model
{
    protected $fillable = [
        'role_id',
        'domain_id',
        'can_view',
        'can_edit',
        'can_delete',
        'can_submit_reports',
        'assigned_at',
        'assigned_by',
        'is_active',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_submit_reports' => 'boolean',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }
}

