<?php

namespace App\Application\DTOs\Admin\Authorization;

class RoleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $description,
        public readonly bool $is_active,
        public readonly array $permissions = [],
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,    
    ) {}
}