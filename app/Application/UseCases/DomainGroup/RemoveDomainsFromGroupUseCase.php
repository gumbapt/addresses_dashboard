<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Domain\Repositories\DomainRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Exceptions\ValidationException;

class RemoveDomainsFromGroupUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository,
        private DomainRepositoryInterface $domainRepository
    ) {}

    /**
     * Remove multiple domains from a group
     * 
     * @param int $groupId
     * @param array $domainIds
     * @return array ['removed' => int]
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function execute(int $groupId, array $domainIds): array
    {
        // Verificar se o grupo existe
        $group = $this->domainGroupRepository->findById($groupId);
        if (!$group) {
            throw new NotFoundException("Domain group with ID {$groupId} not found.");
        }

        // Validar array de IDs
        if (empty($domainIds)) {
            throw new ValidationException('Domain IDs array cannot be empty.');
        }

        // Verificar se todos os domínios existem
        $existingDomains = $this->domainRepository->findByIds($domainIds);
        if (count($existingDomains) !== count($domainIds)) {
            throw new ValidationException('One or more domain IDs are invalid.');
        }

        // Remover domínios do grupo
        $removed = $this->domainGroupRepository->removeDomains($groupId, $domainIds);

        return [
            'removed' => $removed,
            'total_requested' => count($domainIds),
            'group_id' => $groupId,
            'group_name' => $group->name,
        ];
    }
}

