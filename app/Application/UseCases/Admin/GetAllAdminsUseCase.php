<?php

namespace App\Application\UseCases\Admin;

use App\Domain\Repositories\AdminRepositoryInterface;

class GetAllAdminsUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository
    ) {}

    public function execute(): array
    {
        $admins = $this->adminRepository->findAll();
        
        return array_map(function ($admin) {
            return $admin->toDto()->toArray();
        }, $admins);
    }
}

