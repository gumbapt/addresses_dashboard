<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Entities\DomainGroup;
use App\Domain\Repositories\DomainGroupRepositoryInterface;

class UpdateDomainGroupUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository
    ) {}

    public function execute(
        int $id,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?bool $isActive = null,
        ?array $settings = null,
        ?int $maxDomains = null,
        ?int $updatedBy = null
    ): DomainGroup {
        return $this->domainGroupRepository->update(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            isActive: $isActive,
            settings: $settings,
            maxDomains: $maxDomains,
            updatedBy: $updatedBy
        );
    }
}

