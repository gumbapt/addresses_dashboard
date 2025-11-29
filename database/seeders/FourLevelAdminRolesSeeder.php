<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class FourLevelAdminRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Criando 4 níveis de admin...');
        $this->command->newLine();

        // Criar permissões necessárias
        $this->createPermissions();

        // Criar os 4 roles
        $this->createRoles();

        $this->command->newLine();
        $this->command->info('✅ 4 níveis de admin criados com sucesso!');
    }

    private function createPermissions(): void
    {
        $this->command->info('📋 Criando permissões...');

        $permissions = [
            // Permissões de domínio
            ['slug' => 'domain-create', 'name' => 'Create Domain', 'description' => 'Criar novos domínios'],
            ['slug' => 'domain-update', 'name' => 'Update Domain', 'description' => 'Atualizar domínios'],
            ['slug' => 'domain-delete', 'name' => 'Delete Domain', 'description' => 'Deletar domínios'],
            ['slug' => 'domain-read', 'name' => 'View Domain', 'description' => 'Visualizar domínios'],
            
            // Permissões de provider
            ['slug' => 'provider-create', 'name' => 'Create Provider', 'description' => 'Criar novos providers'],
            ['slug' => 'provider-update', 'name' => 'Update Provider', 'description' => 'Atualizar providers'],
            ['slug' => 'provider-delete', 'name' => 'Delete Provider', 'description' => 'Deletar providers'],
            ['slug' => 'provider-read', 'name' => 'View Provider', 'description' => 'Visualizar providers'],
            
            // Permissões de domain group
            ['slug' => 'domain-group-create', 'name' => 'Create Domain Group', 'description' => 'Criar grupos de domínios'],
            ['slug' => 'domain-group-update', 'name' => 'Update Domain Group', 'description' => 'Atualizar grupos de domínios'],
            ['slug' => 'domain-group-delete', 'name' => 'Delete Domain Group', 'description' => 'Deletar grupos de domínios'],
            ['slug' => 'domain-group-read', 'name' => 'View Domain Group', 'description' => 'Visualizar grupos de domínios'],
            
            // Permissões de admin
            ['slug' => 'admin-create', 'name' => 'Create Admin', 'description' => 'Criar novos admins'],
            ['slug' => 'admin-update', 'name' => 'Update Admin', 'description' => 'Atualizar admins'],
            ['slug' => 'admin-delete', 'name' => 'Delete Admin', 'description' => 'Deletar admins'],
            ['slug' => 'admin-read', 'name' => 'View Admin', 'description' => 'Visualizar admins'],
            ['slug' => 'admin-assign-users', 'name' => 'Assign Users to Domains', 'description' => 'Adicionar usuários aos domínios'],
            
            // Permissões de dashboard
            ['slug' => 'dashboard-view', 'name' => 'View Dashboard', 'description' => 'Visualizar dashboards'],
            ['slug' => 'dashboard-view-all', 'name' => 'View All Dashboards', 'description' => 'Visualizar dashboards de todos os grupos'],
            
            // Permissões de relatórios
            ['slug' => 'report-view', 'name' => 'View Reports', 'description' => 'Visualizar relatórios'],
            ['slug' => 'report-manage', 'name' => 'Manage Reports', 'description' => 'Gerenciar relatórios'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'description' => $perm['description'],
                    'resource' => explode('-', $perm['slug'])[0],
                    'action' => explode('-', $perm['slug'])[1] ?? 'read',
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Permissões criadas');
    }

    private function createRoles(): void
    {
        $this->command->info('👥 Criando roles...');

        // 1. Sudo Admin (Nível 1)
        $sudoAdmin = Role::firstOrCreate(
            ['slug' => 'sudo-admin'],
            [
                'name' => 'Sudo Admin',
                'description' => 'Acesso total: pode criar domínios, providers e grupos de domínios',
                'is_active' => true,
            ]
        );

        $sudoPermissions = [
            'domain-create', 'domain-update', 'domain-delete', 'domain-read',
            'provider-create', 'provider-update', 'provider-delete', 'provider-read',
            'domain-group-create', 'domain-group-update', 'domain-group-delete', 'domain-group-read',
            'admin-create', 'admin-update', 'admin-delete', 'admin-read', 'admin-assign-users',
            'dashboard-view', 'dashboard-view-all',
            'report-view', 'report-manage',
        ];
        $this->assignPermissions($sudoAdmin, $sudoPermissions);
        $this->command->info('  ✅ Sudo Admin criado');

        // 2. Admin (Nível 2)
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Não pode criar domínios/providers/grupos, mas pode adicionar usuários aos seus domínios',
                'is_active' => true,
            ]
        );

        $adminPermissions = [
            'domain-read',
            'provider-read',
            'domain-group-read',
            'admin-create', 'admin-update', 'admin-read', 'admin-assign-users',
            'dashboard-view', 'dashboard-view-all',
            'report-view', 'report-manage',
        ];
        $this->assignPermissions($admin, $adminPermissions);
        $this->command->info('  ✅ Admin (Nível 2) criado');

        // 3. Manager (Nível 3)
        $manager = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Pode fazer tudo menos adicionar/remover usuários. Vê dashboards de todos os domínios dos seus grupos',
                'is_active' => true,
            ]
        );

        $managerPermissions = [
            'domain-read',
            'provider-read',
            'domain-group-read',
            'admin-read', // Pode ver mas não criar/atualizar/deletar
            'dashboard-view', 'dashboard-view-all',
            'report-view', 'report-manage',
        ];
        $this->assignPermissions($manager, $managerPermissions);
        $this->command->info('  ✅ Manager (Nível 3) criado');

        // 4. Viewer (Nível 4)
        $viewer = Role::firstOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Viewer',
                'description' => 'Apenas visualização dos domínios atribuídos',
                'is_active' => true,
            ]
        );

        $viewerPermissions = [
            'domain-read',
            'provider-read',
            'domain-group-read',
            'dashboard-view',
            'report-view',
        ];
        $this->assignPermissions($viewer, $viewerPermissions);
        $this->command->info('  ✅ Viewer (Nível 4) criado');
    }

    private function assignPermissions(Role $role, array $permissionSlugs): void
    {
        $permissions = Permission::whereIn('slug', $permissionSlugs)->get();
        
        foreach ($permissions as $permission) {
            if (!$role->permissions->contains($permission->id)) {
                $role->permissions()->attach($permission->id);
            }
        }
    }
}

