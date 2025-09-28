<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar todos os admins
        $superAdmin = Admin::where('email', 'admin@dashboard.com')->first();
        $admin = Admin::where('email', 'admin2@dashboard.com')->first();

        // Buscar todas as permissões
        $permissions = Permission::all();

        // Buscar roles
        $adminRole = Role::where('slug', 'admin')->first();
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        // Atribuir todas as permissões às roles
        if (!$permissions->isEmpty()) {
            // Atribuir todas as permissões à role super-admin
            if ($superAdminRole) {
                $superAdminRole->permissions()->sync($permissions->pluck('id')->toArray());
                $this->command->info("✅ Todas as permissões atribuídas à role 'super-admin'");
            }

            // Atribuir todas as permissões à role admin
            if ($adminRole) {
                $adminRole->permissions()->sync($permissions->pluck('id')->toArray());
                $this->command->info("✅ Todas as permissões atribuídas à role 'admin'");
            }
        } else {
            $this->command->warn("⚠️ Nenhuma permissão encontrada. Execute PermissionSeeder primeiro.");
        }

        if ($adminRole && $superAdminRole) {
            // Atribuir role de admin ao admin secundário
            if ($admin) {
                $admin->roles()->syncWithPivotValues([$adminRole->id], [
                    'assigned_at' => now(),
                    'assigned_by' => $superAdmin->id
                ]);
                $this->command->info("✅ Role 'admin' atribuída ao Admin secundário");
            }

            // Atribuir role de super-admin ao super admin
            if ($superAdmin) {
                $superAdmin->roles()->syncWithPivotValues([$superAdminRole->id], [
                    'assigned_at' => now(),
                    'assigned_by' => $superAdmin->id
                ]);
                $this->command->info("✅ Role 'super-admin' atribuída ao Super Admin");
            }
        } else {
            $this->command->warn("⚠️ Roles não encontradas. Execute RoleSeeder primeiro.");
        }
    }
}
