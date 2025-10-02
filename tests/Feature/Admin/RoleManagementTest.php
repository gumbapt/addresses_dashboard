<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
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

    public function setUp(): void
    {  
        parent::setUp();
        
        // Seed the database in the correct order
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminRolePermissionSeeder::class);
        $this->sudoAdminModel = Admin::where('is_super_admin', true)->first();
        // Assert that the seeder worked
        $this->assertNotNull($this->sudoAdminModel, 'Super admin should be created by AdminSeeder');
    }

    /**
     * @test
     */
    public function an_admin_can_list_roles(): void
    {
        // Arrange - Get admin token from seeded admin

        $token = $this->sudoAdminModel->createToken('test-token')->plainTextToken;
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
        dd('test');
        $token = $this->sudoAdminModel->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post('/api/admin/role/create', ['name' => 'Test Role', 'description' => 'Test Description']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['role' => ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at']]
            ]);
    }
    /**
     * @test
     */
     public function an_admin_can_create_a_role_with_permissions(): void
     {
        // Arrange
        $token = $this->sudoAdminModel->createToken('test-token')->plainTextToken;
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

}
