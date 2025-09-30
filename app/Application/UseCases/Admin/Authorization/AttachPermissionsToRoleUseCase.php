<?php

namespace App\Application\UseCases\Admin\Authorization;

use App\Domain\Entities\Role;
use App\Domain\Repositories\PermissionRepositoryInterface;
use App\Domain\Repositories\RoleRepositoryInterface;

class AttachPermissionsToRoleUseCase
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function execute(int $roleId, array $permissionIds): Role
    {
        $permissions = $this->permissionRepository->findByIds($permissionIds);
        $this->roleRepository->attachPermissions($roleId, $permissions);
        return $this->roleRepository->findById($roleId);
    }
}