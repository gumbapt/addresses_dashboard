<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Three fixed roles:
     * - super-admin (Sudo Admin): full access, including all domain permissions (create/read/update/delete/manage),
     *   all domain-group permissions, providers, reports, dashboards, roles, users, etc.—every active permission.
     * - admin: cannot create domains, providers, or domain groups; can manage users within their scope.
     * - manager: full operational access except adding/removing users and assigning/unassigning roles.
     */
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Sudo Admin',
                'description' => 'Full access: create and manage domains, providers, and domain groups (categories), plus every other permission in the system.',
                'is_active' => true,
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Cannot create domains, providers, or domain groups; can add and manage users for their domains and groups.',
                'is_active' => true,
            ]
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Category Manager',
                'description' => 'Can perform all actions except adding or removing users and assigning or unassigning roles. Can view dashboards for all domains in their category (domain groups).',
                'is_active' => true,
            ]
        );

        // Sudo Admin: sync every active permission, including the full domain set:
        // domain-create, domain-read, domain-update, domain-delete, domain-manage, and all domain-group-* slugs.
        // Extra domain scopes (e.g. domain.access.all) are attached when DomainPermissionSeeder runs afterward.
        $allPermissionIds = Permission::query()->where('is_active', true)->pluck('id')->all();
        $superAdmin->permissions()->sync($allPermissionIds);

        // Admin: cannot create domains/providers/domain groups; cannot delete admins or manage role definitions.
        $adminExcludedSlugs = [
            'domain-create',
            'provider-create',
            'domain-group-create',
            'domain-group-update',
            'domain-group-delete',
            'admin-delete',
            'role-manage',
            'role-create',
            'role-update',
            'role-delete',
        ];
        $adminPermissionIds = Permission::query()
            ->where('is_active', true)
            ->whereNotIn('slug', $adminExcludedSlugs)
            ->pluck('id')
            ->all();
        $adminRole->permissions()->sync($adminPermissionIds);

        $managerExcludedSlugs = [
            'admin-create',
            'admin-delete',
            'user-create',
            'user-delete',
            'role-assign',
            'role-unassign',
        ];
        $managerPermissionIds = Permission::query()
            ->where('is_active', true)
            ->whereNotIn('slug', $managerExcludedSlugs)
            ->pluck('id')
            ->all();
        $managerRole->permissions()->sync($managerPermissionIds);
    }
}
