<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        $providerNames = [
            'AT&T', 'Verizon', 'T-Mobile', 'Spectrum', 'Xfinity', 'Cox Communications',
            'Frontier', 'CenturyLink', 'Optimum', 'Mediacom', 'Windstream',
            'HughesNet', 'Viasat', 'Starlink', 'Google Fiber', 'Earthlink'
        ];
        
        $technologies = [
            'Fiber', 'Cable', 'Mobile', 'DSL', 'Satellite', 'Wireless'
        ];
        
        $name = fake()->randomElement($providerNames) . ' ' . fake()->randomNumber(2);
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'website' => fake()->boolean(70) ? fake()->url() : null,
            'logo_url' => fake()->boolean(50) ? fake()->imageUrl(200, 100, 'business') : null,
            'description' => fake()->boolean(80) ? fake()->sentence(10) : null,
            'technologies' => fake()->randomElements($technologies, fake()->numberBetween(1, 3)),
            'is_active' => fake()->boolean(90),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withTechnology(string $technology): static
    {
        return $this->state(fn (array $attributes) => [
            'technologies' => array_unique(array_merge($attributes['technologies'] ?? [], [$technology])),
        ]);
    }

    public function withTechnologies(array $technologies): static
    {
        return $this->state(fn (array $attributes) => [
            'technologies' => $technologies,
        ]);
    }

    public function withWebsite(string $website): static
    {
        return $this->state(fn (array $attributes) => [
            'website' => $website,
        ]);
    }

    public function fiberProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Google Fiber', 'AT&T Fiber', 'Verizon Fios']) . ' ' . fake()->randomNumber(2),
            'technologies' => ['Fiber'],
            'description' => 'High-speed fiber optic internet provider',
        ]);
    }

    public function cableProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Spectrum', 'Xfinity', 'Cox']) . ' ' . fake()->randomNumber(2),
            'technologies' => ['Cable'],
            'description' => 'Cable internet and TV provider',
        ]);
    }

    public function mobileProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['T-Mobile', 'Verizon Wireless', 'AT&T Mobile']) . ' ' . fake()->randomNumber(2),
            'technologies' => ['Mobile'],
            'description' => 'Mobile and wireless internet provider',
        ]);
    }
}
