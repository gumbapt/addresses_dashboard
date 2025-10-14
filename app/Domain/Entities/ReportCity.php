<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Report\ReportCityDto;

class ReportCity
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $cityId,            // FK → cities (normalizado)
        public readonly int $requestCount,      // 19
        public readonly array $zipCodes         // [] array de zip codes
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getCityId(): int
    {
        return $this->cityId;
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    public function getZipCodes(): array
    {
        return $this->zipCodes;
    }

    public function hasZipCodes(): bool
    {
        return !empty($this->zipCodes);
    }

    public function getZipCodeCount(): int
    {
        return count($this->zipCodes);
    }

    public function toDto(): ReportCityDto
    {
        return new ReportCityDto(
            id: $this->id,
            report_id: $this->reportId,
            city_id: $this->cityId,
            request_count: $this->requestCount,
            zip_codes: $this->zipCodes
        );
    }
}
