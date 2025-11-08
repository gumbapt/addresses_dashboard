<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Domain\Exceptions\ValidationException;

class DeleteDomainGroupUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository
    ) {}

    public function execute(int $id): bool
    {
        // Verificar se tem domínios associados
        $domainsCount = $this->domainGroupRepository->getDomainsCount($id);
        
        if ($domainsCount > 0) {
            throw new ValidationException(
                "Cannot delete domain group with {$domainsCount} associated domains. Please remove or reassign the domains first."
            );
        }
        
        return $this->domainGroupRepository->delete($id);
    }
}

