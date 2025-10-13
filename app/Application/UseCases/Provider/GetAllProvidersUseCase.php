<?php

namespace App\Application\UseCases\Provider;

use App\Domain\Repositories\ProviderRepositoryInterface;

class GetAllProvidersUseCase
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository
    ) {}

    public function execute(): array
    {
        return $this->providerRepository->findAll();
    }

    public function executeByTechnology(string $technology): array
    {
        return $this->providerRepository->findByTechnology($technology);
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?string $technology = null,
        ?bool $isActive = null
    ): array {
        return $this->providerRepository->findAllPaginated($page, $perPage, $search, $technology, $isActive);
    }
}
