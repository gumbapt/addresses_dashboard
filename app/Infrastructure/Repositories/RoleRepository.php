<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Role;
use App\Domain\Repositories\RoleRepositoryInterface;
use App\Models\Role as RoleModel;
use Illuminate\Support\Str;

class RoleRepository implements RoleRepositoryInterface
{
    public function findById(int $id): ?Role
    {

        
        $role = RoleModel::find($id);
        if (!$role) {
            return null;
        }
        return new Role($role->id, $role->slug, $role->name, $role->description, $role->is_active, $role->permissions);
    }

    public function findBySlug(string $slug): ?Role
    {
        $role = RoleModel::where('slug', $slug)->first();
        if (!$role) {
            return null;
        }
        return new Role($role->id, $role->slug, $role->name, $role->description, $role->is_active, $role->permissions);
    }

    public function findByName(string $name): ?Role
    {
        $role = RoleModel::where('name', $name)->first();
        if (!$role) {
            return null;
        }
        return new Role($role->id, $role->slug, $role->name, $role->description, $role->is_active, $role->permissions);
    }

    public function findAll(): array
    {
        return RoleModel::all()->map(function ($role) {
            return $role->toEntity();
        })->toArray();
    }
    
    public function create(string $name, string $description): Role
    {

        $slug = Str::slug($name);
        $role = RoleModel::create([
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'is_active' => true,
        ]);
        return $role->toEntity();
    }

    public function update(int $id, string $slug, string $name, string $description): Role
    {
        $role = RoleModel::where('id', $id)->update([
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
        ]);
        return $role->refresh()->toEntity();
    }

    public function delete(int $id): void
    {
        RoleModel::where('id', $id)->delete();
    }

    public function attachPermissions(int $roleId, array $permissionIds): Role
    {
        $role = RoleModel::findOrFail($roleId);
        $role->permissions()->sync($permissionIds);
        return $role->refresh()->toEntity();
    }

}