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
        $this->seed(RoleSeeder::class,PermissionSeeder::class,AdminSeeder::class,AdminRolePermissionSeeder::class);
        // $this->admin = Admin::factory()->create([
        //     'email' => 'admin@dashboard_addresses.com',
        //     'password' => bcrypt('password123')
        // ]);
    }

    /**
     * @test
     */
    public function an_admin_can_list_roles(): void
    {
        $response = $this->get('/api/admin/roles');

        $response->assertStatus(200);
    }
}
