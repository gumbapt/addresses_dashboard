<?php

namespace App\Application\DTOs\Geographic;

class CityDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $state_id,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?int $population = null,
        public readonly bool $is_active = true,
        public readonly ?string $state_code = null,
        public readonly ?string $state_name = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'state_id' => $this->state_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'population' => $this->population,
            'is_active' => $this->is_active,
        ];

        // Include state info if available
        if ($this->state_code !== null) {
            $data['state_code'] = $this->state_code;
        }

        if ($this->state_name !== null) {
            $data['state_name'] = $this->state_name;
        }

        return $data;
    }
}

