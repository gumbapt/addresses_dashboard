<?php

namespace App\Domain\Services;

use App\Models\Admin;
use App\Models\DomainGroup;
use App\Models\AdminDomainGroup;
use Illuminate\Support\Facades\DB;

class AdminDomainGroupService
{
    /**
     * Atribuir grupos de domínios a um admin
     * 
     * @param Admin $admin Admin que receberá os grupos
     * @param array $domainGroupIds IDs dos grupos a atribuir
     * @param Admin $assignedBy Admin que está fazendo a atribuição
     * @return array IDs dos grupos atribuídos
     */
    public function assignDomainGroupsToAdmin(Admin $admin, array $domainGroupIds, Admin $assignedBy): array
    {
        // Verificar se o admin que está atribuindo tem permissão para atribuir esses grupos
        $assignableGroups = $assignedBy->getAssignableDomainGroups();
        
        // Filtrar apenas grupos que o admin pode atribuir
        $validGroupIds = array_intersect($domainGroupIds, $assignableGroups);
        
        if (empty($validGroupIds)) {
            return [];
        }

        $assigned = [];
        foreach ($validGroupIds as $groupId) {
            // Verificar se já existe associação ativa
            $existing = AdminDomainGroup::where('admin_id', $admin->id)
                ->where('domain_group_id', $groupId)
                ->where('is_active', true)
                ->first();

            if (!$existing) {
                AdminDomainGroup::create([
                    'admin_id' => $admin->id,
                    'domain_group_id' => $groupId,
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy->id,
                    'is_active' => true,
                ]);
                $assigned[] = $groupId;
            }
        }

        return $assigned;
    }

    /**
     * Remover grupos de domínios de um admin
     * 
     * @param Admin $admin Admin que terá os grupos removidos
     * @param array $domainGroupIds IDs dos grupos a remover
     * @param Admin $removedBy Admin que está fazendo a remoção
     * @return array IDs dos grupos removidos
     */
    public function removeDomainGroupsFromAdmin(Admin $admin, array $domainGroupIds, Admin $removedBy): array
    {
        // Verificar se o admin que está removendo tem permissão para remover desses grupos
        $assignableGroups = $removedBy->getAssignableDomainGroups();
        
        // Filtrar apenas grupos que o admin pode gerenciar
        $validGroupIds = array_intersect($domainGroupIds, $assignableGroups);
        
        if (empty($validGroupIds)) {
            return [];
        }

        $removed = [];
        foreach ($validGroupIds as $groupId) {
            $updated = AdminDomainGroup::where('admin_id', $admin->id)
                ->where('domain_group_id', $groupId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            if ($updated) {
                $removed[] = $groupId;
            }
        }

        return $removed;
    }

    /**
     * Herdar grupos de domínios de um admin para outro (nível 2 -> nível 3)
     * Quando um admin nível 2 cria um admin nível 3, o nível 3 herda os grupos do nível 2
     * 
     * @param Admin $parentAdmin Admin nível 2 (que possui os grupos)
     * @param Admin $childAdmin Admin nível 3 (que receberá os grupos)
     * @return array IDs dos grupos herdados
     */
    public function inheritDomainGroups(Admin $parentAdmin, Admin $childAdmin): array
    {
        // Obter grupos do admin pai
        $parentGroups = $parentAdmin->getAccessibleDomainGroups();
        
        if (empty($parentGroups)) {
            return [];
        }

        // Atribuir os grupos ao admin filho
        return $this->assignDomainGroupsToAdmin($childAdmin, $parentGroups, $parentAdmin);
    }

    /**
     * Obter todos os domínios acessíveis através dos grupos de um admin
     * 
     * @param Admin $admin
     * @return array IDs dos domínios acessíveis
     */
    public function getAccessibleDomainsFromGroups(Admin $admin): array
    {
        // Sudo admin tem acesso a todos os domínios
        if ($admin->is_super_admin) {
            return DomainGroup::with('domains')->get()
                ->pluck('domains')
                ->flatten()
                ->pluck('id')
                ->unique()
                ->toArray();
        }

        // Obter grupos do admin
        $groupIds = $admin->getAccessibleDomainGroups();
        
        if (empty($groupIds)) {
            return [];
        }

        // Obter todos os domínios desses grupos
        return DomainGroup::whereIn('id', $groupIds)
            ->with('domains')
            ->get()
            ->pluck('domains')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();
    }

    /**
     * Verificar se admin pode atribuir um grupo específico
     * 
     * @param Admin $admin Admin que quer atribuir
     * @param int $domainGroupId ID do grupo
     * @return bool
     */
    public function canAssignDomainGroup(Admin $admin, int $domainGroupId): bool
    {
        $assignableGroups = $admin->getAssignableDomainGroups();
        return in_array($domainGroupId, $assignableGroups);
    }
}

