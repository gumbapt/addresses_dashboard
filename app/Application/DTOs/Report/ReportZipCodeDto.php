<?php

namespace App\Application\DTOs\Report;

class ReportZipCodeDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $report_id,
        public readonly int $zip_code_id,
        public readonly int $request_count,
        public readonly float $percentage
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'zip_code_id' => $this->zip_code_id,
            'request_count' => $this->request_count,
            'percentage' => $this->percentage,
        ];
    }
}
