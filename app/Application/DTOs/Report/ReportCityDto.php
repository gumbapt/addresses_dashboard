<?php

namespace App\Application\DTOs\Report;

class ReportCityDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $report_id,
        public readonly int $city_id,
        public readonly int $request_count,
        public readonly array $zip_codes
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'city_id' => $this->city_id,
            'request_count' => $this->request_count,
            'zip_codes' => $this->zip_codes,
        ];
    }
}
