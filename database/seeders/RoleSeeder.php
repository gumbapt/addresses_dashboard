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

        // Analytics User - Acesso apenas para visualizar (read-only)
        $analyticsUser = Role::firstOrCreate(
            ['slug' => 'analytics-user'],
            [
                'name' => 'Analytics User',
                'description' => 'Acesso apenas de leitura para visualizar relatórios, analytics, domains, providers e dashboards',
                'is_active' => true,
            ]
        );

        // Criar permissões de leitura se não existirem
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

        // Criar ou buscar permissões e atribuir à role
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

        // Sincronizar todas as permissões de leitura com a role (substitui as existentes)
        $analyticsUser->permissions()->sync($permissionIds);
    }
}
