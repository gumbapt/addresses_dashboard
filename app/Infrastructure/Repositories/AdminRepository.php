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
        return $admin->toEntity();
    }

    public function findByEmail(string $email): ?Admin
    {
        $admin = AdminModel::where('email', $email)->first();
        if (!$admin) {
            return null;
        }

        return $admin->toEntity();
    }

    public function findAll(): array
    {
        $admins = AdminModel::all();
        
        return $admins->map(function ($admin) {
            return $admin->toEntity();
        })->toArray();
    }

    public function create(string $name, string $email, string $password, bool $isActive = true): Admin
    {
        $admin = AdminModel::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'is_super_admin'=> false,
            'is_active' => $isActive,
        ]);

        return $admin->toEntity();  
    }

    public function update(
        int $id,
        ?string $name = null,
        ?string $email = null,
        ?string $password = null,
        ?bool $isActive = null
    ): Admin {
        $admin = AdminModel::findOrFail($id);
        
        $updateData = [];
        if ($name !== null) $updateData['name'] = $name;
        if ($email !== null) $updateData['email'] = $email;
        if ($password !== null) $updateData['password'] = bcrypt($password);
        if ($isActive !== null) $updateData['is_active'] = $isActive;
        
        $admin->update($updateData);
        
        return $admin->fresh()->toEntity();
    }

    public function delete(int $id): void
    {
        AdminModel::findOrFail($id)->delete();
    }

    public function findByIdWithRolesAndPermissions(int $id): ?Admin
    {
        $admin = AdminModel::with(['roles.permissions'])->find($id);
        
        if (!$admin) {
            return null;
        }

        return $admin->toEntity();

    }

    public function updateLastLogin(int $id): void
    {
        AdminModel::where('id', $id)->update(['last_login_at' => now()]);
    }
} 