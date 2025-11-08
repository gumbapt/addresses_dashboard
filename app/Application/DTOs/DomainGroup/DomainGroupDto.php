<?php

namespace App\Application\DTOs\DomainGroup;

use DateTime;

class DomainGroupDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description = null,
        public readonly bool $is_active = true,
        public readonly ?array $settings = null,
        public readonly ?int $max_domains = null,
        public readonly ?int $created_by = null,
        public readonly ?int $updated_by = null,
        public readonly ?DateTime $created_at = null,
        public readonly ?DateTime $updated_at = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'settings' => $this->settings,
            'max_domains' => $this->max_domains,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

