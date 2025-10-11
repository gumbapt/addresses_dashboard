<?php

namespace Database\Factories;

use App\Models\ZipCode;
use App\Models\State;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZipCodeFactory extends Factory
{
    protected $model = ZipCode::class;

    public function definition(): array
    {
        // Generate random 5-digit ZIP code
        $zipCode = str_pad((string) fake()->numberBetween(1000, 99999), 5, '0', STR_PAD_LEFT);
        
        return [
            'code' => $zipCode,
            'state_id' => State::inRandomOrder()->first()?->id ?? State::factory(),
            'city_id' => fake()->boolean(70) ? (City::inRandomOrder()->first()?->id) : null, // 70% chance of having city
            'latitude' => fake()->latitude(25, 50),
            'longitude' => fake()->longitude(-125, -65),
            'type' => fake()->randomElement(['Standard', 'PO Box', 'Unique', null]),
            'population' => fake()->numberBetween(100, 50000),
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

    public function forCity(int $cityId): static
    {
        return $this->state(fn (array $attributes) => [
            'city_id' => $cityId,
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

