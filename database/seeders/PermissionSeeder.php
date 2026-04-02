<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Default permission catalogue (including backup-read, backup-create, backup-restore, backup-config-manage,
     * backup-audit-read). Run before RoleSeeder so the Sudo Admin role (super-admin) sync includes all rows.
     */
    public function run(): void
    {
        $permissions = [
            // Admin permissions
            [
                'slug' => 'admin-create',
                'name' => 'Create Administrator',
                'description' => 'Allows creating new administrators',
                'resource' => 'admin',
                'action' => 'create',
                'route' => 'admin/create',
            ],
            [
                'slug' => 'admin-read',
                'name' => 'View Administrator',
                'description' => 'Allows viewing administrator information',
                'resource' => 'admin',
                'action' => 'read',
                'route' => 'admin/read',
            ],
            [
                'slug' => 'admin-update',
                'name' => 'Edit Administrator',
                'description' => 'Allows editing administrator information',
                'resource' => 'admin',
                'action' => 'update',
            ],
            [
                'slug' => 'admin-delete',
                'name' => 'Delete Administrator',
                'description' => 'Allows deleting administrators',
                'resource' => 'admin',
                'action' => 'delete',
                'route' => 'admin/delete',
            ],
            [
                'slug' => 'admin-manage',
                'name' => 'Manage Administrators',
                'description' => 'Allows managing all aspects of administrators',
                'resource' => 'admin',
                'action' => 'manage',
                'route' => 'admin/manage',
            ],
            // User permissions
            [
                'slug' => 'user-create',
                'name' => 'Create User',
                'description' => 'Allows creating new users',
                'resource' => 'user',
                'action' => 'create',
                'route' => 'user/create',
            ],
            [
                'slug' => 'user-read',
                'name' => 'View User',
                'description' => 'Allows viewing user information',
                'resource' => 'user',
                'action' => 'read',
                'route' => 'user/read',
            ],
            [
                'slug' => 'user-update',
                'name' => 'Edit User',
                'description' => 'Allows editing user information',
                'resource' => 'user',
                'action' => 'update',
            ],
            [
                'slug' => 'user-delete',
                'name' => 'Delete User',
                'description' => 'Allows deleting users',
                'resource' => 'user',
                'action' => 'delete',
                'route' => 'user/delete',
            ],
            // Chat permissions
            [
                'slug' => 'chat-manage',
                'name' => 'Manage Chat',
                'description' => 'Allows managing chats and messages',
                'resource' => 'chat',
                'action' => 'manage',
                'route' => 'chat/manage',
            ],
            [
                'slug' => 'chat-read',
                'name' => 'View Chat',
                'description' => 'Allows viewing chats and messages',
                'resource' => 'chat',
                'action' => 'read',
                'route' => 'chat/read',
            ],
            // Role permissions
            [
                'slug' => 'role-assign',
                'name' => 'Assign Roles',
                'description' => 'Allows assigning roles to users and administrators',
                'resource' => 'role',
                'action' => 'assign',
                'route' => 'role/assign',
            ],
            [
                'slug' => 'role-manage',
                'name' => 'Manage Roles',
                'description' => 'Allows creating, editing and deleting roles',
                'resource' => 'role',
                'action' => 'manage',
                'route' => 'role/manage',
            ],
            [
                'slug' => 'role-read',
                'name' => 'View Role',
                'description' => 'Allows viewing roles',
                'resource' => 'role',
                'action' => 'read',
                'route' => 'role/read',
            ],
            [
                'slug' => 'role-delete',
                'name' => 'Delete Role',
                'description' => 'Allows deleting roles',
                'resource' => 'role',
                'action' => 'delete',
                'route' => 'role/delete',
            ],
            [
                'slug' => 'role-create',
                'name' => 'Create Role',
                'description' => 'Allows creating roles',
                'resource' => 'role',
                'action' => 'create',
                'route' => 'role/create',
            ],
            [
                'slug' => 'role-update',
                'name' => 'Update Role',
                'description' => 'Allows updating roles',
                'resource' => 'role',
                'action' => 'update',
                'route' => 'role/update',
            ],
            [
                'slug' => 'role-unassign',
                'name' => 'Unassign Role',
                'description' => 'Allows unassigning roles from users and administrators',
                'resource' => 'role',
                'action' => 'unassign',
                'route' => 'role/unassign',
            ],
            // Domain permissions
            [
                'slug' => 'domain-create',
                'name' => 'Create Domain',
                'description' => 'Allows creating new domains',
                'resource' => 'domain',
                'action' => 'create',
                'route' => 'domain/create',
            ],
            [
                'slug' => 'domain-read',
                'name' => 'View Domain',
                'description' => 'Allows viewing domain information',
                'resource' => 'domain',
                'action' => 'read',
                'route' => 'domain/read',
            ],
            [
                'slug' => 'domain-update',
                'name' => 'Update Domain',
                'description' => 'Allows updating domain information',
                'resource' => 'domain',
                'action' => 'update',
                'route' => 'domain/update',
            ],
            [
                'slug' => 'domain-delete',
                'name' => 'Delete Domain',
                'description' => 'Allows deleting domains',
                'resource' => 'domain',
                'action' => 'delete',
                'route' => 'domain/delete',
            ],
            [
                'slug' => 'domain-manage',
                'name' => 'Manage Domain',
                'description' => 'Allows managing all aspects of domains including API keys',
                'resource' => 'domain',
                'action' => 'manage',
                'route' => 'domain/manage',
            ],
            // Provider permissions
            [
                'slug' => 'provider-create',
                'name' => 'Create Provider',
                'description' => 'Allows creating new providers',
                'resource' => 'provider',
                'action' => 'create',
                'route' => 'provider/create',
            ],
            [
                'slug' => 'provider-read',
                'name' => 'View Provider',
                'description' => 'Allows viewing provider information',
                'resource' => 'provider',
                'action' => 'read',
                'route' => 'provider/read',
            ],
            [
                'slug' => 'provider-update',
                'name' => 'Update Provider',
                'description' => 'Allows updating provider information',
                'resource' => 'provider',
                'action' => 'update',
                'route' => 'provider/update',
            ],
            [
                'slug' => 'provider-delete',
                'name' => 'Delete Provider',
                'description' => 'Allows deleting providers',
                'resource' => 'provider',
                'action' => 'delete',
                'route' => 'provider/delete',
            ],
            [
                'slug' => 'provider-manage',
                'name' => 'Manage Provider',
                'description' => 'Allows managing all aspects of providers including technologies',
                'resource' => 'provider',
                'action' => 'manage',
                'route' => 'provider/manage',
            ],
            // Report permissions
            [
                'slug' => 'report-read',
                'name' => 'View Reports',
                'description' => 'Allows viewing reports and analytics',
                'resource' => 'report',
                'action' => 'read',
            ],
            [
                'slug' => 'report-manage',
                'name' => 'Manage Reports',
                'description' => 'Allows managing reports',
                'resource' => 'report',
                'action' => 'manage',
            ],
            // Dashboard permissions
            [
                'slug' => 'dashboard-view',
                'name' => 'View Dashboard',
                'description' => 'Allows viewing dashboards',
                'resource' => 'dashboard',
                'action' => 'view',
            ],
            [
                'slug' => 'dashboard-view-all',
                'name' => 'View All Domain Group Dashboards',
                'description' => 'Allows viewing dashboards for all domains in the assigned domain groups (category)',
                'resource' => 'dashboard',
                'action' => 'view_all',
            ],
            // Domain group ("category") permissions
            [
                'slug' => 'domain-group-create',
                'name' => 'Create Domain Group',
                'description' => 'Allows creating domain groups',
                'resource' => 'domain-group',
                'action' => 'create',
                'route' => 'domain-group/create',
            ],
            [
                'slug' => 'domain-group-read',
                'name' => 'View Domain Group',
                'description' => 'Allows viewing domain groups',
                'resource' => 'domain-group',
                'action' => 'read',
                'route' => 'domain-group/read',
            ],
            [
                'slug' => 'domain-group-update',
                'name' => 'Update Domain Group',
                'description' => 'Allows updating domain groups',
                'resource' => 'domain-group',
                'action' => 'update',
                'route' => 'domain-group/update',
            ],
            [
                'slug' => 'domain-group-delete',
                'name' => 'Delete Domain Group',
                'description' => 'Allows deleting domain groups',
                'resource' => 'domain-group',
                'action' => 'delete',
                'route' => 'domain-group/delete',
            ],
            [
                'slug' => 'admin-assign-users',
                'name' => 'Assign Users To Domains',
                'description' => 'Allows assigning admins/users to domain groups and domains within scope',
                'resource' => 'admin',
                'action' => 'assign_users',
                'route' => 'admin/assign-users',
            ],
            // Backup permissions (default catalogue). The Sudo Admin role (super-admin) receives all of these
            // via RoleSeeder; admin and manager roles exclude backup-* slugs explicitly.
            [
                'slug' => 'backup-read',
                'name' => 'View Backups',
                'description' => 'Allows listing and viewing backup metadata and files',
                'resource' => 'backup',
                'action' => 'read',
                'route' => 'backup/read',
            ],
            [
                'slug' => 'backup-create',
                'name' => 'Create Backups',
                'description' => 'Allows triggering manual or automatic backup jobs',
                'resource' => 'backup',
                'action' => 'create',
                'route' => 'backup/create',
            ],
            [
                'slug' => 'backup-restore',
                'name' => 'Restore Backups',
                'description' => 'Allows restoring application state from a backup',
                'resource' => 'backup',
                'action' => 'restore',
                'route' => 'backup/restore',
            ],
            [
                'slug' => 'backup-config-manage',
                'name' => 'Manage Backup Configuration',
                'description' => 'Allows viewing and updating periodic backup settings',
                'resource' => 'backup',
                'action' => 'config',
                'route' => 'backup/config',
            ],
            [
                'slug' => 'backup-audit-read',
                'name' => 'View Backup Audit Log',
                'description' => 'Allows viewing backup audit entries',
                'resource' => 'backup',
                'action' => 'audit',
                'route' => 'backup/audit',
            ],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'description' => $perm['description'],
                    'resource' => $perm['resource'],
                    'action' => $perm['action'],
                    'route' => $perm['route'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
