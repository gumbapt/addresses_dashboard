<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Provider\ProviderDto;

class Provider
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $website = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $description = null,
        public readonly array $technologies = [],
        public readonly bool $isActive = true
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTechnologies(): array
    {
        return $this->technologies;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function hasTechnology(string $technology): bool
    {
        return in_array($technology, $this->technologies);
    }

    public function toDto(): ProviderDto
    {
        return new ProviderDto(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            website: $this->website,
            logo_url: $this->logoUrl,
            description: $this->description,
            technologies: $this->technologies,
            is_active: $this->isActive
        );
    }
}
