<?php

namespace App\Jobs;

use App\Application\Services\ReportProcessor;
use App\Infrastructure\Repositories\ReportRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $reportId,
        public array $reportData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ReportProcessor $processor,
        ReportRepository $reportRepository
    ): void {
        Log::info('Starting report processing', [
            'report_id' => $this->reportId,
            'data_version' => $this->reportData['metadata']['data_version'] ?? 'unknown'
        ]);

        try {
            // Update status to processing
            $reportRepository->updateStatus($this->reportId, 'processing');
            
            DB::transaction(function () use ($processor) {
                // Process the report data
                $processor->process($this->reportId, $this->reportData);
            });
            
            // Update status to processed
            $reportRepository->updateStatus($this->reportId, 'processed');
            
            Log::info('Report processing completed successfully', [
                'report_id' => $this->reportId
            ]);
            
        } catch (\Exception $e) {
            // Update status to failed
            $reportRepository->updateStatus($this->reportId, 'failed');
            
            Log::error('Report processing failed', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to trigger job failure handling
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReportJob failed permanently', [
            'report_id' => $this->reportId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        // Update report status to failed
        try {
            $reportRepository = app(ReportRepository::class);
            $reportRepository->updateStatus($this->reportId, 'failed');
        } catch (\Exception $e) {
            Log::error('Failed to update report status after job failure', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
