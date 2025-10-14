<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Report\ReportStateDto;

class ReportState
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $stateId,           // FK → states (normalizado)
        public readonly int $requestCount,      // 239
        public readonly float $successRate,     // 0
        public readonly float $avgSpeed         // 0
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getStateId(): int
    {
        return $this->stateId;
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function getAvgSpeed(): float
    {
        return $this->avgSpeed;
    }

    public function toDto(): ReportStateDto
    {
        return new ReportStateDto(
            id: $this->id,
            report_id: $this->reportId,
            state_id: $this->stateId,
            request_count: $this->requestCount,
            success_rate: $this->successRate,
            avg_speed: $this->avgSpeed
        );
    }
}
