<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Role;
use App\Domain\Repositories\RoleRepositoryInterface;

class RoleRepository implements RoleRepositoryInterface
{
    public function findById(int $id): ?Role
    {

        
        $role = RoleModel::find($id);
        if (!$role) {
            return null;
        }
        return new Role($role->id, $role->name, $role->description, $role->display_name, $role->is_active, $role->permissions);
    }

    public function findByName(string $name): ?Role
    {
        $role = RoleModel::where('name', $name)->first();
        if (!$role) {
            return null;
        }
        return new Role($role->id, $role->name, $role->description, $role->display_name, $role->is_active, $role->permissions);
    }

    public function findAll(): array
    {
        return RoleModel::all()->map(function ($role) {
            return new Role($role->id, $role->name, $role->description, $role->display_name, $role->is_active, $role->permissions);
        })->toArray();
    }
    
    public function create(string $name, string $description, string $display_name): Role
    {
        $role = RoleModel::create([
            'name' => $name,
            'description' => $description,
            'display_name' => $display_name,
        ]);
    }

    public function update(int $id, string $name, string $description, string $display_name): void
    {
        RoleModel::where('id', $id)->update([
            'name' => $name,
            'description' => $description,
            'display_name' => $display_name,
        ]);
    }

    public function delete(int $id): void
    {
        RoleModel::where('id', $id)->delete();
    }
}