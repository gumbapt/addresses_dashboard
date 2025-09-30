<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Entities\Role as RoleEntity;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function toEntity(): RoleEntity
    {
        $permissions = $this->permissions()->get();
        if($permissions->count() > 0){
        $permissionsEntities = [];
        $permissions->map(function ($permission) use (&$permissionsEntities) {
                $permissionsEntities[] = $permission->toEntity();
            });
            return new RoleEntity(
                id: $this->id,
                slug: $this->slug,
                name: $this->name,
                description: $this->description,
                is_active: $this->is_active,
                created_at: $this->created_at,
                updated_at: $this->updated_at,
            );
        }
        return new RoleEntity(
            id: $this->id,
            slug: $this->slug,
            name: $this->name,
            description: $this->description,
            is_active: $this->is_active,
            created_at: $this->created_at,
            updated_at: $this->updated_at,
        );
    }
}
