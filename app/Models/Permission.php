<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /** @use HasFactory<\Database\Factories\PermissionFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'resource',
        'action',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function toEntity(): \App\Domain\Entities\Permission
    {
        return new \App\Domain\Entities\Permission(
            id: $this->id,
            slug: $this->slug,
            name: $this->name,
            description: $this->description,
            is_active: $this->is_active,
            resource: $this->resource,
            action: $this->action
        );
    }
}
