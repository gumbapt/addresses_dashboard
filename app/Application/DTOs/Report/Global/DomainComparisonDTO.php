<?php

namespace App\Application\DTOs\Report\Global;

class DomainComparisonDTO
{
    public function __construct(
        public readonly int $domainId,
        public readonly string $domainName,
        public readonly array $metrics,
        public readonly ?array $vsBaseDomain = null,
    ) {}

    public function toArray(): array
    {
        $result = [
            'domain' => [
                'id' => $this->domainId,
                'name' => $this->domainName,
            ],
            'metrics' => $this->metrics,
        ];

        if ($this->vsBaseDomain !== null) {
            $result['comparison'] = $this->vsBaseDomain;
        }

        return $result;
    }
}

