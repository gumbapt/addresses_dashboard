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
}
