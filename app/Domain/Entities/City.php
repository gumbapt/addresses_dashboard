<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Geographic\CityDto;

class City
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $stateId,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?int $population = null,
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

    public function getStateId(): int
    {
        return $this->stateId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toDto(): CityDto
    {
        return new CityDto(
            id: $this->id,
            name: $this->name,
            state_id: $this->stateId,
            latitude: $this->latitude,
            longitude: $this->longitude,
            population: $this->population,
            is_active: $this->isActive
        );
    }
}

