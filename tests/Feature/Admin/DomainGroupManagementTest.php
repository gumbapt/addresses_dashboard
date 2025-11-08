<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\DomainGroup;
use App\Models\Domain;
use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $regularAdmin;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminRolePermissionSeeder::class);
        
        $this->superAdmin = Admin::where('is_super_admin', true)->first();
        $this->regularAdmin = Admin::factory()->create(['is_super_admin' => false]);
    }

    /** @test */
    public function super_admin_can_list_domain_groups(): void
    {
        // Arrange
        DomainGroup::factory()->count(5)->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domain-groups');
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'max_domains',
                    ]
                ],
                'pagination'
            ]);
    }

    /** @test */
    public function super_admin_can_create_domain_group(): void
    {
        // Arrange
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        $groupData = [
            'name' => 'Premium Partners',
            'description' => 'Premium tier clients',
            'max_domains' => 20,
            'is_active' => true,
            'settings' => [
                'tier' => 'premium',
                'support' => 'priority',
            ],
        ];
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/admin/domain-groups', $groupData);
        
        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Domain group created successfully.',
            ])
            ->assertJsonPath('data.name', 'Premium Partners')
            ->assertJsonPath('data.slug', 'premium-partners')
            ->assertJsonPath('data.max_domains', 20);
        
        $this->assertDatabaseHas('domain_groups', [
            'name' => 'Premium Partners',
            'slug' => 'premium-partners',
            'max_domains' => 20,
        ]);
    }

    /** @test */
    public function super_admin_can_update_domain_group(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create(['name' => 'Old Name']);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/admin/domain-groups/{$group->id}", [
            'name' => 'Updated Name',
            'max_domains' => 30,
        ]);
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Domain group updated successfully.',
            ]);
        
        $this->assertDatabaseHas('domain_groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
            'max_domains' => 30,
        ]);
    }

    /** @test */
    public function super_admin_can_delete_empty_domain_group(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create();
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/admin/domain-groups/{$group->id}");
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Domain group deleted successfully.',
            ]);
        
        $this->assertSoftDeleted('domain_groups', ['id' => $group->id]);
    }

    /** @test */
    public function cannot_delete_domain_group_with_domains(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create();
        Domain::factory()->count(3)->create(['domain_group_id' => $group->id]);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/admin/domain-groups/{$group->id}");
        
        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
        
        $this->assertDatabaseHas('domain_groups', ['id' => $group->id]);
    }

    /** @test */
    public function regular_admin_cannot_create_domain_group(): void
    {
        // Arrange
        $token = $this->regularAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/admin/domain-groups', [
            'name' => 'Test Group',
        ]);
        
        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Access denied. Only Super Admins can perform this action.',
            ]);
    }

    /** @test */
    public function regular_admin_cannot_update_domain_group(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create();
        $token = $this->regularAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/admin/domain-groups/{$group->id}", [
            'name' => 'Updated Name',
        ]);
        
        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function regular_admin_cannot_delete_domain_group(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create();
        $token = $this->regularAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/admin/domain-groups/{$group->id}");
        
        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function can_get_domain_group_details(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create(['name' => 'Test Group']);
        Domain::factory()->count(3)->create(['domain_group_id' => $group->id]);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson("/api/admin/domain-groups/{$group->id}");
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Group')
            ->assertJsonPath('data.domains_count', 3);
    }

    /** @test */
    public function can_get_domains_of_group(): void
    {
        // Arrange
        $group = DomainGroup::factory()->create();
        $domains = Domain::factory()->count(5)->create(['domain_group_id' => $group->id]);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson("/api/admin/domain-groups/{$group->id}/domains");
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.total', 5);
    }

    /** @test */
    public function can_filter_domain_groups_by_search(): void
    {
        // Arrange
        DomainGroup::factory()->create(['name' => 'Production Servers']);
        DomainGroup::factory()->create(['name' => 'Staging Servers']);
        DomainGroup::factory()->create(['name' => 'Development']);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domain-groups?search=Production');
        
        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Production Servers', $data[0]['name']);
    }

    /** @test */
    public function can_filter_domain_groups_by_active_status(): void
    {
        // Arrange
        DomainGroup::factory()->count(3)->create(['is_active' => true]);
        DomainGroup::factory()->count(2)->create(['is_active' => false]);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/domain-groups?is_active=1');
        
        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function slug_is_generated_automatically_if_not_provided(): void
    {
        // Arrange
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/admin/domain-groups', [
            'name' => 'My Test Group',
        ]);
        
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('domain_groups', [
            'name' => 'My Test Group',
            'slug' => 'my-test-group',
        ]);
    }
}

