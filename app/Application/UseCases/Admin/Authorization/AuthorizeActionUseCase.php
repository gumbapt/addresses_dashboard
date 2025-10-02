<?php

namespace App\Application\UseCases\Admin\Authorization;

use App\Models\Admin;
use App\Domain\Exceptions\AuthorizationException;

class AuthorizeActionUseCase
{
    public function __construct(
        private CheckAdminPermissionUseCase $checkPermissionUseCase
    ) {}

    public function execute(Admin $admin, string $permissionSlug): void
    {
        if (!$this->checkPermissionUseCase->execute($admin, $permissionSlug)) {
            throw new AuthorizationException(
                "Admin {$admin->id} does not have permission to perform this action. Required permission: {$permissionSlug}"
            );
        }
    }

    public function executeMultiple(Admin $admin, array $permissionSlugs): void
    {
        foreach ($permissionSlugs as $permissionSlug) {
            $this->execute($admin, $permissionSlug);
        }
    }

    public function executeAny(Admin $admin, array $permissionSlugs): void
    {
        if (!$this->checkPermissionUseCase->executeAny($admin, $permissionSlugs)) {
            $permissionsList = implode(', ', $permissionSlugs);
            throw new AuthorizationException(
                "Admin {$admin->id} does not have any of the required permissions: {$permissionsList}"
            );
        }
    }
}
