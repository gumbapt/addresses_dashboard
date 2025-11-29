<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminDomainGroup extends Pivot
{
    protected $table = 'admin_domain_groups';

    public $incrementing = true;

    protected $fillable = [
        'admin_id',
        'domain_group_id',
        'assigned_at',
        'assigned_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * Relacionamento: Admin que possui acesso ao grupo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Relacionamento: Grupo de domínios
     */
    public function domainGroup(): BelongsTo
    {
        return $this->belongsTo(DomainGroup::class);
    }

    /**
     * Relacionamento: Admin que atribuiu o acesso
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    /**
     * Scope: Apenas associações ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
