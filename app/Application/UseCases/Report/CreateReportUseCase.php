<?php

namespace App\Application\UseCases\Report;

use App\Domain\Entities\Report;
use App\Domain\Repositories\ReportRepositoryInterface;
use DateTime;

class CreateReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    public function execute(
        int $domainId,
        array $reportData
    ): Report {
        // Extract metadata
        $metadata = $reportData['metadata'];
        
        return $this->reportRepository->create(
            domainId: $domainId,
            reportDate: $metadata['report_date'],
            reportPeriodStart: new DateTime($metadata['report_period']['start']),
            reportPeriodEnd: new DateTime($metadata['report_period']['end']),
            generatedAt: new DateTime($metadata['generated_at']),
            totalProcessingTime: $metadata['total_processing_time'] ?? 0,
            dataVersion: $metadata['data_version'],
            rawData: $reportData,
            status: 'pending'
        );
    }

    public function executeWithStatus(
        int $domainId,
        array $reportData,
        string $status
    ): Report {
        $metadata = $reportData['metadata'];
        
        return $this->reportRepository->create(
            domainId: $domainId,
            reportDate: $metadata['report_date'],
            reportPeriodStart: new DateTime($metadata['report_period']['start']),
            reportPeriodEnd: new DateTime($metadata['report_period']['end']),
            generatedAt: new DateTime($metadata['generated_at']),
            totalProcessingTime: $metadata['total_processing_time'] ?? 0,
            dataVersion: $metadata['data_version'],
            rawData: $reportData,
            status: $status
        );
    }
}
