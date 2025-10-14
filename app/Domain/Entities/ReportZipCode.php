<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Report\ReportZipCodeDto;

class ReportZipCode
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $zipCodeId,         // FK → zip_codes (normalizado)
        public readonly int $requestCount,      // 13
        public readonly float $percentage       // 0 (será calculado)
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getZipCodeId(): int
    {
        return $this->zipCodeId;
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function toDto(): ReportZipCodeDto
    {
        return new ReportZipCodeDto(
            id: $this->id,
            report_id: $this->reportId,
            zip_code_id: $this->zipCodeId,
            request_count: $this->requestCount,
            percentage: $this->percentage
        );
    }
}
