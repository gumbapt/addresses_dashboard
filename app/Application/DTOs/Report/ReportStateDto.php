<?php

namespace App\Application\DTOs\Report;

class ReportStateDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $report_id,
        public readonly int $state_id,
        public readonly int $request_count,
        public readonly float $success_rate,
        public readonly float $avg_speed
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'state_id' => $this->state_id,
            'request_count' => $this->request_count,
            'success_rate' => $this->success_rate,
            'avg_speed' => $this->avg_speed,
        ];
    }
}
