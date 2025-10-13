<?php

namespace App\Application\DTOs\Provider;

class ProviderDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $website = null,
        public readonly ?string $logo_url = null,
        public readonly ?string $description = null,
        public readonly array $technologies = [],
        public readonly bool $is_active = true,
        public readonly ?int $total_reports = null,
        public readonly ?float $avg_requests = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            'description' => $this->description,
            'technologies' => $this->technologies,
            'is_active' => $this->is_active,
        ];

        // Include aggregated data if available
        if ($this->total_reports !== null) {
            $data['total_reports'] = $this->total_reports;
        }

        if ($this->avg_requests !== null) {
            $data['avg_requests'] = $this->avg_requests;
        }

        return $data;
    }
}
