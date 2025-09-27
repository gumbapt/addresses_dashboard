<?php

namespace App\Domain\Entities;

class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $display_name,
        public readonly bool $is_active,
        public readonly string $resource,
        public readonly string $action
    ) {}

}  