<?php

namespace App\Application\UseCases\Domain;

use App\Domain\Entities\Domain;
use App\Domain\Repositories\DomainRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class GetDomainByIdUseCase
{
    public function __construct(
        private DomainRepositoryInterface $domainRepository
    ) {}

    public function execute(int $id): Domain
    {
        $domain = $this->domainRepository->findById($id);
        
        if (!$domain) {
            throw new NotFoundException("Domain with ID {$id} not found");
        }
        
        return $domain;
    }
}

