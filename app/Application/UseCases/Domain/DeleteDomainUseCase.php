<?php

namespace App\Application\UseCases\Domain;

use App\Domain\Repositories\DomainRepositoryInterface;

class DeleteDomainUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(int $id): void
    {
        $this->domainRepository->delete($id);
    }
}

