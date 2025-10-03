<?php

namespace App\Application\UseCases\Admin\Authorization;

use App\Domain\Entities\Role;
use App\Domain\Repositories\RoleRepositoryInterface;

class UpdateRoleUseCase
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function execute(int $id, string $name, string $description): Role
    {
        
        return $this->roleRepository->update($id, $name, $description);
    }
}