<?php

namespace App\Application\DTOs\Report\Global;

class ProviderRankingDTO
{
    public function __construct(
        public readonly int $rank,
        public readonly int $domainId,
        public readonly string $domainName,
        public readonly string $domainSlug,
        public readonly int $providerId,
        public readonly string $providerName,
        public readonly ?string $technology,
        public readonly int $totalRequests,
        public readonly float $avgSuccessRate,
        public readonly float $avgSpeed,
        public readonly int $totalReports,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly int $daysCovered,
    ) {}

    public function toArray(): array
    {
        return [
            'rank' => $this->rank,
            'domain_id' => $this->domainId,
            'domain_name' => $this->domainName,
            'domain_slug' => $this->domainSlug,
            'provider_id' => $this->providerId,
            'provider_name' => $this->providerName,
            'technology' => $this->technology,
            'total_requests' => $this->totalRequests,
            'avg_success_rate' => round($this->avgSuccessRate, 2),
            'avg_speed' => round($this->avgSpeed, 2),
            'total_reports' => $this->totalReports,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'days_covered' => $this->daysCovered,
        ];
    }
}

