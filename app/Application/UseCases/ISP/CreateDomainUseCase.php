<?php

namespace App\Application\UseCases\ISP;

use App\Domain\Repositories\DomainRepositoryInterface;

class CreateDomainUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(
        string $name,
        string $domainUrl,
        ?string $siteId = null,
        string $timezone = 'UTC',
        ?string $wordpressVersion = null,
        ?string $pluginVersion = null,
        ?array $settings = null
    ): array {
        $domain = $this->domainRepository->create(
            $name,
            $domainUrl,
            $siteId,
            $timezone,
            $wordpressVersion,
            $pluginVersion,
            $settings
        );
        
        return $domain->toDto()->toArray();
    }
}

