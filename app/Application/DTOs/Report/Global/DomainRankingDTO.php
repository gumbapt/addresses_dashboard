<?php

namespace App\Application\DTOs\Report\Global;

class DomainRankingDTO
{
    public function __construct(
        public readonly int $rank,
        public readonly int $domainId,
        public readonly string $domainName,
        public readonly string $domainSlug,
        public readonly int $totalRequests,
        public readonly float $successRate,
        public readonly float $avgSpeed,
        public readonly float $score,
        public readonly int $totalReports,
        public readonly int $uniqueProviders,
        public readonly int $uniqueStates,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly int $daysCovered,
    ) {}

    public function toArray(): array
    {
        return [
            'rank' => $this->rank,
            'domain' => [
                'id' => $this->domainId,
                'name' => $this->domainName,
                'slug' => $this->domainSlug,
            ],
            'metrics' => [
                'total_requests' => $this->totalRequests,
                'success_rate' => round($this->successRate, 2),
                'avg_speed' => round($this->avgSpeed, 2),
                'score' => round($this->score, 2),
                'unique_providers' => $this->uniqueProviders,
                'unique_states' => $this->uniqueStates,
            ],
            'coverage' => [
                'total_reports' => $this->totalReports,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
                'days_covered' => $this->daysCovered,
            ],
        ];
    }
}

