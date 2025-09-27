<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;

class Role
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly bool $is_active,
        public readonly array $permissions = [],
       
    ) {}

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission->id, $this->permissions);
    }

    public function canAccess(string $resource, string $action): bool
    {
        return $this->hasPermission(new Permission($resource, $action));
    }
}
