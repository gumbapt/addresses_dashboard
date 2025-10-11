<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'state_id' => State::inRandomOrder()->first()?->id ?? State::factory(),
            'latitude' => fake()->latitude(25, 50), // US latitude range
            'longitude' => fake()->longitude(-125, -65), // US longitude range
            'population' => fake()->numberBetween(1000, 1000000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forState(int $stateId): static
    {
        return $this->state(fn (array $attributes) => [
            'state_id' => $stateId,
        ]);
    }

    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}

