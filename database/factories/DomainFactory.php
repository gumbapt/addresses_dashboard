<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        $name = fake()->company() . ' ISP';
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'domain_url' => fake()->domainName(),
            'site_id' => 'wp-prod-' . Str::random(10),
            'api_key' => 'sk_live_' . Str::random(64),
            'status' => 'active',
            'timezone' => fake()->randomElement([
                'America/New_York',
                'America/Chicago',
                'America/Denver',
                'America/Los_Angeles',
                'America/Phoenix',
                'UTC'
            ]),
            'wordpress_version' => fake()->randomElement(['6.8.0', '6.8.1', '6.8.2', '6.8.3']),
            'plugin_version' => fake()->randomElement(['2.0.0', '2.0.1', '2.1.0']),
            'settings' => [
                'enable_notifications' => fake()->boolean(80),
                'report_frequency' => fake()->randomElement(['daily', 'weekly']),
                'max_retries' => fake()->numberBetween(1, 5),
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => 'inactive',
        ]);
    }

    public function withSpecificTimezone(string $timezone): static
    {
        return $this->state(fn (array $attributes) => [
            'timezone' => $timezone,
        ]);
    }
}

