<?php

namespace App\Application\DTOs\Report;

class AggregatedReportStatsDTO
{
    public function __construct(
        public readonly int $domainId,
        public readonly string $domainName,
        public readonly int $totalReports,
        public readonly ?string $firstReportDate,
        public readonly ?string $lastReportDate,
        public readonly array $summary,
        public readonly array $providers,
        public readonly array $states,
        public readonly array $cities,
        public readonly array $zipCodes,
        public readonly array $dailyTrends,
    ) {}

    public function toArray(): array
    {
        return [
            'domain' => [
                'id' => $this->domainId,
                'name' => $this->domainName,
            ],
            'period' => [
                'total_reports' => $this->totalReports,
                'first_report' => $this->firstReportDate,
                'last_report' => $this->lastReportDate,
                'days_covered' => $this->totalReports > 0 ? 
                    (strtotime($this->lastReportDate ?? 'now') - strtotime($this->firstReportDate ?? 'now')) / 86400 + 1 : 0,
            ],
            'summary' => $this->summary,
            'providers' => $this->providers,
            'geographic' => [
                'states' => $this->states,
                'cities' => $this->cities,
                'zip_codes' => $this->zipCodes,
            ],
            'trends' => $this->dailyTrends,
        ];
    }
}

