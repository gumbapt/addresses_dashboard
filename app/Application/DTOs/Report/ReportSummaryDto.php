<?php

namespace App\Application\DTOs\Report;

class ReportSummaryDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $report_id,
        public readonly int $total_requests,
        public readonly float $success_rate,
        public readonly int $failed_requests,
        public readonly float $avg_requests_per_hour,
        public readonly int $unique_providers,
        public readonly int $unique_states,
        public readonly int $unique_zip_codes
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'total_requests' => $this->total_requests,
            'success_rate' => $this->success_rate,
            'failed_requests' => $this->failed_requests,
            'avg_requests_per_hour' => $this->avg_requests_per_hour,
            'unique_providers' => $this->unique_providers,
            'unique_states' => $this->unique_states,
            'unique_zip_codes' => $this->unique_zip_codes,
        ];
    }
}
