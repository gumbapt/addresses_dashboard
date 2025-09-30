<?php

namespace Tests\Feature\Admin;

use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {  
        
        parent::setUp();
        $this->seed(
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,
            AdminRolePermissionSeeder::class
        );
    }

    /**
     * @test
     */
    public function an_admin_can_list_roles(): void
    {
        // Arrange - Get admin token from seeded admin
        $admin = \App\Models\Admin::first();
        $token = $admin->createToken('test-token')->plainTextToken;

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
