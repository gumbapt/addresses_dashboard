<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Report\ReportSummaryDto;

class ReportSummary
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,           // FK → reports
        public readonly int $totalRequests,     // 1502
        public readonly float $successRate,     // 85.15
        public readonly int $failedRequests,    // 223
        public readonly float $avgRequestsPerHour, // 1.56
        public readonly int $uniqueProviders,   // 0 (será calculado)
        public readonly int $uniqueStates,      // 0 (será calculado)
        public readonly int $uniqueZipCodes     // 0 (será calculado)
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getTotalRequests(): int
    {
        return $this->totalRequests;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function getFailedRequests(): int
    {
        return $this->failedRequests;
    }

    public function getAvgRequestsPerHour(): float
    {
        return $this->avgRequestsPerHour;
    }

    public function getUniqueProviders(): int
    {
        return $this->uniqueProviders;
    }

    public function getUniqueStates(): int
    {
        return $this->uniqueStates;
    }

    public function getUniqueZipCodes(): int
    {
        return $this->uniqueZipCodes;
    }

    public function toDto(): ReportSummaryDto
    {
        return new ReportSummaryDto(
            id: $this->id,
            report_id: $this->reportId,
            total_requests: $this->totalRequests,
            success_rate: $this->successRate,
            failed_requests: $this->failedRequests,
            avg_requests_per_hour: $this->avgRequestsPerHour,
            unique_providers: $this->uniqueProviders,
            unique_states: $this->uniqueStates,
            unique_zip_codes: $this->uniqueZipCodes
        );
    }
}
