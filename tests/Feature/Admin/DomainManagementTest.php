<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $adminWithDomainPermissions;

    public function setUp(): void
    {
        parent::setUp();
        
        // Seed the database
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminRolePermissionSeeder::class);
        $this->seed(DomainSeeder::class);
        
        $this->superAdmin = Admin::where('is_super_admin', true)->first();
        
        // Create admin with domain permissions
        $this->adminWithDomainPermissions = Admin::factory()->create([
            'name' => 'Domain Admin',
            'email' => 'domainadmin@test.com',
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        
        // Assign role with domain permissions
        $adminRole = Role::where('slug', 'admin')->first();
        $this->adminWithDomainPermissions->roles()->attach($adminRole->id, [
            'assigned_at' => now(),
            'assigned_by' => $this->superAdmin->id
        ]);
    }

    /** @test */
    public function super_admin_can_list_domains(): void
    {
        $this->withoutExceptionHandling();
        // Arrange
        Domain::factory()->count(5)->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domains');
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'domain_url',
                        'site_id',
                        'api_key',
                        'status',
                        'timezone',
                        'is_active'
                    ]
                ],
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page'
                ]
            ]);
    }

    /** @test */
    public function can_paginate_domains(): void
    {
        // Arrange
        Domain::factory()->count(30)->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domains?page=1&per_page=10');
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.current_page', 1);
        
        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function can_search_domains_by_name(): void
    {
        // Arrange
        Domain::factory()->create(['name' => 'SmarterHome ISP']);
        Domain::factory()->create(['name' => 'BroadbandSearch']);
        Domain::factory()->count(5)->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domains?search=SmarterHome');
        
        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        $foundDomain = collect($data)->first(fn($domain) => str_contains($domain['name'], 'SmarterHome'));
        $this->assertNotNull($foundDomain);
    }

    /** @test */
    public function can_filter_domains_by_active_status(): void
    {
        // Arrange
        Domain::factory()->count(3)->create(['is_active' => true]);
        Domain::factory()->count(2)->inactive()->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domains?is_active=true');
        
        // Assert
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $domain) {
            $this->assertTrue($domain['is_active']);
        }
    }

    /** @test */
    public function super_admin_can_create_domain(): void
    {
        // Arrange
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        $domainData = [
            'name' => 'New ISP Platform',
            'domain_url' => 'api.newisp.com',
            'site_id' => 'wp-prod-newisp-001',
            'timezone' => 'America/New_York',
            'wordpress_version' => '6.8.3',
            'plugin_version' => '2.0.0',
            'settings' => [
                'enable_notifications' => true,
                'report_frequency' => 'daily'
            ]
        ];
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/admin/domains', $domainData);
        
        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'api_key',
                    'is_active'
                ]
            ]);
        
        $this->assertDatabaseHas('domains', [
            'name' => 'New ISP Platform',
            'domain_url' => 'api.newisp.com',
            'slug' => 'new-isp-platform'
        ]);
        
        // Verify API key was generated
        $this->assertStringStartsWith('dmn_live_', $response->json('data.api_key'));
    }

    /** @test */
    public function super_admin_can_update_domain(): void
    {
        // Arrange
        $domain = Domain::factory()->create(['name' => 'Original Name']);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/admin/domains/{$domain->id}", [
            'name' => 'Updated Name',
            'is_active' => false
        ]);
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Domain updated successfully'
            ]);
        
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'is_active' => false
        ]);
    }

    /** @test */
    public function super_admin_can_delete_domain(): void
    {
        // Arrange
        $domain = Domain::factory()->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/admin/domains/{$domain->id}");
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Domain deleted successfully'
            ]);
        
        $this->assertDatabaseMissing('domains', [
            'id' => $domain->id
        ]);
    }

    /** @test */
    public function super_admin_can_regenerate_api_key(): void
    {
        // Arrange
        $domain = Domain::factory()->create();
        $originalApiKey = $domain->api_key;
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson("/api/admin/domains/{$domain->id}/regenerate-api-key");
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
        
        $newApiKey = $response->json('data.api_key');
        $this->assertNotEquals($originalApiKey, $newApiKey);
        $this->assertStringStartsWith('dmn_live_', $newApiKey);
        
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'api_key' => $newApiKey
        ]);
    }

    /** @test */
    public function super_admin_can_get_domain_by_id(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'name' => 'Test Domain'
        ]);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson("/api/admin/domains/{$domain->id}");
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $domain->id,
                    'name' => 'Test Domain'
                ]
            ]);
    }

    /** @test */
    public function creates_unique_slug_even_with_same_name(): void
    {
        // Arrange
        $firstDomain = Domain::factory()->create(['name' => 'Test Domain', 'slug' => 'test-domain']);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act - Create another with same name (should succeed with different slug)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/admin/domains', [
            'name' => 'Test Domain',
            'domain_url' => 'different.com'
        ]);
        
        // Assert - Should create successfully (slug will be auto-generated as unique)
        $response->assertStatus(201);
        // Verify second domain has unique slug
        $secondDomain = Domain::find($response->json('data.id'));
        $this->assertEquals('test-domain-1', $secondDomain->slug);
        // Verify both domains exist with different slugs
        $this->assertDatabaseHas('domains', ['slug' => 'test-domain']);
        $this->assertDatabaseHas('domains', ['slug' => 'test-domain-1']);
    }

    /** @test */
    public function cannot_create_domain_without_required_fields(): void
    {
        // Arrange
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/admin/domains', []);
        
        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'domain_url']);
    }

    /** @test */
    public function admin_without_domain_read_cannot_list_domains(): void
    {
        // Arrange
        $adminRole = Role::where('slug', 'admin')->first();
        $domainReadPermission = Permission::where('slug', 'domain-read')->first();
        
        // Remove domain-read permission if it exists
        if ($domainReadPermission) {
            $currentPermissions = $adminRole->permissions()->pluck('permissions.id')->toArray();
            $remainingPermissions = array_diff($currentPermissions, [$domainReadPermission->id]);
            $adminRole->permissions()->sync($remainingPermissions);
        }
        
        $token = $this->adminWithDomainPermissions->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domains');
        
        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_domains(): void
    {
        // Act
        $response = $this->getJson('/api/admin/domains');
        
        // Assert
        $response->assertStatus(401);
    }
}

