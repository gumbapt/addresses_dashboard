<?php

namespace Database\Factories;

use App\Models\ReportSummary;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportSummaryFactory extends Factory
{
    protected $model = ReportSummary::class;

    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'total_requests' => $this->faker->numberBetween(100, 5000),
            'success_rate' => $this->faker->randomFloat(2, 80, 99),
            'failed_requests' => $this->faker->numberBetween(10, 500),
            'avg_requests_per_hour' => $this->faker->randomFloat(2, 1, 100),
            'unique_providers' => $this->faker->numberBetween(10, 150),
            'unique_states' => $this->faker->numberBetween(5, 50),
            'unique_zip_codes' => $this->faker->numberBetween(50, 1000),
        ];
    }
}

