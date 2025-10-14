<?php

namespace Tests\Integration;

use App\Application\Services\ReportProcessor;
use App\Domain\Repositories\ProviderRepositoryInterface;
use App\Domain\Repositories\StateRepositoryInterface;
use App\Domain\Repositories\CityRepositoryInterface;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use App\Models\Domain;
use App\Models\Provider;
use App\Models\State;
use App\Models\City;
use App\Models\ZipCode;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\ReportProvider;
use App\Models\ReportState;
use App\Models\ReportCity;
use App\Models\ReportZipCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReportProcessorTest extends TestCase
{
    use RefreshDatabase;

    private ReportProcessor $processor;
    private Domain $testDomain;
    private Report $testReport;
    private array $sampleReportData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = app(ReportProcessor::class);

        // Create test domain and report
        $this->testDomain = Domain::factory()->create(['name' => 'test.domain.com']);
        $this->testReport = Report::factory()->create(['domain_id' => $this->testDomain->id]);

        $this->sampleReportData = [
            'summary' => [
                'total_requests' => 1500,
                'success_rate' => 85.15,
                'failed_requests' => 223,
                'avg_requests_per_hour' => 1.56,
                'unique_providers' => 25,
                'unique_states' => 12,
                'unique_zip_codes' => 150
            ],
            'providers' => [
                'top_providers' => [
                    [
                        'name' => 'AT & T',
                        'total_count' => 39,
                        'technology' => 'Mobile',
                        'success_rate' => 92.5,
                        'avg_speed' => 45.2
                    ],
                    [
                        'name' => 'Verizon Wireless',
                        'total_count' => 32,
                        'technology' => 'Fiber',
                        'success_rate' => 88.1,
                        'avg_speed' => 78.5
                    ]
                ]
            ],
            'geographic' => [
                'states' => [
                    [
                        'code' => 'CA',
                        'name' => 'California',
                        'request_count' => 239,
                        'success_rate' => 89.2,
                        'avg_speed' => 52.1
                    ],
                    [
                        'code' => 'NY',
                        'name' => 'New York',
                        'request_count' => 184,
                        'success_rate' => 91.5,
                        'avg_speed' => 48.9
                    ]
                ],
                'top_cities' => [
                    [
                        'name' => 'Los Angeles',
                        'request_count' => 89,
                        'zip_codes' => ['90210', '90211', '90212']
                    ],
                    [
                        'name' => 'New York City',
                        'request_count' => 76,
                        'zip_codes' => ['10001', '10002']
                    ]
                ],
                'top_zip_codes' => [
                    [
                        'zip_code' => '90210',
                        'request_count' => 35,
                        'percentage' => 15.2
                    ],
                    [
                        'zip_code' => '10001',
                        'request_count' => 28,
                        'percentage' => 12.8
                    ]
                ]
            ]
        ];
    }

    public function test_processes_complete_report_successfully(): void
    {
        Log::shouldReceive('info')->atLeast(2);
        Log::shouldReceive('debug')->atLeast(1);

        $this->processor->process($this->testReport->id, $this->sampleReportData);

        // Verify summary was created
        $this->assertDatabaseHas('report_summaries', [
            'report_id' => $this->testReport->id,
            'total_requests' => 1500,
            'success_rate' => 85.15
        ]);

        // Verify providers were created and normalized
        $this->assertDatabaseHas('report_providers', [
            'report_id' => $this->testReport->id,
            'original_name' => 'AT & T',
            'technology' => 'Mobile',
            'total_count' => 39
        ]);

        $this->assertDatabaseHas('report_providers', [
            'report_id' => $this->testReport->id,
            'original_name' => 'Verizon Wireless',
            'technology' => 'Fiber',
            'total_count' => 32
        ]);

        // Verify states were processed
        $this->assertDatabaseHas('report_states', [
            'report_id' => $this->testReport->id,
            'request_count' => 239
        ]);

        // Verify cities were processed
        $this->assertDatabaseHas('report_cities', [
            'report_id' => $this->testReport->id,
            'request_count' => 89
        ]);

        // Verify zip codes were processed
        $this->assertDatabaseHas('report_zip_codes', [
            'report_id' => $this->testReport->id,
            'request_count' => 35,
            'percentage' => 15.2
        ]);
    }

    public function test_processes_summary_data(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->once();

        $this->processor->process($this->testReport->id, ['summary' => $this->sampleReportData['summary']]);

        $this->assertDatabaseHas('report_summaries', [
            'report_id' => $this->testReport->id,
            'total_requests' => 1500,
            'success_rate' => 85.15,
            'failed_requests' => 223,
            'avg_requests_per_hour' => 1.56,
            'unique_providers' => 25,
            'unique_states' => 12,
            'unique_zip_codes' => 150,
        ]);
    }

    public function test_skips_empty_summary_data(): void
    {
        Log::shouldReceive('info')->twice();

        $this->processor->process($this->testReport->id, ['summary' => []]);

        $this->assertDatabaseMissing('report_summaries', [
            'report_id' => $this->testReport->id
        ]);
    }

    public function test_processes_provider_data_with_normalization(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->twice(); // Processing and completion logs

        $this->processor->process($this->testReport->id, [
            'providers' => $this->sampleReportData['providers']
        ]);

        // Check that providers were created with proper normalization
        $reportProviders = ReportProvider::where('report_id', $this->testReport->id)->get();
        $this->assertCount(2, $reportProviders);

        // Find specific provider records
        $attRecord = $reportProviders->where('original_name', 'AT & T')->first();
        $verizonRecord = $reportProviders->where('original_name', 'Verizon Wireless')->first();

        $this->assertNotNull($attRecord);
        $this->assertNotNull($verizonRecord);

        // Verify AT&T was normalized properly
        $this->assertEquals('Mobile', $attRecord->technology);
        $this->assertEquals(39, $attRecord->total_count);
        $this->assertEquals(1, $attRecord->rank_position);

        // Verify Verizon was normalized properly  
        $this->assertEquals('Fiber', $verizonRecord->technology);
        $this->assertEquals(32, $verizonRecord->total_count);
        $this->assertEquals(2, $verizonRecord->rank_position);

        // Verify providers were created in the providers table via normalization
        $this->assertDatabaseHas('providers', ['name' => 'AT&T']);
        $this->assertDatabaseHas('providers', ['name' => 'Verizon']);
    }

    public function test_skips_empty_provider_data(): void
    {
        Log::shouldReceive('info')->twice();

        $this->processor->process($this->testReport->id, [
            'providers' => ['top_providers' => []]
        ]);

        $this->assertDatabaseMissing('report_providers', [
            'report_id' => $this->testReport->id
        ]);
    }

    public function test_processes_geographic_data(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->times(3); // states, cities, zip codes

        $this->processor->process($this->testReport->id, [
            'geographic' => $this->sampleReportData['geographic']
        ]);

        // Verify states were processed
        $this->assertDatabaseHas('report_states', [
            'report_id' => $this->testReport->id,
            'request_count' => 239
        ]);

        $this->assertDatabaseHas('report_states', [
            'report_id' => $this->testReport->id,
            'request_count' => 184
        ]);

        // Verify cities were processed
        $this->assertDatabaseHas('report_cities', [
            'report_id' => $this->testReport->id,
            'request_count' => 89
        ]);

        // Verify zip codes were processed
        $this->assertDatabaseHas('report_zip_codes', [
            'report_id' => $this->testReport->id,
            'request_count' => 35,
            'percentage' => 15.2
        ]);

        // Verify normalized entities were created
        $this->assertDatabaseHas('states', ['code' => 'CA', 'name' => 'California']);
        $this->assertDatabaseHas('states', ['code' => 'NY', 'name' => 'New York']);
        $this->assertDatabaseHas('cities', ['name' => 'Los Angeles']);
        $this->assertDatabaseHas('cities', ['name' => 'New York City']);
        $this->assertDatabaseHas('zip_codes', ['code' => '90210']);
        $this->assertDatabaseHas('zip_codes', ['code' => '10001']);
    }

    public function test_handles_missing_optional_data_gracefully(): void
    {
        Log::shouldReceive('info')->twice();

        // Should not crash with empty data
        $this->processor->process($this->testReport->id, []);

        // No related records should be created
        $this->assertDatabaseMissing('report_summaries', ['report_id' => $this->testReport->id]);
        $this->assertDatabaseMissing('report_providers', ['report_id' => $this->testReport->id]);
        $this->assertDatabaseMissing('report_states', ['report_id' => $this->testReport->id]);
    }

    public function test_handles_partial_data_gracefully(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->once();

        // Process only summary data
        $this->processor->process($this->testReport->id, [
            'summary' => $this->sampleReportData['summary']
        ]);

        // Verify only summary was created
        $this->assertDatabaseHas('report_summaries', [
            'report_id' => $this->testReport->id,
            'total_requests' => 1500
        ]);

        // No other data should be created
        $this->assertDatabaseMissing('report_providers', ['report_id' => $this->testReport->id]);
        $this->assertDatabaseMissing('report_states', ['report_id' => $this->testReport->id]);
    }

    public function test_normalizes_provider_names_correctly(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->twice();

        // Test with various AT&T name variations
        $testData = [
            'providers' => [
                'top_providers' => [
                    ['name' => 'AT & T', 'total_count' => 20, 'technology' => 'Mobile'],
                    ['name' => 'ATT', 'total_count' => 15, 'technology' => 'Fiber'],
                    ['name' => 'At&t', 'total_count' => 10, 'technology' => 'DSL']
                ]
            ]
        ];

        $this->processor->process($this->testReport->id, $testData);

        // All should be normalized to same provider but tracked separately
        $reportProviders = ReportProvider::where('report_id', $this->testReport->id)->get();
        $this->assertCount(3, $reportProviders);

        // Verify all have same normalized provider_id but different original_name
        $providerIds = $reportProviders->pluck('provider_id')->unique();
        $this->assertCount(1, $providerIds); // All should map to same normalized provider

        // Verify original names are preserved
        $originalNames = $reportProviders->pluck('original_name')->toArray();
        $this->assertContains('AT & T', $originalNames);
        $this->assertContains('ATT', $originalNames);
        $this->assertContains('At&t', $originalNames);
    }

    public function test_creates_geographic_entities_if_missing(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->times(3);

        // Test with new geographic data not yet in database
        $testData = [
            'geographic' => [
                'states' => [
                    ['code' => 'TX', 'name' => 'Texas', 'request_count' => 100]
                ],
                'top_cities' => [
                    ['name' => 'Houston', 'request_count' => 50]
                ],
                'top_zip_codes' => [
                    ['zip_code' => '77001', 'request_count' => 25, 'percentage' => 10.0]
                ]
            ]
        ];

        $this->processor->process($this->testReport->id, $testData);

        // Verify entities were created
        $this->assertDatabaseHas('states', ['code' => 'TX', 'name' => 'Texas']);
        $this->assertDatabaseHas('cities', ['name' => 'Houston']);
        $this->assertDatabaseHas('zip_codes', ['code' => '77001']);

        // Verify report relationships
        $this->assertDatabaseHas('report_states', [
            'report_id' => $this->testReport->id,
            'request_count' => 100
        ]);
        $this->assertDatabaseHas('report_cities', [
            'report_id' => $this->testReport->id,
            'request_count' => 50
        ]);
        $this->assertDatabaseHas('report_zip_codes', [
            'report_id' => $this->testReport->id,
            'request_count' => 25
        ]);
    }

    public function test_handles_duplicate_provider_names_across_technologies(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->twice();

        $testData = [
            'providers' => [
                'top_providers' => [
                    ['name' => 'AT&T', 'total_count' => 30, 'technology' => 'Mobile'],
                    ['name' => 'AT&T', 'total_count' => 25, 'technology' => 'Fiber']
                ]
            ]
        ];

        $this->processor->process($this->testReport->id, $testData);

        $reportProviders = ReportProvider::where('report_id', $this->testReport->id)->get();
        $this->assertCount(2, $reportProviders);

        // Should have same provider_id but different technologies
        $providerIds = $reportProviders->pluck('provider_id')->unique();
        $this->assertCount(1, $providerIds); // Same normalized provider

        $technologies = $reportProviders->pluck('technology')->toArray();
        $this->assertContains('Mobile', $technologies);
        $this->assertContains('Fiber', $technologies);
    }

    public function test_preserves_rank_positions(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->twice();

        $testData = [
            'providers' => [
                'top_providers' => [
                    ['name' => 'Provider A', 'total_count' => 100, 'technology' => 'Fiber'],
                    ['name' => 'Provider B', 'total_count' => 80, 'technology' => 'Mobile'],
                    ['name' => 'Provider C', 'total_count' => 60, 'technology' => 'DSL']
                ]
            ]
        ];

        $this->processor->process($this->testReport->id, $testData);

        // Verify rank positions are preserved
        $providerA = ReportProvider::where('report_id', $this->testReport->id)
            ->where('original_name', 'Provider A')
            ->first();
        $providerB = ReportProvider::where('report_id', $this->testReport->id)
            ->where('original_name', 'Provider B')
            ->first();
        $providerC = ReportProvider::where('report_id', $this->testReport->id)
            ->where('original_name', 'Provider C')
            ->first();

        $this->assertEquals(1, $providerA->rank_position);
        $this->assertEquals(2, $providerB->rank_position);
        $this->assertEquals(3, $providerC->rank_position);
    }

    public function test_handles_missing_optional_fields_gracefully(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->twice();

        // Test with minimal provider data (missing success_rate, avg_speed)
        $testData = [
            'providers' => [
                'top_providers' => [
                    ['name' => 'Basic Provider', 'total_count' => 50]
                    // Missing technology, success_rate, avg_speed
                ]
            ]
        ];

        $this->processor->process($this->testReport->id, $testData);

        // Should still process with defaults
        $this->assertDatabaseHas('report_providers', [
            'report_id' => $this->testReport->id,
            'original_name' => 'Basic Provider',
            'total_count' => 50,
            'technology' => 'Unknown', // Default from ProviderHelper
            'success_rate' => 0, // Default
            'avg_speed' => 0, // Default
        ]);
    }

    public function test_processes_large_dataset(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->atLeast(1);

        // Create large dataset
        $largeData = ['providers' => ['top_providers' => []]];
        for ($i = 0; $i < 100; $i++) {
            $largeData['providers']['top_providers'][] = [
                'name' => "Provider {$i}",
                'total_count' => rand(10, 1000),
                'technology' => ['Mobile', 'Fiber', 'DSL'][rand(0, 2)]
            ];
        }

        $startTime = microtime(true);
        $this->processor->process($this->testReport->id, $largeData);
        $endTime = microtime(true);

        // Should complete in reasonable time (less than 5 seconds)
        $this->assertLessThan(5.0, $endTime - $startTime);

        // Verify all providers were processed
        $this->assertEquals(100, ReportProvider::where('report_id', $this->testReport->id)->count());
    }
}
