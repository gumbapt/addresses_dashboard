<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportDate = $this->faker->date();
        $startTime = $reportDate . ' 00:00:00';
        $endTime = $reportDate . ' 23:59:59';
        
        return [
            'domain_id' => Domain::factory(),
            'report_date' => $reportDate,
            'report_period_start' => $startTime,
            'report_period_end' => $endTime,
            'generated_at' => $this->faker->dateTimeBetween($startTime, $endTime),
            'total_processing_time' => $this->faker->numberBetween(30, 300),
            'data_version' => $this->faker->randomElement(['1.0.0', '2.0.0', '2.1.0']),
            'raw_data' => [
                'source' => [
                    'domain' => $this->faker->domainName(),
                    'site_id' => 'wp-' . $this->faker->uuid(),
                    'site_name' => $this->faker->company()
                ],
                'metadata' => [
                    'report_date' => $reportDate,
                    'data_version' => '2.0.0'
                ],
                'summary' => [
                    'total_requests' => $this->faker->numberBetween(100, 5000),
                    'success_rate' => $this->faker->randomFloat(2, 70, 99),
                    'failed_requests' => $this->faker->numberBetween(10, 200)
                ]
            ],
            'status' => $this->faker->randomElement(['pending', 'processing', 'processed', 'failed']),
        ];
    }

    /**
     * Indicate that the report is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the report is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate that the report is processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
        ]);
    }

    /**
     * Indicate that the report has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Create a report with comprehensive data.
     */
    public function withCompleteData(): static
    {
        return $this->state(fn (array $attributes) => [
            'raw_data' => [
                'source' => [
                    'domain' => $this->faker->domainName(),
                    'site_id' => 'wp-' . $this->faker->uuid(),
                    'site_name' => $this->faker->company()
                ],
                'metadata' => [
                    'report_date' => $attributes['report_date'],
                    'report_period' => [
                        'start' => $attributes['report_period_start'],
                        'end' => $attributes['report_period_end']
                    ],
                    'generated_at' => $attributes['generated_at']->format('Y-m-d H:i:s'),
                    'data_version' => $attributes['data_version']
                ],
                'summary' => [
                    'total_requests' => $this->faker->numberBetween(1000, 10000),
                    'success_rate' => $this->faker->randomFloat(2, 80, 99),
                    'failed_requests' => $this->faker->numberBetween(50, 500),
                    'avg_requests_per_hour' => $this->faker->randomFloat(2, 1, 10),
                    'unique_providers' => $this->faker->numberBetween(10, 50),
                    'unique_states' => $this->faker->numberBetween(5, 25),
                    'unique_zip_codes' => $this->faker->numberBetween(50, 200)
                ],
                'providers' => [
                    'top_providers' => [
                        [
                            'name' => 'AT&T',
                            'total_count' => $this->faker->numberBetween(100, 500),
                            'technology' => 'Mobile',
                            'success_rate' => $this->faker->randomFloat(2, 85, 98),
                            'avg_speed' => $this->faker->randomFloat(2, 30, 80)
                        ],
                        [
                            'name' => 'Verizon',
                            'total_count' => $this->faker->numberBetween(80, 400),
                            'technology' => 'Fiber',
                            'success_rate' => $this->faker->randomFloat(2, 82, 95),
                            'avg_speed' => $this->faker->randomFloat(2, 40, 100)
                        ]
                    ]
                ],
                'geographic' => [
                    'states' => [
                        [
                            'code' => 'CA',
                            'name' => 'California',
                            'request_count' => $this->faker->numberBetween(200, 800),
                            'success_rate' => $this->faker->randomFloat(2, 85, 95),
                            'avg_speed' => $this->faker->randomFloat(2, 45, 75)
                        ],
                        [
                            'code' => 'NY',
                            'name' => 'New York',
                            'request_count' => $this->faker->numberBetween(150, 600),
                            'success_rate' => $this->faker->randomFloat(2, 80, 92),
                            'avg_speed' => $this->faker->randomFloat(2, 40, 70)
                        ]
                    ],
                    'top_cities' => [
                        [
                            'name' => 'Los Angeles',
                            'request_count' => $this->faker->numberBetween(50, 200),
                            'zip_codes' => ['90210', '90211', '90212']
                        ]
                    ],
                    'top_zip_codes' => [
                        [
                            'zip_code' => '90210',
                            'request_count' => $this->faker->numberBetween(20, 80),
                            'percentage' => $this->faker->randomFloat(2, 5, 25)
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Create a report for a specific domain.
     */
    public function forDomain(Domain $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_id' => $domain->id,
            'raw_data' => array_merge($attributes['raw_data'] ?? [], [
                'source' => array_merge($attributes['raw_data']['source'] ?? [], [
                    'domain' => $domain->name
                ])
            ])
        ]);
    }

    /**
     * Create a report for a specific date.
     */
    public function forDate(string $date): static
    {
        $startTime = $date . ' 00:00:00';
        $endTime = $date . ' 23:59:59';
        
        return $this->state(fn (array $attributes) => [
            'report_date' => $date,
            'report_period_start' => $startTime,
            'report_period_end' => $endTime,
            'generated_at' => $this->faker->dateTimeBetween($startTime, $endTime),
            'raw_data' => array_merge($attributes['raw_data'] ?? [], [
                'metadata' => array_merge($attributes['raw_data']['metadata'] ?? [], [
                    'report_date' => $date,
                    'report_period' => [
                        'start' => $startTime,
                        'end' => $endTime
                    ]
                ])
            ])
        ]);
    }
}
