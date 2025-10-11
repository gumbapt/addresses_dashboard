<?php

namespace App\Application\UseCases\Domain;

use App\Domain\Entities\Domain;
use App\Domain\Repositories\DomainRepositoryInterface;

class RegenerateApiKeyUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(int $id): Domain
    {
        return $this->domainRepository->regenerateApiKey($id);
    }
}

