<?php

namespace App\Application\DTOs\Geographic;

class ZipCodeDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly int $state_id,
        public readonly ?int $city_id = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $type = null,
        public readonly ?int $population = null,
        public readonly bool $is_active = true,
        public readonly ?string $state_code = null,
        public readonly ?string $state_name = null,
        public readonly ?string $city_name = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'type' => $this->type,
            'population' => $this->population,
            'is_active' => $this->is_active,
        ];

        // Include related data if available
        if ($this->state_code !== null) {
            $data['state_code'] = $this->state_code;
        }

        if ($this->state_name !== null) {
            $data['state_name'] = $this->state_name;
        }

        if ($this->city_name !== null) {
            $data['city_name'] = $this->city_name;
        }

        return $data;
    }
}

