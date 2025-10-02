<?php

namespace App\Application\UseCases\Admin\Authorization;

use App\Models\Admin;
use App\Domain\Repositories\PermissionRepositoryInterface;

class CheckAdminPermissionUseCase
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository
    ) {}

    public function execute(Admin $admin, string $permissionSlug): bool
    {
        // Super admin bypass
        if ($admin->isSuperAdmin()) {
            return true;
        }

        // Check if admin has the required permission through roles
        $roles = $admin->roles()->with('permissions')->get();
        
        foreach ($roles as $role) {
            if (!$role->is_active) {
                continue;
            }

            foreach ($role->permissions as $permission) {
                if ($permission->slug === $permissionSlug && $permission->is_active) {
                    return true;
                }
            }
        }

        return false;
    }

    public function executeMultiple(Admin $admin, array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permissionSlug) {
            if (!$this->execute($admin, $permissionSlug)) {
                return false;
            }
        }

        return true;
    }

    public function executeAny(Admin $admin, array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permissionSlug) {
            if ($this->execute($admin, $permissionSlug)) {
                return true;
            }
        }

        return false;
    }
}
