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

    private Admin $adminWithAllPermissions;
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
        
        // Create admin with ALL permissions using factory
        $this->adminWithAllPermissions = Admin::factory()->create([
            'name' => 'Admin With All Permissions',
            'email' => 'allperms@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        
        // Create admin without permissions using factory
        $this->adminWithoutPermissions = Admin::factory()->create([
            'name' => 'Admin Without Permissions',
            'email' => 'noperms@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        
        // Assign role with ALL permissions to adminWithAllPermissions
        $adminRole = Role::where('slug', 'admin')->first();
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));
        $this->adminWithAllPermissions->roles()->attach($adminRole->id);
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
    public function admin_with_all_permissions_can_access_authorized_endpoints(): void
    {
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        
        // Test role listing (requires role-read permission)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/admin/roles');
        
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function admin_without_specific_permission_cannot_access_endpoint(): void
    {
        // Remove specific permission from admin's role
        $adminRole = $this->adminWithAllPermissions->roles()->first();
        $roleCreatePermission = Permission::where('slug', 'role-create')->first();
        
        // Remove only the role-create permission
        $currentPermissions = $adminRole->permissions()->pluck('permission_id')->toArray();
        $remainingPermissions = array_diff($currentPermissions, [$roleCreatePermission->id]);
        $adminRole->permissions()->sync($remainingPermissions);
        
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        
        // Test role creation (requires role-create permission, which we just removed)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', [
            'name' => 'Test Role',
            'description' => 'Test Description'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Admin ' . $this->adminWithAllPermissions->id . ' does not have permission to perform this action. Required permission: role-create'
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

    /**
     * @test
     */
    public function admin_can_create_role_when_has_create_permission(): void
    {
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        
        // Test role creation (admin has all permissions including role-create)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', [
            'name' => 'Test Role',
            'description' => 'Test Description'
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['role' => ['id', 'name', 'slug', 'description', 'is_active']]
            ]);
    }

    /**
     * @test
     */
    public function admin_cannot_attach_permissions_without_manage_permission(): void
    {
        // Remove role-manage permission from admin's role
        $adminRole = $this->adminWithAllPermissions->roles()->first();
        $roleManagePermission = Permission::where('slug', 'role-manage')->first();
        
        // Remove only the role-manage permission
        $currentPermissions = $adminRole->permissions()->pluck('permission_id')->toArray();
        $remainingPermissions = array_diff($currentPermissions, [$roleManagePermission->id]);
        $adminRole->permissions()->sync($remainingPermissions);
        
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        
        // Test role creation with permissions (requires role-manage permission for attaching permissions)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', [
            'name' => 'Test Role',
            'description' => 'Test Description',
            'permissions' => [1, 2, 3]
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Admin ' . $this->adminWithAllPermissions->id . ' does not have permission to perform this action. Required permission: role-manage'
            ]);
    }
}
