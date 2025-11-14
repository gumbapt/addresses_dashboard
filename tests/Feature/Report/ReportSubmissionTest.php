<?php

namespace Tests\Feature\Report;

use App\Models\Domain;
use App\Models\Report;
use App\Jobs\ProcessReportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Domain $testDomain;
    private array $validReportData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test domain with API key
        $this->testDomain = Domain::factory()->create([
            'name' => 'test.domain.com',
            'api_key' => str_repeat('a', 64),
            'is_active' => true,
        ]);

        // Valid report data structure
        $this->validReportData = [
            'source' => [
                'domain' => 'test.domain.com',
                'site_id' => 'wp-test-001',
                'site_name' => 'Test Site'
            ],
            'metadata' => [
                'report_date' => '2025-10-13',
                'report_period' => [
                    'start' => '2025-10-13 00:00:00',
                    'end' => '2025-10-13 23:59:59'
                ],
                'generated_at' => '2025-10-13 18:54:50',
                'total_processing_time' => 120,
                'data_version' => '2.0.0'
            ],
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
                        'name' => 'AT&T',
                        'total_count' => 39,
                        'technology' => 'Mobile',
                        'success_rate' => 92.5,
                        'avg_speed' => 45.2
                    ],
                    [
                        'name' => 'Verizon',
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
                    ]
                ],
                'top_zip_codes' => [
                    [
                        'zip_code' => '90210',
                        'request_count' => 35,
                        'percentage' => 15.2
                    ]
                ]
            ]
        ];
    }

    public function test_can_submit_valid_report_with_bearer_token(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/reports/submit', $this->validReportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Report received and queued for processing'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'domain_id',
                    'report_date',
                    'status'
                ]
            ]);

        // Verify report was created in database
        $this->assertDatabaseHas('reports', [
            'domain_id' => $this->testDomain->id,
            'status' => 'pending',
            'data_version' => '2.0.0'
        ]);

        // Verify job was dispatched
        Queue::assertPushed(ProcessReportJob::class);
    }

    public function test_can_submit_valid_report_with_api_key_header(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/reports/submit', $this->validReportData, [
            'X-API-Key' => $this->testDomain->api_key,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Report received and queued for processing'
            ]);

        Queue::assertPushed(ProcessReportJob::class);
    }

    public function test_cannot_submit_report_without_api_key(): void
    {
        $response = $this->postJson('/api/reports/submit', $this->validReportData);

        $response->assertStatus(401);
    }

    public function test_cannot_submit_report_with_invalid_api_key(): void
    {
        $response = $this->postJson('/api/reports/submit', $this->validReportData, [
            'Authorization' => 'Bearer invalid_key',
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_submit_report_with_inactive_domain(): void
    {
        $this->testDomain->update(['is_active' => false]);

        $response = $this->postJson('/api/reports/submit', $this->validReportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_submit_report_with_domain_mismatch(): void
    {
        $reportData = $this->validReportData;
        $reportData['source']['domain'] = 'different.domain.com';

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Domain mismatch - authenticated domain does not match source domain'
            ]);
    }

    public function test_cannot_submit_report_without_required_source_fields(): void
    {
        $reportData = $this->validReportData;
        unset($reportData['source']['domain']);

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source.domain']);
    }

    public function test_cannot_submit_report_without_required_metadata_fields(): void
    {
        $reportData = $this->validReportData;
        unset($reportData['metadata']['report_date']);

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['metadata.report_date']);
    }

    public function test_cannot_submit_report_with_invalid_date_format(): void
    {
        $reportData = $this->validReportData;
        $reportData['metadata']['report_date'] = '2025/10/13'; // Wrong format

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['metadata.report_date']);
    }

    public function test_can_submit_report_with_minimal_required_data(): void
    {
        Queue::fake();

        $minimalData = [
            'source' => [
                'domain' => 'test.domain.com',
                'site_id' => 'wp-test-001',
                'site_name' => 'Test Site'
            ],
            'metadata' => [
                'report_date' => '2025-10-13',
                'report_period' => [
                    'start' => '2025-10-13 00:00:00',
                    'end' => '2025-10-13 23:59:59'
                ],
                'generated_at' => '2025-10-13 18:54:50',
                'data_version' => '2.0.0'
            ],
            'summary' => []
        ];

        $response = $this->postJson('/api/reports/submit', $minimalData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(201);
        Queue::assertPushed(ProcessReportJob::class);
    }

    public function test_validates_provider_data_structure(): void
    {
        $reportData = $this->validReportData;
        $reportData['providers']['top_providers'][0]['name'] = ''; // Empty name

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['providers.top_providers.0.name']);
    }

    public function test_validates_geographic_data_structure(): void
    {
        $reportData = $this->validReportData;
        $reportData['geographic']['states'][0]['code'] = 'CALIFORNIA'; // Wrong format

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['geographic.states.0.code']);
    }

    public function test_validates_numeric_fields(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['total_requests'] = 'not_a_number';

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['summary.total_requests']);
    }

    public function test_validates_success_rate_range(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['success_rate'] = 150; // Over 100%

        $response = $this->postJson('/api/reports/submit', $reportData, [
            'Authorization' => 'Bearer ' . $this->testDomain->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['summary.success_rate']);
    }
}
