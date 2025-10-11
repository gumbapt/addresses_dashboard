<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->unique()->state(),
            'timezone' => fake()->randomElement([
                'America/New_York',
                'America/Chicago',
                'America/Denver',
                'America/Los_Angeles',
                'America/Anchorage',
                'Pacific/Honolulu',
            ]),
            'latitude' => fake()->latitude(25, 50),
            'longitude' => fake()->longitude(-125, -65),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function withTimezone(string $timezone): static
    {
        return $this->state(fn (array $attributes) => [
            'timezone' => $timezone,
        ]);
    }
}

