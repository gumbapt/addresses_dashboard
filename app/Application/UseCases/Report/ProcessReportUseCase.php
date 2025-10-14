<?php

namespace App\Application\UseCases\Report;

use App\Application\Services\ReportProcessor;
use App\Domain\Repositories\ReportRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class ProcessReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private ReportProcessor $reportProcessor
    ) {}

    /**
     * Process a report synchronously
     */
    public function execute(int $reportId, array $reportData): void
    {
        // Verify report exists
        $report = $this->reportRepository->findById($reportId);
        if (!$report) {
            throw new NotFoundException("Report with ID {$reportId} not found");
        }

        // Update status to processing
        $this->reportRepository->updateStatus($reportId, 'processing');

        try {
            // Process the report
            $this->reportProcessor->process($reportId, $reportData);
            
            // Update status to processed
            $this->reportRepository->updateStatus($reportId, 'processed');
            
        } catch (\Exception $e) {
            // Update status to failed
            $this->reportRepository->updateStatus($reportId, 'failed');
            throw $e;
        }
    }
}
