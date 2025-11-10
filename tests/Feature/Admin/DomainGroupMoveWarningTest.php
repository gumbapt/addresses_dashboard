<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\DomainGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainGroupMoveWarningTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private DomainGroup $sourceGroup;
    private DomainGroup $targetGroup;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin
        $this->superAdmin = Admin::factory()->create([
            'is_super_admin' => true,
        ]);

        // Create two groups
        $this->sourceGroup = DomainGroup::factory()->create([
            'name' => 'Source Group',
            'slug' => 'source-group',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->targetGroup = DomainGroup::factory()->create([
            'name' => 'Target Group',
            'slug' => 'target-group',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /** @test */
    public function warns_when_moving_domains_from_another_group()
    {
        // Arrange - Create domains in source group
        $domains = Domain::factory()->count(3)->create([
            'domain_group_id' => $this->sourceGroup->id,
        ]);
        
        $domainIds = $domains->pluck('id')->toArray();

        // Act - Try to add them to target group (should move them)
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->targetGroup->id}/domains", [
                'domain_ids' => $domainIds,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'group_id' => $this->targetGroup->id,
                    'domains_added' => 0, // None are new
                    'domains_moved' => 3, // All 3 are moved
                ],
            ]);

        // Verify moved_from contains source group info
        $data = $response->json('data');
        $this->assertCount(3, $data['moved_from']);
        $this->assertEquals('Source Group', $data['moved_from'][0]['current_group_name']);
        $this->assertEquals($this->sourceGroup->id, $data['moved_from'][0]['current_group_id']);

        // Verify message mentions movement
        $message = $response->json('message');
        $this->assertStringContainsString('moved from other groups', $message);
        $this->assertStringContainsString('3 domain(s)', $message);

        // Verify in database - domains are now in target group
        foreach ($domains as $domain) {
            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'domain_group_id' => $this->targetGroup->id,
            ]);
        }
    }

    /** @test */
    public function distinguishes_between_added_and_moved_domains()
    {
        // Arrange
        // 2 domains already in source group
        $domainsInSource = Domain::factory()->count(2)->create([
            'domain_group_id' => $this->sourceGroup->id,
        ]);
        
        // 2 domains without group (new)
        $domainsWithoutGroup = Domain::factory()->count(2)->create([
            'domain_group_id' => null,
        ]);
        
        $allDomainIds = $domainsInSource->pluck('id')
            ->merge($domainsWithoutGroup->pluck('id'))
            ->toArray();

        // Act - Add all 4 domains to target group
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->targetGroup->id}/domains", [
                'domain_ids' => $allDomainIds,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'group_id' => $this->targetGroup->id,
                    'domains_added' => 2, // 2 are new
                    'domains_moved' => 2, // 2 are moved
                    'total_updated' => 4, // Total affected
                ],
            ]);

        // Verify message contains both actions
        $message = $response->json('message');
        $this->assertStringContainsString('2 domain(s) added', $message);
        $this->assertStringContainsString('2 domain(s) moved', $message);

        // Verify moved_from only has 2 entries
        $data = $response->json('data');
        $this->assertCount(2, $data['moved_from']);
    }

    /** @test */
    public function moved_domains_do_not_count_against_group_limit()
    {
        // Arrange - Group with limit of 5
        $limitedGroup = DomainGroup::factory()->create([
            'name' => 'Limited Group',
            'slug' => 'limited-group',
            'max_domains' => 5,
            'created_by' => $this->superAdmin->id,
        ]);

        // Already has 4 domains
        Domain::factory()->count(4)->create(['domain_group_id' => $limitedGroup->id]);

        // 3 domains in another group (will be moved)
        $domainsToMove = Domain::factory()->count(3)->create([
            'domain_group_id' => $this->sourceGroup->id,
        ]);

        // 1 new domain (will be added)
        $newDomain = Domain::factory()->create(['domain_group_id' => null]);

        $allDomainIds = $domainsToMove->pluck('id')
            ->push($newDomain->id)
            ->toArray();

        // Act - Try to add 4 domains (3 moved + 1 new)
        // Should succeed because only 1 is actually new (4 + 1 = 5, within limit)
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$limitedGroup->id}/domains", [
                'domain_ids' => $allDomainIds,
            ]);

        // Assert - Should succeed
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'domains_added' => 1, // Only 1 new
                    'domains_moved' => 3, // 3 moved (don't count against limit for validation)
                    'total_domains' => 8, // Final count (4 old + 3 moved + 1 new = 8 total in group)
                ],
            ]);
    }

    /** @test */
    public function fails_when_new_domains_exceed_limit_even_with_moved_ones()
    {
        // Arrange - Group with limit of 5
        $limitedGroup = DomainGroup::factory()->create([
            'name' => 'Limited Group',
            'slug' => 'limited-group',
            'max_domains' => 5,
            'created_by' => $this->superAdmin->id,
        ]);

        // Already has 4 domains
        Domain::factory()->count(4)->create(['domain_group_id' => $limitedGroup->id]);

        // 1 domain in another group (will be moved)
        $domainToMove = Domain::factory()->create([
            'domain_group_id' => $this->sourceGroup->id,
        ]);

        // 2 new domains (will be added)
        $newDomains = Domain::factory()->count(2)->create(['domain_group_id' => null]);

        $allDomainIds = array_merge(
            [$domainToMove->id],
            $newDomains->pluck('id')->toArray()
        );

        // Act - Try to add 3 domains (1 moved + 2 new)
        // Should fail because 2 new would bring total to 6 (4 + 2 = 6 > 5)
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$limitedGroup->id}/domains", [
                'domain_ids' => $allDomainIds,
            ]);

        // Assert - Should fail  
        $response->assertStatus(400);
        
        $message = $response->json('message');
        $this->assertStringContainsString('2 new domains', $message);
        $this->assertStringContainsString('only has 1 available slots', $message);
    }

    /** @test */
    public function shows_source_group_names_in_moved_from_info()
    {
        // Arrange - Domains from different source groups
        $group1 = DomainGroup::factory()->create(['name' => 'Group One']);
        $group2 = DomainGroup::factory()->create(['name' => 'Group Two']);

        $domain1 = Domain::factory()->create(['domain_group_id' => $group1->id, 'name' => 'domain1.com']);
        $domain2 = Domain::factory()->create(['domain_group_id' => $group2->id, 'name' => 'domain2.com']);

        // Act - Move both to target group
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/admin/domain-groups/{$this->targetGroup->id}/domains", [
                'domain_ids' => [$domain1->id, $domain2->id],
            ]);

        // Assert
        $response->assertStatus(200);
        
        $movedFrom = $response->json('data.moved_from');
        $this->assertCount(2, $movedFrom);

        // Find each domain in moved_from
        $domain1Info = collect($movedFrom)->firstWhere('domain_id', $domain1->id);
        $domain2Info = collect($movedFrom)->firstWhere('domain_id', $domain2->id);

        $this->assertEquals('Group One', $domain1Info['current_group_name']);
        $this->assertEquals('Group Two', $domain2Info['current_group_name']);
        $this->assertEquals('domain1.com', $domain1Info['domain_name']);
        $this->assertEquals('domain2.com', $domain2Info['domain_name']);
    }
}

