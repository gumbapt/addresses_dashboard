<?php

namespace App\Application\UseCases\Domain;

use App\Domain\Repositories\DomainRepositoryInterface;

class GetAllDomainsUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(): array
    {
        return $this->domainRepository->findAll();
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        return $this->domainRepository->findAllPaginated($page, $perPage, $search, $isActive);
    }
}

