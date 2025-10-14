<?php

namespace Tests\Unit\Application\UseCases\Report;

use App\Application\UseCases\Report\CreateReportUseCase;
use App\Domain\Entities\Report as ReportEntity;
use App\Domain\Repositories\ReportRepositoryInterface;
use App\Models\Domain;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CreateReportUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private CreateReportUseCase $useCase;
    private Domain $testDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = new CreateReportUseCase(
            app(ReportRepositoryInterface::class)
        );
        
        $this->testDomain = Domain::factory()->create([
            'name' => 'Test Domain',
            'slug' => 'test-domain',
        ]);
    }

    public function test_create_report_from_json_data(): void
    {
        $reportData = [
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
                'total_processing_time' => 0,
                'data_version' => '2.0.0'
            ],
            'summary' => [
                'total_requests' => 1500,
                'success_rate' => 85.15,
                'failed_requests' => 223
            ],
            'providers' => [
                'top_providers' => [
                    ['name' => 'AT&T', 'total_count' => 39, 'technology' => 'Mobile']
                ]
            ]
        ];

        $report = $this->useCase->execute($this->testDomain->id, $reportData);

        $this->assertInstanceOf(ReportEntity::class, $report);
        $this->assertEquals($this->testDomain->id, $report->getDomainId());
        $this->assertEquals('2025-10-13', $report->getReportDate());
        $this->assertEquals('2.0.0', $report->getDataVersion());
        $this->assertEquals('pending', $report->getStatus());
        
        // Verify raw data is preserved
        $this->assertEquals($reportData, $report->getRawData());
        
        // Verify timestamps are parsed correctly
        $this->assertEquals('2025-10-13 00:00:00', $report->getReportPeriodStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-13 23:59:59', $report->getReportPeriodEnd()->format('Y-m-d H:i:s'));
    }

    public function test_create_report_with_custom_status(): void
    {
        $reportData = [
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
            'summary' => []
        ];

        $report = $this->useCase->executeWithStatus(
            $this->testDomain->id,
            $reportData,
            'processing'
        );

        $this->assertEquals('processing', $report->getStatus());
        $this->assertTrue($report->isProcessing());
        $this->assertEquals(120, $report->getTotalProcessingTime());
    }

    public function test_report_date_extraction(): void
    {
        $reportData = [
            'metadata' => [
                'report_date' => '2025-12-25',
                'report_period' => [
                    'start' => '2025-12-25 00:00:00',
                    'end' => '2025-12-25 23:59:59'
                ],
                'generated_at' => '2025-12-25 23:59:59',
                'data_version' => '3.0.0'
            ]
        ];

        $report = $this->useCase->execute($this->testDomain->id, $reportData);

        $this->assertEquals('2025-12-25', $report->getReportDate());
        $this->assertEquals('3.0.0', $report->getDataVersion());
    }
}
