<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Admin;
use App\Domain\Repositories\AdminRepositoryInterface;
use App\Models\Admin as AdminModel;

class AdminRepository implements AdminRepositoryInterface
{
    public function findById(int $id): ?Admin
    {
        $admin = AdminModel::find($id);
        
        if (!$admin) {
            return null;
        }

        return new Admin(
            id: $admin->id,
            name: $admin->name,
            email: $admin->email,
            password: $admin->password,
            isActive: $admin->is_active,
            isSuperAdmin: $admin->is_super_admin,
            lastLoginAt: $admin->last_login_at
        );
    }

    public function findByEmail(string $email): ?Admin
    {
        $admin = AdminModel::where('email', $email)->first();
        
        if (!$admin) {
            return null;
        }

        return new Admin(
            id: $admin->id,
            name: $admin->name,
            email: $admin->email,
            password: $admin->password,
            isActive: $admin->is_active,
            isSuperAdmin: $admin->is_super_admin,
            lastLoginAt: $admin->last_login_at
        );
    }

    public function create(string $name, string $email, string $password): Admin
    {
        $admin = AdminModel::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_super_admin'=> false,
            'is_active' => true,
        ]);

        return $admin->toEntity();  
    }

    public function findByIdWithRolesAndPermissions(int $id): ?Admin
    {
        $admin = AdminModel::with(['roles.permissions'])->find($id);
        
        if (!$admin) {
            return null;
        }

        // For now, return basic admin entity - we'll enhance this later
        return new Admin(
            id: $admin->id,
            name: $admin->name,
            email: $admin->email,
            password: $admin->password,
            isActive: $admin->is_active,
            isSuperAdmin: $admin->is_super_admin,
            lastLoginAt: $admin->last_login_at
        );
    }

    public function updateLastLogin(int $id): void
    {
        AdminModel::where('id', $id)->update(['last_login_at' => now()]);
    }
} 