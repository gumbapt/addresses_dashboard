<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\DomainGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainGroupBatchOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $regularAdmin;
    private DomainGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin
        $this->superAdmin = Admin::factory()->create([
            'is_super_admin' => true,
        ]);

        // Create Regular Admin
        $this->regularAdmin = Admin::factory()->create([
            'is_super_admin' => false,
        ]);

        // Create a domain group
        $this->group = DomainGroup::factory()->create([
            'name' => 'Test Group',
            'slug' => 'test-group',
            'max_domains' => 10,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /** @test */
    public function super_admin_can_add_domains_to_group()
    {
        // Arrange
        $domains = Domain::factory()->count(3)->create();
        
        $domainIds = $domains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'group_id' => $this->group->id,
                    'domains_added' => 3,
                    'domains_moved' => 0,
                    'total_requested' => 3,
                ],
            ]);

        // Verify in database
        foreach ($domains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => $this->group->id,
            ]);
        }
    }

    /** @test */
    public function regular_admin_cannot_add_domains_to_group()
    {
        // Arrange
        $domains = Domain::factory()->count(2)->create();
        
        $domainIds = $domains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->regularAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(403);

        // Verify NOT in database
        foreach ($domains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => null,
            ]);
        }
    }

    /** @test */
    public function cannot_add_domains_exceeding_group_limit()
    {
        // Arrange
        $group = DomainGroup::factory()->create([
            'max_domains' => 3,
            'created_by' => $this->superAdmin->id,
        ]);

        // Already add 2 domains
        Domain::factory()->count(2)->create(['domain_group_id' => $group->id]);

        // Try to add 3 more (would be 5 total, exceeding limit of 3)
        $newDomains = Domain::factory()->count(3)->create();
        $domainIds = $newDomains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$group->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        // Verify domains were NOT added
        foreach ($newDomains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => null,
            ]);
        }
    }

    /** @test */
    public function can_add_domains_to_unlimited_group()
    {
        // Arrange
        $group = DomainGroup::factory()->create([
            'max_domains' => null, // Unlimited
            'created_by' => $this->superAdmin->id,
        ]);

        // Create many domains
        $domains = Domain::factory()->count(50)->create();
        $domainIds = $domains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$group->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'domains_added' => 50,
                ],
            ]);
    }

    /** @test */
    public function super_admin_can_remove_domains_from_group()
    {
        // Arrange
        $domains = Domain::factory()->count(3)->create([
            'domain_group_id' => $this->group->id,
        ]);
        
        $domainIds = $domains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'group_id' => $this->group->id,
                    'domains_removed' => 3,
                    'total_requested' => 3,
                ],
            ]);

        // Verify in database
        foreach ($domains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => null,
            ]);
        }
    }

    /** @test */
    public function regular_admin_cannot_remove_domains_from_group()
    {
        // Arrange
        $domains = Domain::factory()->count(2)->create([
            'domain_group_id' => $this->group->id,
        ]);
        
        $domainIds = $domains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->regularAdmin, 'sanctum')
            ->deleteJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(403);

        // Verify STILL in group
        foreach ($domains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => $this->group->id,
            ]);
        }
    }

    /** @test */
    public function validation_error_when_domain_ids_missing()
    {
        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                // Missing domain_ids
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain_ids']);
    }

    /** @test */
    public function validation_error_when_domain_ids_not_array()
    {
        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => 'not-an-array',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain_ids']);
    }

    /** @test */
    public function validation_error_when_domain_ids_empty()
    {
        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => [],
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain_ids']);
    }

    /** @test */
    public function validation_error_when_domain_ids_invalid()
    {
        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->group->id}/domains", [
                'domain_ids' => [999, 998, 997], // Non-existent IDs
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain_ids.0', 'domain_ids.1', 'domain_ids.2']);
    }

    /** @test */
    public function returns_404_when_group_not_found()
    {
        // Arrange
        $domains = Domain::factory()->count(2)->create();
        $domainIds = $domains->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/999/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function can_move_domains_between_groups()
    {
        // Arrange
        $group1 = DomainGroup::factory()->create(['created_by' => $this->superAdmin->id]);
        $group2 = DomainGroup::factory()->create(['created_by' => $this->superAdmin->id]);
        
        $domains = Domain::factory()->count(3)->create(['domain_group_id' => $group1->id]);
        $domainIds = $domains->pluck('id')->toArray();

        // Act 1: Remove from group1
        $response1 = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/admin/domain-groups/{$group1->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Act 2: Add to group2
        $response2 = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$group2->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verify domains are now in group2
        foreach ($domains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => $group2->id,
            ]);
        }
    }
}

