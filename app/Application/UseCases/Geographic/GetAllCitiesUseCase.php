<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Repositories\CityRepositoryInterface;

class GetAllCitiesUseCase
{
    public function __construct(
        private CityRepositoryInterface $cityRepository
    ) {}

    public function execute(): array
    {
        return $this->cityRepository->findAll();
    }

    public function executeByState(int $stateId): array
    {
        return $this->cityRepository->findByState($stateId);
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $stateId = null,
        ?bool $isActive = null
    ): array {
        return $this->cityRepository->findAllPaginated($page, $perPage, $search, $stateId, $isActive);
    }
}

