<?php

namespace App\Application\UseCases\Domain;

use App\Domain\Entities\Domain;
use App\Domain\Repositories\DomainRepositoryInterface;

class UpdateDomainUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(
        int $id,
        ?string $name = null,
        ?string $domainUrl = null,
        ?string $siteId = null,
        ?bool $isActive = null,
        ?string $timezone = null,
        ?string $wordpressVersion = null,
        ?string $pluginVersion = null,
        ?array $settings = null
    ): Domain {
        return $this->domainRepository->update(
            $id,
            $name,
            $domainUrl,
            $siteId,
            $isActive,
            $timezone,
            $wordpressVersion,
            $pluginVersion,
            $settings
        );
    }
}

