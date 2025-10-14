<?php

namespace Tests\Unit\Application\UseCases\Report;

use App\Application\Services\ReportProcessor;
use App\Application\UseCases\Report\ProcessReportUseCase;
use App\Domain\Entities\Report;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Repositories\ReportRepositoryInterface;
use Tests\TestCase;
use Mockery;

class ProcessReportUseCaseTest extends TestCase
{
    private ProcessReportUseCase $useCase;
    private ReportRepositoryInterface $mockReportRepository;
    private ReportProcessor $mockReportProcessor;
    private array $sampleReportData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockReportRepository = Mockery::mock(ReportRepositoryInterface::class);
        $this->mockReportProcessor = Mockery::mock(ReportProcessor::class);

        $this->useCase = new ProcessReportUseCase(
            $this->mockReportRepository,
            $this->mockReportProcessor
        );

        $this->sampleReportData = [
            'source' => [
                'domain' => 'test.domain.com',
                'site_id' => 'wp-test-001'
            ],
            'metadata' => [
                'report_date' => '2025-10-13',
                'data_version' => '2.0.0'
            ],
            'summary' => [
                'total_requests' => 1500,
                'success_rate' => 85.15
            ]
        ];
    }

    public function test_successfully_processes_existing_report(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);

        // Mock report exists
        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->with($reportId)
            ->andReturn($mockReport);

        // Mock status updates
        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing');

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processed');

        // Mock successful processing
        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $this->sampleReportData);

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_throws_exception_for_nonexistent_report(): void
    {
        $reportId = 999;

        // Mock report not found
        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->with($reportId)
            ->andReturn(null);

        // Should not attempt to process or update status
        $this->mockReportRepository->shouldReceive('updateStatus')->never();
        $this->mockReportProcessor->shouldReceive('process')->never();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Report with ID 999 not found');

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_updates_status_to_processing_before_processing(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        // Verify status is updated to processing BEFORE processing starts
        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing')
            ->ordered();

        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $this->sampleReportData)
            ->ordered();

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processed')
            ->ordered();

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_updates_status_to_processed_after_successful_processing(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing');

        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $this->sampleReportData);

        // Verify status is updated to processed AFTER processing succeeds
        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processed');

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_updates_status_to_failed_when_processing_fails(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);
        $processingException = new \Exception('Processing failed');

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing');

        // Mock processing failure
        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $this->sampleReportData)
            ->andThrow($processingException);

        // Should update status to failed
        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'failed');

        // Should re-throw the exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Processing failed');

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_passes_exact_data_to_processor(): void
    {
        $reportId = 42;
        $specificData = [
            'source' => ['domain' => 'specific.test.com'],
            'metadata' => ['report_date' => '2025-01-01'],
            'summary' => ['total_requests' => 999],
            'providers' => ['top_providers' => []]
        ];
        $mockReport = Mockery::mock(Report::class);

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')->twice();

        // Verify exact data is passed to processor
        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $specificData);

        $this->useCase->execute($reportId, $specificData);
    }

    public function test_handles_database_exception_during_status_update(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);
        $dbException = new \Exception('Database connection failed');

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        // Mock database failure on first status update
        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing')
            ->andThrow($dbException);

        // Should not attempt processing or further status updates
        $this->mockReportProcessor->shouldReceive('process')->never();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_handles_processor_exception_with_proper_cleanup(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);
        $processingException = new \RuntimeException('Memory limit exceeded');

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing');

        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->andThrow($processingException);

        // Should still update status to failed even with exception
        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'failed');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Memory limit exceeded');

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_processes_empty_report_data(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);
        $emptyData = [];

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing');

        // Processor should handle empty data gracefully
        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $emptyData);

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processed');

        $this->useCase->execute($reportId, $emptyData);
    }

    public function test_handles_mixed_success_and_failure_scenarios(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'processing');

        // First call succeeds, but let's say processor throws exception
        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));

        $this->mockReportRepository->shouldReceive('updateStatus')
            ->once()
            ->with($reportId, 'failed');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unexpected error');

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_verifies_report_existence_before_any_processing(): void
    {
        $reportId = 1;

        // Mock report not found
        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->with($reportId)
            ->andReturn(null);

        // These should never be called since report doesn't exist
        $this->mockReportRepository->shouldReceive('updateStatus')->never();
        $this->mockReportProcessor->shouldReceive('process')->never();

        $this->expectException(NotFoundException::class);

        $this->useCase->execute($reportId, $this->sampleReportData);
        
        $this->assertTrue(true);
    }

    public function test_handles_large_report_data(): void
    {
        $reportId = 1;
        $mockReport = Mockery::mock(Report::class);
        
        // Create large report data
        $largeData = $this->sampleReportData;
        $largeData['providers'] = ['top_providers' => []];
        for ($i = 0; $i < 1000; $i++) {
            $largeData['providers']['top_providers'][] = [
                'name' => "Provider {$i}",
                'total_count' => rand(1, 100)
            ];
        }

        $this->mockReportRepository->shouldReceive('findById')
            ->once()
            ->andReturn($mockReport);

        $this->mockReportRepository->shouldReceive('updateStatus')->twice();

        $this->mockReportProcessor->shouldReceive('process')
            ->once()
            ->with($reportId, $largeData);

        $this->useCase->execute($reportId, $largeData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
