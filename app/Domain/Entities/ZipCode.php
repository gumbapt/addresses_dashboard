<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Geographic\ZipCodeDto;

class ZipCode
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly int $stateId,
        public readonly ?int $cityId = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $type = null,
        public readonly ?int $population = null,
        public readonly bool $isActive = true
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getStateId(): int
    {
        return $this->stateId;
    }

    public function getCityId(): ?int
    {
        return $this->cityId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toDto(): ZipCodeDto
    {
        return new ZipCodeDto(
            id: $this->id,
            code: $this->code,
            state_id: $this->stateId,
            city_id: $this->cityId,
            latitude: $this->latitude,
            longitude: $this->longitude,
            type: $this->type,
            population: $this->population,
            is_active: $this->isActive
        );
    }
}

