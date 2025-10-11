<?php

namespace App\Application\UseCases\Domain;

use App\Domain\Entities\Domain;
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
    ): Domain {
        return $this->domainRepository->create(
            $name,
            $domainUrl,
            $siteId,
            $timezone,
            $wordpressVersion,
            $pluginVersion,
            $settings
        );
    }
}

