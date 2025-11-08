<?php

namespace App\Domain\Entities;

use DateTime;

class DomainGroup
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
        public readonly ?array $settings = null,
        public readonly ?int $maxDomains = null,
        public readonly ?int $createdBy = null,
        public readonly ?int $updatedBy = null,
        public readonly ?DateTime $createdAt = null,
        public readonly ?DateTime $updatedAt = null,
    ) {}

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'settings' => $this->settings,
            'max_domains' => $this->maxDomains,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Converte para DTO
     */
    public function toDto(): \App\Application\DTOs\DomainGroup\DomainGroupDto
    {
        return new \App\Application\DTOs\DomainGroup\DomainGroupDto(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            is_active: $this->isActive,
            settings: $this->settings,
            max_domains: $this->maxDomains,
            created_by: $this->createdBy,
            updated_by: $this->updatedBy,
            created_at: $this->createdAt,
            updated_at: $this->updatedAt,
        );
    }

    /**
     * Verifica se tem limite de domínios
     */
    public function hasMaxDomainsLimit(): bool
    {
        return !is_null($this->maxDomains);
    }

    /**
     * Verifica se é ilimitado
     */
    public function isUnlimited(): bool
    {
        return is_null($this->maxDomains);
    }
}

