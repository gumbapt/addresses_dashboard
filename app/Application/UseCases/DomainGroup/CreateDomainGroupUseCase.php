<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Entities\DomainGroup;
use App\Domain\Repositories\DomainGroupRepositoryInterface;

class CreateDomainGroupUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository
    ) {}

    public function execute(
        string $name,
        string $slug,
        ?string $description = null,
        bool $isActive = true,
        ?array $settings = null,
        ?int $maxDomains = null,
        ?int $createdBy = null
    ): DomainGroup {
        return $this->domainGroupRepository->create(
            name: $name,
            slug: $slug,
            description: $description,
            isActive: $isActive,
            settings: $settings,
            maxDomains: $maxDomains,
            createdBy: $createdBy
        );
    }
}

