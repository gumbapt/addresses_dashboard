<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Admin;
use App\Models\Domain;
use App\Domain\Services\DomainPermissionService;

class DomainPermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding Domain Permissions...');
        $this->command->newLine();

        // 1. Criar permissões de acesso a domínios
        $this->createDomainPermissions();

        // 2. Atribuir permissão global ao super-admin
        $this->assignGlobalAccessToSuperAdmin();

        // 3. Criar role de exemplo: Domain Manager
        $this->createDomainManagerRole();

        // 4. Exemplo prático: Atribuir domínios a roles
        $this->assignExampleDomains();

        $this->command->newLine();
        $this->command->info('✅ Domain permissions seeded successfully!');
    }

    private function createDomainPermissions(): void
    {
        $permissions = [
            [
                'slug' => 'domain.access.all',
                'name' => 'Access All Domains',
                'description' => 'Can access reports from all domains without restrictions',
                'resource' => 'domain',
                'action' => 'access-all',
                'is_active' => true,
            ],
            [
                'slug' => 'domain.access.assigned',
                'name' => 'Access Assigned Domains',
                'description' => 'Can access only specifically assigned domains',
                'resource' => 'domain',
                'action' => 'access-assigned',
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );

            if ($permission->wasRecentlyCreated) {
                $this->command->info("  ✅ Permissão criada: {$permission->name}");
            } else {
                $this->command->line("  ℹ️  Permissão já existe: {$permission->name}");
            }
        }
    }

    private function assignGlobalAccessToSuperAdmin(): void
    {
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        
        if (!$superAdminRole) {
            $this->command->warn('  ⚠️  Role super-admin não encontrada');
            return;
        }

        $globalPermission = Permission::where('slug', 'domain.access.all')->first();
        
        if (!$superAdminRole->permissions->contains($globalPermission->id)) {
            $superAdminRole->permissions()->attach($globalPermission);
            $this->command->info("  ✅ Permissão 'domain.access.all' atribuída a super-admin");
        } else {
            $this->command->line("  ℹ️  super-admin já tem permissão global de domínios");
        }
    }

    private function createDomainManagerRole(): void
    {
        $domainManagerRole = Role::firstOrCreate(
            ['slug' => 'domain-manager'],
            [
                'name' => 'Domain Manager',
                'description' => 'Can manage assigned domains and view their reports',
                'is_active' => true,
            ]
        );

        if ($domainManagerRole->wasRecentlyCreated) {
            $this->command->info("  ✅ Role criada: Domain Manager");
        } else {
            $this->command->line("  ℹ️  Role já existe: Domain Manager");
        }

        // Atribuir permissão de acesso a domínios atribuídos
        $assignedPermission = Permission::where('slug', 'domain.access.assigned')->first();
        
        if (!$domainManagerRole->permissions->contains($assignedPermission->id)) {
            $domainManagerRole->permissions()->attach($assignedPermission);
            $this->command->info("  ✅ Permissão 'domain.access.assigned' atribuída a Domain Manager");
        }

        // Atribuir permissões básicas de relatórios
        $reportPermissions = Permission::whereIn('slug', [
            'report.view',
            'report.create',
        ])->get();

        foreach ($reportPermissions as $permission) {
            if (!$domainManagerRole->permissions->contains($permission->id)) {
                $domainManagerRole->permissions()->attach($permission);
            }
        }
    }

    private function assignExampleDomains(): void
    {
        $domainManagerRole = Role::where('slug', 'domain-manager')->first();
        $superAdmin = Admin::where('email', 'admin@dashboard.com')->first();
        
        if (!$domainManagerRole || !$superAdmin) {
            $this->command->warn('  ⚠️  Não foi possível criar exemplo (role ou admin não encontrado)');
            return;
        }

        $domains = Domain::where('is_active', true)->limit(2)->pluck('id')->toArray();
        
        if (empty($domains)) {
            $this->command->warn('  ⚠️  Nenhum domínio ativo encontrado para exemplo');
            return;
        }

        $service = app(DomainPermissionService::class);
        $service->assignDomainsToRole(
            $domainManagerRole,
            $domains,
            $superAdmin,
            [
                'can_view' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_submit_reports' => false,
            ]
        );

        $assignedDomainNames = Domain::whereIn('id', $domains)->pluck('name')->toArray();
        $this->command->info("  ✅ Exemplo: Domínios atribuídos a 'Domain Manager': " . implode(', ', $assignedDomainNames));
    }
}

