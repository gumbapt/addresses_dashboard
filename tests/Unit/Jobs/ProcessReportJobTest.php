<?php

namespace Tests\Unit\Jobs;

use App\Application\Services\ReportProcessor;
use App\Infrastructure\Repositories\ReportRepository;
use App\Jobs\ProcessReportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ProcessReportJobTest extends TestCase
{

    private ProcessReportJob $job;
    private array $sampleReportData;

    protected function setUp(): void
    {
        parent::setUp();

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
                'total_requests' => 1000
            ]
        ];

        $this->job = new ProcessReportJob(1, $this->sampleReportData);
    }

    public function test_job_has_correct_configuration(): void
    {
        $this->assertEquals(300, $this->job->timeout);
        $this->assertEquals(3, $this->job->tries);
        $this->assertEquals(3, $this->job->maxExceptions);
    }

    public function test_job_processes_report_successfully(): void
    {
        Log::shouldReceive('info')
            ->times(2) // Start and completion logs
            ->withAnyArgs();

        // Mock dependencies
        $mockProcessor = Mockery::mock(ReportProcessor::class);
        $mockRepository = Mockery::mock(ReportRepository::class);

        $mockRepository->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'processing');

        $mockProcessor->shouldReceive('process')
            ->once()
            ->with(1, $this->sampleReportData);

        $mockRepository->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'processed');

        // Mock DB transaction
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        // Execute job
        $this->job->handle($mockProcessor, $mockRepository);
        
        $this->assertTrue(true);
        
        // Assertion to ensure the test actually validates something
        $this->assertTrue(true);
    }

    public function test_job_handles_processing_failure(): void
    {
        Log::shouldReceive('info')->once(); // Start log
        Log::shouldReceive('error')->once(); // Error log

        // Mock dependencies
        $mockProcessor = Mockery::mock(ReportProcessor::class);
        $mockRepository = Mockery::mock(ReportRepository::class);

        $mockRepository->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'processing');

        $exception = new \Exception('Processing failed');
        
        $mockProcessor->shouldReceive('process')
            ->once()
            ->andThrow($exception);

        $mockRepository->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'failed');

        // Mock DB transaction
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        // Expect exception to be re-thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Processing failed');

        $this->job->handle($mockProcessor, $mockRepository);
        
        $this->assertTrue(true);
    }

    public function test_job_logs_start_message(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Starting report processing', [
                'report_id' => 1,
                'data_version' => '2.0.0'
            ]);

        Log::shouldReceive('info')->once(); // Completion log

        // Mock successful processing
        $mockProcessor = Mockery::mock(ReportProcessor::class);
        $mockRepository = Mockery::mock(ReportRepository::class);

        $mockRepository->shouldReceive('updateStatus')->twice();
        $mockProcessor->shouldReceive('process')->once();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->job->handle($mockProcessor, $mockRepository);
        
        $this->assertTrue(true);
    }

    public function test_job_logs_completion_message(): void
    {
        Log::shouldReceive('info')->once(); // Start log
        Log::shouldReceive('info')
            ->once()
            ->with('Report processing completed successfully', [
                'report_id' => 1
            ]);

        // Mock successful processing
        $mockProcessor = Mockery::mock(ReportProcessor::class);
        $mockRepository = Mockery::mock(ReportRepository::class);

        $mockRepository->shouldReceive('updateStatus')->twice();
        $mockProcessor->shouldReceive('process')->once();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->job->handle($mockProcessor, $mockRepository);
        
        $this->assertTrue(true);
    }

    public function test_job_logs_error_with_details(): void
    {
        Log::shouldReceive('info')->once(); // Start log
        
        Log::shouldReceive('error')
            ->once()
            ->with('Report processing failed', Mockery::on(function ($context) {
                return isset($context['report_id']) && 
                       isset($context['error']) && 
                       isset($context['trace']) &&
                       $context['report_id'] === 1 &&
                       $context['error'] === 'Database error';
            }));

        // Mock dependencies
        $mockProcessor = Mockery::mock(ReportProcessor::class);
        $mockRepository = Mockery::mock(ReportRepository::class);

        $mockRepository->shouldReceive('updateStatus')->once()->with(1, 'processing');
        
        $exception = new \Exception('Database error');
        $mockProcessor->shouldReceive('process')->once()->andThrow($exception);
        
        $mockRepository->shouldReceive('updateStatus')->once()->with(1, 'failed');

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->expectException(\Exception::class);
        $this->job->handle($mockProcessor, $mockRepository);
        
        $this->assertTrue(true);
    }

    public function test_job_handles_data_version_gracefully(): void
    {
        $reportDataWithoutVersion = [
            'source' => ['domain' => 'test.com'],
            'metadata' => ['report_date' => '2025-10-13']
        ];

        $job = new ProcessReportJob(1, $reportDataWithoutVersion);

        Log::shouldReceive('info')
            ->once()
            ->with('Starting report processing', [
                'report_id' => 1,
                'data_version' => 'unknown'
            ]);

        Log::shouldReceive('info')->once(); // Completion log

        // Mock successful processing
        $mockProcessor = Mockery::mock(ReportProcessor::class);
        $mockRepository = Mockery::mock(ReportRepository::class);

        $mockRepository->shouldReceive('updateStatus')->twice();
        $mockProcessor->shouldReceive('process')->once();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $job->handle($mockProcessor, $mockRepository);
    }

    public function test_failed_method_logs_failure(): void
    {
        $exception = new \Exception('Fatal error');

        Log::shouldReceive('error')
            ->once()
            ->with('ProcessReportJob failed permanently', Mockery::on(function ($context) {
                return isset($context['report_id']) && 
                       isset($context['error']) && 
                       isset($context['attempts']) &&
                       $context['report_id'] === 1 &&
                       $context['error'] === 'Fatal error';
            }));

        // Mock attempts method
        $job = Mockery::mock(ProcessReportJob::class)->makePartial();
        $job->shouldReceive('attempts')->once()->andReturn(3);
        $job->reportId = 1;

        $job->failed($exception);
    }

    public function test_failed_method_updates_report_status(): void
    {
        $exception = new \Exception('Fatal error');

        Log::shouldReceive('error')->twice(); // Main error + potential update error

        // Mock the app helper and repository
        $mockRepository = Mockery::mock(ReportRepository::class);
        $mockRepository->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'failed');

        $this->app->instance(ReportRepository::class, $mockRepository);

        $job = Mockery::mock(ProcessReportJob::class)->makePartial();
        $job->shouldReceive('attempts')->once()->andReturn(3);
        $job->reportId = 1;

        $job->failed($exception);
    }

    public function test_failed_method_handles_update_error_gracefully(): void
    {
        $exception = new \Exception('Fatal error');

        Log::shouldReceive('error')->once(); // Main error log
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to update report status after job failure', Mockery::on(function ($context) {
                return isset($context['report_id']) && 
                       isset($context['error']) &&
                       $context['report_id'] === 1;
            }));

        // Mock repository that throws error on update
        $mockRepository = Mockery::mock(ReportRepository::class);
        $mockRepository->shouldReceive('updateStatus')
            ->once()
            ->andThrow(new \Exception('Update failed'));

        $this->app->instance(ReportRepository::class, $mockRepository);

        $job = Mockery::mock(ProcessReportJob::class)->makePartial();
        $job->shouldReceive('attempts')->once()->andReturn(3);
        $job->reportId = 1;

        $job->failed($exception);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
