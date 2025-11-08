<?php

namespace Database\Factories;

use App\Models\DomainGroup;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainGroup>
 */
class DomainGroupFactory extends Factory
{
    protected $model = DomainGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(10),
            'is_active' => true,
            'settings' => [
                'feature_flags' => [
                    'reports' => true,
                    'analytics' => true,
                ],
            ],
            'max_domains' => fake()->optional(0.7)->numberBetween(5, 50), // 70% chance de ter limite
            'created_by' => Admin::factory(),
        ];
    }

    /**
     * Indicate that the domain group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the domain group has unlimited domains.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_domains' => null,
        ]);
    }

    /**
     * Indicate that the domain group has a specific max domains limit.
     */
    public function withLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'max_domains' => $limit,
        ]);
    }
}
