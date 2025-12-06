<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'System Super Administrator',
                'is_active' => true,
            ]
        );

        Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'System Administrator',
                'is_active' => true,
            ]
        );

        Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'description' => 'Regular user',
                'is_active' => true,
            ]
        );

        // Analytics User - Read-only access for viewing
        $analyticsUser = Role::firstOrCreate(
            ['slug' => 'analytics-user'],
            [
                'name' => 'Analytics User',
                'description' => 'Read-only access to view reports, analytics, domains, providers and dashboards',
                'is_active' => true,
            ]
        );  

        // Create read-only permissions if they don't exist
        $readPermissions = [
            [
                'slug' => 'report-read',
                'name' => 'View Reports',
                'description' => 'Allows viewing reports and analytics',
                'resource' => 'report',
                'action' => 'read',
            ],
            [
                'slug' => 'domain-read',
                'name' => 'View Domain',
                'description' => 'Allows viewing domain information',
                'resource' => 'domain',
                'action' => 'read',
            ],
            [
                'slug' => 'provider-read',
                'name' => 'View Provider',
                'description' => 'Allows viewing provider information',
                'resource' => 'provider',
                'action' => 'read',
            ],
            [
                'slug' => 'dashboard-view',
                'name' => 'View Dashboard',
                'description' => 'Allows viewing dashboards',
                'resource' => 'dashboard',
                'action' => 'view',
            ],
        ];

        // Create or find permissions and assign to role
        $permissionIds = [];
        foreach ($readPermissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                [
                    'name' => $permData['name'],
                    'description' => $permData['description'],
                    'resource' => $permData['resource'],
                    'action' => $permData['action'],
                    'is_active' => true,
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Sync all read permissions with the role (replaces existing ones)
        $analyticsUser->permissions()->sync($permissionIds);
    }
}
