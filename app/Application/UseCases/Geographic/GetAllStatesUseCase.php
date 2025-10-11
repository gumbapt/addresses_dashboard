<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Repositories\StateRepositoryInterface;

class GetAllStatesUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository
    ) {}

    public function execute(): array
    {
        return $this->stateRepository->findAll();
    }

    public function executeActive(): array
    {
        return $this->stateRepository->findAllActive();
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        return $this->stateRepository->findAllPaginated($page, $perPage, $search, $isActive);
    }
}

