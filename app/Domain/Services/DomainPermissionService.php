<?php

namespace App\Domain\Services;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\Role;
use App\Models\RoleDomainPermission;

class DomainPermissionService
{
    /**
     * Verifica se um admin tem acesso a um domínio específico
     */
    public function canAccessDomain(Admin $admin, int $domainId): bool
    {
        // 0. Super Admins têm acesso a TUDO
        if ($admin->isSuperAdmin()) {
            return true;
        }

        // 1. Verificar se tem permissão global
        if ($this->hasGlobalDomainAccess($admin)) {
            return true;
        }

        // 2. Verificar se tem acesso ao domínio específico
        return $this->hasAssignedDomainAccess($admin, $domainId);
    }

    /**
     * Verifica se admin tem acesso global a todos os domínios
     */
    public function hasGlobalDomainAccess(Admin $admin): bool
    {
        // Super Admins têm acesso global automaticamente
        if ($admin->isSuperAdmin()) {
            return true;
        }

        foreach ($admin->roles as $role) {
            $permissions = $role->permissions->pluck('slug');
            if ($permissions->contains('domain.access.all')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se admin tem acesso a um domínio específico via role
     */
    public function hasAssignedDomainAccess(Admin $admin, int $domainId): bool
    {
        $roleIds = $admin->roles->pluck('id');

        return RoleDomainPermission::whereIn('role_id', $roleIds)
            ->where('domain_id', $domainId)
            ->where('can_view', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Retorna lista de IDs de domínios acessíveis por um admin
     */
    public function getAccessibleDomains(Admin $admin): array
    {
        // Super Admins têm acesso a TODOS os domínios
        if ($admin->isSuperAdmin()) {
            return Domain::where('is_active', true)->pluck('id')->toArray();
        }

        // Se tem acesso global via permissão, retorna todos
        if ($this->hasGlobalDomainAccess($admin)) {
            return Domain::where('is_active', true)->pluck('id')->toArray();
        }

        // Senão, retorna apenas os domínios atribuídos
        $roleIds = $admin->roles->pluck('id');

        return RoleDomainPermission::whereIn('role_id', $roleIds)
            ->where('can_view', true)
            ->where('is_active', true)
            ->pluck('domain_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Retorna domínios acessíveis com detalhes completos
     */
    public function getAccessibleDomainsWithDetails(Admin $admin): array
    {
        $domainIds = $this->getAccessibleDomains($admin);
        
        if (empty($domainIds)) {
            return [];
        }

        return Domain::whereIn('id', $domainIds)
            ->where('is_active', true)
            ->get()
            ->map(function($domain) use ($admin) {
                $permissions = $this->getDomainPermissions($admin, $domain->id);
                
                return [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'slug' => $domain->slug,
                    'domain_url' => $domain->domain_url,
                    'permissions' => $permissions,
                ];
            })
            ->toArray();
    }

    /**
     * Retorna permissões específicas de um admin para um domínio
     */
    public function getDomainPermissions(Admin $admin, int $domainId): array
    {
        // Super Admins têm TODAS as permissões
        if ($admin->isSuperAdmin()) {
            return [
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_submit_reports' => true,
            ];
        }

        // Se tem acesso global via permissão, retorna todas as permissões
        if ($this->hasGlobalDomainAccess($admin)) {
            return [
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_submit_reports' => true,
            ];
        }

        // Buscar permissões específicas
        $roleIds = $admin->roles->pluck('id');

        $permission = RoleDomainPermission::whereIn('role_id', $roleIds)
            ->where('domain_id', $domainId)
            ->where('is_active', true)
            ->first();

        if (!$permission) {
            return [
                'can_view' => false,
                'can_edit' => false,
                'can_delete' => false,
                'can_submit_reports' => false,
            ];
        }

        return [
            'can_view' => $permission->can_view,
            'can_edit' => $permission->can_edit,
            'can_delete' => $permission->can_delete,
            'can_submit_reports' => $permission->can_submit_reports,
        ];
    }

    /**
     * Atribui domínios a uma role
     */
    public function assignDomainsToRole(
        Role $role,
        array $domainIds,
        Admin $assignedBy,
        array $permissions = []
    ): void {
        foreach ($domainIds as $domainId) {
            RoleDomainPermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'domain_id' => $domainId,
                ],
                [
                    'can_view' => $permissions['can_view'] ?? true,
                    'can_edit' => $permissions['can_edit'] ?? false,
                    'can_delete' => $permissions['can_delete'] ?? false,
                    'can_submit_reports' => $permissions['can_submit_reports'] ?? false,
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy->id,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Remove domínios de uma role
     */
    public function revokeDomainsFromRole(Role $role, array $domainIds): void
    {
        RoleDomainPermission::where('role_id', $role->id)
            ->whereIn('domain_id', $domainIds)
            ->delete();
    }

    /**
     * Retorna domínios atribuídos a uma role
     */
    public function getRoleDomains(Role $role): array
    {
        return RoleDomainPermission::where('role_id', $role->id)
            ->where('is_active', true)
            ->with('domain')
            ->get()
            ->map(fn($rdp) => [
                'domain_id' => $rdp->domain_id,
                'domain_name' => $rdp->domain->name,
                'domain_slug' => $rdp->domain->slug,
                'can_view' => $rdp->can_view,
                'can_edit' => $rdp->can_edit,
                'can_delete' => $rdp->can_delete,
                'can_submit_reports' => $rdp->can_submit_reports,
                'assigned_at' => $rdp->assigned_at->toISOString(),
            ])
            ->toArray();
    }
}

