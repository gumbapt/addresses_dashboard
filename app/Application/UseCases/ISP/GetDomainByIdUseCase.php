<?php

namespace App\Application\UseCases\ISP;

use App\Domain\Repositories\DomainRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class GetDomainByIdUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(int $id): array
    {
        $domain = $this->domainRepository->findById($id);
        
        if (!$domain) {
            throw new NotFoundException("Domain with ID {$id} not found");
        }
        
        return $domain->toDto()->toArray();
    }
}

