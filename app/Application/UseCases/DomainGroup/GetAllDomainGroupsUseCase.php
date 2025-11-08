<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Repositories\DomainGroupRepositoryInterface;

class GetAllDomainGroupsUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository
    ) {}

    public function execute(): array
    {
        return $this->domainGroupRepository->findAll();
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        return $this->domainGroupRepository->findAllPaginated($page, $perPage, $search, $isActive);
    }

    public function executeActive(): array
    {
        return $this->domainGroupRepository->findActive();
    }
}

