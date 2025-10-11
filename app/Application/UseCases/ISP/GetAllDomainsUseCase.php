<?php

namespace App\Application\UseCases\ISP;

use App\Domain\Repositories\DomainRepositoryInterface;

class GetAllDomainsUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(): array
    {
        $domains = $this->domainRepository->findAll();
        
        return array_map(function ($domain) {
            return $domain->toDto()->toArray();
        }, $domains);
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        $result = $this->domainRepository->findAllPaginated($page, $perPage, $search, $isActive);
        
        // Convert entities to DTOs
        $result['data'] = array_map(function ($domain) {
            return $domain->toDto()->toArray();
        }, $result['data']);
        
        return $result;
    }
}

