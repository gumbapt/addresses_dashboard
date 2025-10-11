<?php

namespace App\Application\DTOs\Geographic;

class StateDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $timezone,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly bool $is_active = true
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
        ];
    }
}

