<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Entities\DomainGroup;
use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class GetDomainGroupByIdUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository
    ) {}

    public function execute(int $id): DomainGroup
    {
        $group = $this->domainGroupRepository->findById($id);
        
        if (!$group) {
            throw new NotFoundException("Domain group with ID {$id} not found");
        }
        
        return $group;
    }
}

