<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Repositories\ZipCodeRepositoryInterface;

class GetAllZipCodesUseCase
{
    public function __construct(
        private ZipCodeRepositoryInterface $zipCodeRepository
    ) {}

    public function execute(): array
    {
        return $this->zipCodeRepository->findAll();
    }

    public function executeByState(int $stateId): array
    {
        return $this->zipCodeRepository->findByState($stateId);
    }

    public function executeByCity(int $cityId): array
    {
        return $this->zipCodeRepository->findByCity($cityId);
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $stateId = null,
        ?int $cityId = null,
        ?bool $isActive = null
    ): array {
        return $this->zipCodeRepository->findAllPaginated($page, $perPage, $search, $stateId, $cityId, $isActive);
    }
}

