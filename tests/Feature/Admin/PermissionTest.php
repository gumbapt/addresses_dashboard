<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Permission;
use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private Admin $adminWithPermissions;
    private Admin $adminWithoutPermissions;
    private Admin $superAdmin;

    public function setUp(): void
    {
        parent::setUp();
        
        // Seed the database
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminRolePermissionSeeder::class);
        
        $this->superAdmin = Admin::where('is_super_admin', true)->first();
        
        // Create admin with limited permissions
        $this->adminWithPermissions = Admin::create([
            'name' => 'Limited Admin',
            'email' => 'limited@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        
        // Create admin without permissions
        $this->adminWithoutPermissions = Admin::create([
            'name' => 'No Permissions Admin',
            'email' => 'noperms@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        
        // Assign role with limited permissions to adminWithPermissions
        $limitedRole = Role::where('slug', 'admin')->first();
        $limitedPermissions = Permission::whereIn('slug', ['role-read', 'user-read'])->get();
        $limitedRole->permissions()->sync($limitedPermissions->pluck('id'));
        $this->adminWithPermissions->roles()->attach($limitedRole->id);
    }

    /**
     * @test
     */
    public function super_admin_can_access_all_endpoints(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Test role creation (requires role-create permission)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', [
            'name' => 'Test Role',
            'description' => 'Test Description'
        ]);
        
        $response->assertStatus(201);
    }

    /**
     * @test
     */
    public function admin_with_permission_can_access_authorized_endpoints(): void
    {
        $token = $this->adminWithPermissions->createToken('test-token')->plainTextToken;
        
        // Test role listing (requires role-read permission)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/admin/roles');
        
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function admin_with_permission_cannot_access_unauthorized_endpoints(): void
    {
        $token = $this->adminWithPermissions->createToken('test-token')->plainTextToken;
        
        // Test role creation (requires role-create permission, which this admin doesn't have)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', [
            'name' => 'Test Role',
            'description' => 'Test Description'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Admin ' . $this->adminWithPermissions->id . ' does not have permission to perform this action. Required permission: role-create'
            ]);
    }

    /**
     * @test
     */
    public function admin_without_permissions_cannot_access_protected_endpoints(): void
    {
        $token = $this->adminWithoutPermissions->createToken('test-token')->plainTextToken;
        
        // Test role listing (requires role-read permission)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/admin/roles');
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Admin ' . $this->adminWithoutPermissions->id . ' does not have permission to perform this action. Required permission: role-read'
            ]);
    }
}
