<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Admin::where('email', 'admin@dashboard.com')->first();
        $admin = Admin::where('email', 'admin2@dashboard.com')->first();

        // Role permission matrices are defined in RoleSeeder; this seeder only attaches roles to demo admins.
        $adminRole = Role::where('slug', 'admin')->first();
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        if ($adminRole && $superAdminRole) {
            // Assign admin role to secondary admin
            if ($admin) {
                $admin->roles()->syncWithPivotValues([$adminRole->id], [
                    'assigned_at' => now(),
                    'assigned_by' => $superAdmin->id
                ]);
                $this->command->info("✅ 'admin' role assigned to Secondary Admin");
            }
            if ($superAdmin) {
                $superAdmin->roles()->syncWithPivotValues([$superAdminRole->id], [
                    'assigned_at' => now(),
                    'assigned_by' => $superAdmin->id
                ]);
                $this->command->info("✅ 'super-admin' role assigned to Super Admin");
            }
        } else {
            $this->command->warn("⚠️ Roles not found. Run RoleSeeder first.");
        }
    }
}
