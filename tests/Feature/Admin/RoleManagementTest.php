<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{

    use RefreshDatabase;

    private Admin $sudoAdminModel;
    private Admin $adminWithAllPermissions;

    public function setUp(): void
    {  
        parent::setUp();
        // Seed the database in the correct order
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminRolePermissionSeeder::class);
        
        $this->sudoAdminModel = Admin::where('is_super_admin', true)->first();
        // Create admin with ALL permissions using factory
        $this->adminWithAllPermissions = Admin::factory()->create([
            'name' => 'Admin With All Permissions',
            'email' => 'allperms@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        // Assign role with ALL permissions to adminWithAllPermissions
        $adminRole = Role::where('slug', 'admin')->first();
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));
        $this->adminWithAllPermissions->roles()->attach($adminRole->id, [
            'assigned_at' => now(),
            'assigned_by' => $this->sudoAdminModel->id
        ]);
        // Assert that the seeder worked
        $this->assertNotNull($this->sudoAdminModel, 'Super admin should be created by AdminSeeder');
        $this->assertNotNull($this->adminWithAllPermissions, 'Admin with all permissions should be created');
    }

    /**
     * @test
     */
    public function an_admin_can_list_roles(): void
    {
        // Arrange - Get admin token from admin with all permissions
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/admin/roles');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * @test
     */
    public function an_admin_can_create_a_role(): void
    {
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', ['name' => 'Test Role', 'description' => 'Test Description']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['role' => ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at']]
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'Test Description',
            'is_active' => true
        ]);
    }
    /**
     * @test
     */
     public function an_admin_can_create_a_role_with_permissions(): void
     {
        // Arrange
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        $roleData = [
            'name' => 'Test Role with Permissions', 
            'description' => 'Test Description with permissions',
            'permissions' => [1, 2, 3]
        ];
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', $roleData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'role' => [
                        'id', 
                        'name', 
                        'slug', 
                        'description', 
                        'is_active', 
                        'created_at', 
                        'updated_at', 
                        'permissions' => [
                            '*' => [
                                'id',
                                'slug',
                                'name',
                                'description',
                                'is_active',
                                'resource',
                                'action'
                            ]
                        ]
                    ]
                ]
            ]);

        // Assert specific values
        $responseData = $response->json('data.role');
        
        $this->assertEquals('Test Role with Permissions', $responseData['name']);
        $this->assertEquals('test-role-with-permissions', $responseData['slug']);
        $this->assertEquals('Test Description with permissions', $responseData['description']);
        $this->assertTrue($responseData['is_active']);
        
        // Assert permissions were attached correctly
        $this->assertCount(3, $responseData['permissions']);
        $this->assertContains(1, array_column($responseData['permissions'], 'id'));
        $this->assertContains(2, array_column($responseData['permissions'], 'id'));
        $this->assertContains(3, array_column($responseData['permissions'], 'id'));
        
        // Assert role was actually created in database
        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role with Permissions',
            'slug' => 'test-role-with-permissions',
            'description' => 'Test Description with permissions',
            'is_active' => true
        ]);
        
        // Assert permissions were attached in pivot table
        $roleId = $responseData['id'];
        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => 1]);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => 2]);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => 3]);
     }

    /**
     * @test
     */
    public function admin_cannot_create_role_without_create_permission(): void
    {
        // Remove role-create permission from admin's role
        $adminRole = $this->adminWithAllPermissions->roles()->first();
        $roleCreatePermission = Permission::where('slug', 'role-create')->first();
        
        // Remove only the role-create permission
        $currentPermissions = $adminRole->permissions()->pluck('permission_id')->toArray();
        $remainingPermissions = array_diff($currentPermissions, [$roleCreatePermission->id]);
        $adminRole->permissions()->sync($remainingPermissions);
        
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        
        // Test role creation (should fail because we removed role-create permission)
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
    public function admin_cannot_list_roles_without_read_permission(): void
    {
        // Remove role-read permission from admin's role
        $adminRole = $this->adminWithAllPermissions->roles()->first();
        $roleReadPermission = Permission::where('slug', 'role-read')->first();
        
        // Remove only the role-read permission
        $currentPermissions = $adminRole->permissions()->pluck('permission_id')->toArray();
        $remainingPermissions = array_diff($currentPermissions, [$roleReadPermission->id]);
        $adminRole->permissions()->sync($remainingPermissions);
        
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        
        // Test role listing (should fail because we removed role-read permission)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('/api/admin/roles');
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Admin ' . $this->adminWithAllPermissions->id . ' does not have permission to perform this action. Required permission: role-read'
            ]);
    }

     /**
      * @test
      */
      public function an_admin_cannot_update_a_role_without_permission(): void
      {
        // Remove role-update permission from admin's role BEFORE making the request
        $adminRole = $this->adminWithAllPermissions->roles()->first();
        $roleUpdatePermission = Permission::where('slug', 'role-update')->first();
        $currentPermissions = $adminRole->permissions()->pluck('permission_id')->toArray();
        $remainingPermissions = array_diff($currentPermissions, [$roleUpdatePermission->id]);
        $adminRole->permissions()->sync($remainingPermissions);
        $token = $this->adminWithAllPermissions->createToken('test-token')->plainTextToken;
        // Test role update (should fail because we removed role-update permission)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->put('/api/admin/role/update', ['id' => 1, 'name' => 'Test Role', 'description' => 'Test Description']);
        $response->assertStatus(403)
            ->assertJson(['error' => 'Admin ' . $this->adminWithAllPermissions->id . ' does not have permission to perform this action. Required permission: role-update']);
     
    }

}
