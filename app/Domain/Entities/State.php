<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Geographic\StateDto;

class State
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $timezone,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly bool $isActive = true
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toDto(): StateDto
    {
        return new StateDto(
            id: $this->id,
            code: $this->code,
            name: $this->name,
            timezone: $this->timezone,
            latitude: $this->latitude,
            longitude: $this->longitude,
            is_active: $this->isActive
        );
    }
}

