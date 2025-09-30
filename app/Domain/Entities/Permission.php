<?php

namespace App\Domain\Entities;

class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly bool $is_active,
        public readonly string $resource,
        public readonly string $action,
        public readonly ?string $route = null
    ) {}


    public function getId(): int
    {
        return $this->id;
    }
}  