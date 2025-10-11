<?php

namespace App\Application\UseCases\ISP;

use App\Domain\Repositories\DomainRepositoryInterface;

class RegenerateApiKeyUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(int $id): array
    {
        $domain = $this->domainRepository->regenerateApiKey($id);
        
        return $domain->toDto()->toArray();
    }
}

