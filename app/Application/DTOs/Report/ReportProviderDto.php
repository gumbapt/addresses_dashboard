<?php

namespace App\Application\DTOs\Report;

class ReportProviderDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $report_id,
        public readonly int $provider_id,
        public readonly string $original_name,
        public readonly string $technology,
        public readonly int $total_count,
        public readonly float $success_rate,
        public readonly float $avg_speed,
        public readonly ?int $rank_position
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'provider_id' => $this->provider_id,
            'original_name' => $this->original_name,
            'technology' => $this->technology,
            'total_count' => $this->total_count,
            'success_rate' => $this->success_rate,
            'avg_speed' => $this->avg_speed,
            'rank_position' => $this->rank_position,
        ];
    }
}
