<?php

namespace App\Application\DTOs\Report;

class ReportDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $domain_id,
        public readonly string $report_date,
        public readonly string $report_period_start,
        public readonly string $report_period_end,
        public readonly string $generated_at,
        public readonly int $total_processing_time,
        public readonly string $data_version,
        public readonly array $raw_data,
        public readonly string $status,
        public readonly string $created_at,
        public readonly string $updated_at
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'report_date' => $this->report_date,
            'report_period_start' => $this->report_period_start,
            'report_period_end' => $this->report_period_end,
            'generated_at' => $this->generated_at,
            'total_processing_time' => $this->total_processing_time,
            'data_version' => $this->data_version,
            'raw_data' => $this->raw_data,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
