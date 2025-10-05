<?php
namespace App\Application\UseCases\Auth;

use App\Domain\Services\AdminAuthServiceInterface;

class AdminLoginUseCase
{
    public function __construct(
        private AdminAuthServiceInterface $adminAuthService
    ) {}

    public function execute(string $email, string $password): array
    {
        $admin = $this->adminAuthService->authenticate($email, $password);
        $token = $this->adminAuthService->generateToken($admin);
        $adminPermissions = $this->adminAuthService->getAdminPermissions($admin);
        
        return [
            'admin' => $admin->toDto()->toArray(),
            'token' => $token,
            'permissions' => $adminPermissions
        ];
    }
} 