<?php

namespace App\Infrastructure\Services;

use App\Domain\Entities\Admin;
use App\Domain\Services\AdminAuthServiceInterface;
use App\Domain\Repositories\AdminRepositoryInterface;
use App\Domain\Exceptions\AuthenticationException;
use App\Models\Admin as AdminModel;

class AdminAuthService implements AdminAuthServiceInterface
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository
    ) {}

    public function authenticate(string $email, string $password): ?Admin
    {
        $admin = $this->adminRepository->findByEmail($email);

        if (!$admin || !$admin->validatePassword($password)) {
            throw new AuthenticationException();
        }

        if (!$admin->isActive()) {
            throw new AuthenticationException('Admin account is not active');
        }

        // Atualizar último login
        $this->adminRepository->updateLastLogin($admin->id);

        return $admin;
    }

    public function generateToken(Admin $admin): string
    {
        // Buscar o modelo Eloquent para usar Sanctum
        $adminModel = AdminModel::find($admin->id);
        
        if (!$adminModel) {
            throw new AuthenticationException('Admin model not found');
        }

        // Gerar token usando Sanctum
        return $adminModel->createToken('admin-api')->plainTextToken;
    }
} 