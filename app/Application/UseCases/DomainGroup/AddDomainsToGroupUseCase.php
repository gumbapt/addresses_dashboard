<?php

namespace App\Application\UseCases\DomainGroup;

use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Domain\Repositories\DomainRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Exceptions\ValidationException;

class AddDomainsToGroupUseCase
{
    public function __construct(
        private DomainGroupRepositoryInterface $domainGroupRepository,
        private DomainRepositoryInterface $domainRepository
    ) {}

    /**
     * Add multiple domains to a group
     * 
     * @param int $groupId
     * @param array $domainIds
     * @return array ['added' => int, 'moved' => int, 'moved_from' => array]
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

        // Verificar quais domínios já estão em outros grupos (serão movidos)
        $domainsInOtherGroups = $this->domainGroupRepository->getDomainsInOtherGroups($domainIds, $groupId);
        $movedCount = count($domainsInOtherGroups);
        $newCount = count($domainIds) - $movedCount;

        // Verificar limite do grupo (considerar apenas domínios novos, não os movidos)
        if ($group->hasMaxDomainsLimit()) {
            $currentCount = $this->domainGroupRepository->getDomainsCount($groupId);
            $totalAfterAdd = $currentCount + $newCount; // Movidos não contam como novos
            
            if ($totalAfterAdd > $group->maxDomains) {
                $availableSlots = $group->maxDomains - $currentCount;
                throw new ValidationException(
                    "Cannot add " . $newCount . " new domains. Group '{$group->name}' only has {$availableSlots} available slots. " .
                    "Current: {$currentCount}/{$group->maxDomains}"
                );
            }
        }

        // Adicionar domínios ao grupo (os que estavam em outros grupos serão movidos)
        $updated = $this->domainGroupRepository->addDomains($groupId, $domainIds);

        return [
            'total_updated' => $updated,
            'domains_added' => $newCount,
            'domains_moved' => $movedCount,
            'moved_from' => $domainsInOtherGroups,
            'total_requested' => count($domainIds),
            'group_id' => $groupId,
            'group_name' => $group->name,
        ];
    }
}

