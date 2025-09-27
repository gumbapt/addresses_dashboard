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

        return [
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => $admin->isActive,
                'last_login_at' => $admin->lastLoginAt?->format('Y-m-d H:i:s')
            ],
            'token' => $token
        ];
    }
} 